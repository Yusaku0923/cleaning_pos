<?php

namespace App\Http\Controllers\Api;

use App\Domain\Delivery\DeliverySummary;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryProduct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** 旧 Api\DeliveryEntryController (日次入力・集計プレビュー)。取引先・品目は自店舗のものだけ。 */
class DeliveryEntryController extends Controller
{
    /** 顧客一覧（部署・商品付き） */
    public function customers(): JsonResponse
    {
        return response()->json(['customers' => DeliveryCustomer::with(['departments.products'])->get()]);
    }

    /** 顧客の部署・商品一覧 */
    public function departments(int $id): JsonResponse
    {
        $customer = DeliveryCustomer::with(['departments.products'])->findOrFail($id);

        return response()->json(['departments' => $customer->departments]);
    }

    /** 日次入力一覧取得（顧客ID・日付でフィルタ） */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => $this->customerRule($request),
            'date' => 'required|date',
        ]);

        $entries = DeliveryDailyEntry::whereHas('product.department', function ($q) use ($request) {
            $q->where('delivery_customer_id', $request->customer_id);
        })->where('date', $request->date)->with('product.department')->get();

        return response()->json(['entries' => $entries]);
    }

    /** 日次入力保存（upsert） */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'entries' => 'required|array',
            'entries.*.delivery_product_id' => 'required|integer',
            'entries.*.date' => 'required|date',
            'entries.*.quantity' => 'required|integer|min:0',
            'entries.*.note' => 'nullable|string|max:255',
        ]);

        // 品目は自店舗のものだけ (exists ルールは店舗のスコープを通らないので、モデルで数える)
        $ids = collect($request->entries)->pluck('delivery_product_id')->unique();
        if (DeliveryProduct::whereIn('id', $ids)->count() !== $ids->count()) {
            throw ValidationException::withMessages(['entries' => '存在しない商品が含まれています。']);
        }

        // (品目, 日付) の一意制約に対する upsert。旧の updateOrCreate は同時に 2 台から保存すると
        // 一意制約違反 (500) になり得たので、1 文で入れ替える。同じ (品目, 日付) が複数あれば後のものが勝つ。
        $now = now();
        $rows = collect($request->entries)
            ->map(fn ($e) => [
                'delivery_product_id' => $e['delivery_product_id'],
                'date' => Carbon::parse($e['date'])->toDateString(),
                'quantity' => $e['quantity'],
                'note' => $e['note'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->keyBy(fn ($r) => $r['delivery_product_id'].'|'.$r['date'])
            ->values()->all();

        DB::transaction(fn () => DeliveryDailyEntry::upsert($rows, ['delivery_product_id', 'date'], ['quantity', 'note', 'updated_at']));

        return response()->json(['ok' => true]);
    }

    /** 顧客の入力済み日付一覧（ステータス付き）直近60日分 */
    public function entryStatus(Request $request): JsonResponse
    {
        $request->validate(['customer_id' => $this->customerRule($request)]);

        $entries = DeliveryDailyEntry::whereHas('product.department', function ($q) use ($request) {
            $q->where('delivery_customer_id', $request->customer_id);
        })
            ->where('date', '>=', now()->subDays(60)->toDateString())
            ->where('quantity', '>', 0)
            ->get()
            ->groupBy(fn ($e) => $e->date->toDateString())
            ->map(fn ($group) => [
                'date' => $group->first()->date->toDateString(),
                'finalized' => $group->some(fn ($e) => ! is_null($e->delivery_note_id)),
            ])
            ->values();

        return response()->json(['dates' => $entries]);
    }

    /** 日次入力削除（顧客ID・日付で一括削除） */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => $this->customerRule($request),
            'date' => 'required|date',
        ]);

        DeliveryDailyEntry::whereHas('product.department', function ($q) use ($request) {
            $q->where('delivery_customer_id', $request->customer_id);
        })->where('date', $request->date)->delete();

        return response()->json(['ok' => true]);
    }

    /** 集計プレビュー（iPad管理画面用） */
    public function preview(Request $request, DeliverySummary $summary): JsonResponse
    {
        $request->validate([
            'customer_id' => $this->customerRule($request),
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        $customer = DeliveryCustomer::findOrFail($request->customer_id);
        $result = $summary->summarize($customer, $request->period_start, $request->period_end);
        $note = $summary->existingNote($customer, $request->period_start, $request->period_end);

        $defaultEmailBody = $customer->email
            ? $summary->defaultEmailBody($customer, Carbon::parse($request->period_start), Carbon::parse($request->period_end))
            : '';

        return response()->json([
            'entries' => $result['entries'],
            'tax_groups' => $result['tax_groups'],
            'has_finalized' => $result['has_finalized'],
            'note_id' => $note?->id,
            'default_email_body' => $defaultEmailBody,
        ]);
    }

    /** customer_id は自店舗の取引先だけ (他店舗は 422)。 */
    private function customerRule(Request $request): array
    {
        return ['required', 'integer', Rule::exists('delivery_customers', 'id')->where('store_id', $request->user()->getKey())];
    }
}
