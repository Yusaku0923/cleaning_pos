<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\Manager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 選択中の担当者・顧客を URL クエリ (?m={managerId}&c={customerId}) から読み、
 * request()->attributes の `manager` (Manager|null) と `customer` (Customer|null) に入れる。
 *
 * 旧はセッション (manager_id / customer_id) に持っていたため、タブを 2 つ開くと顧客が混ざった。
 * v2 はセッションを読みも書きもしない。
 *
 * - 存在しない ID・他店舗の ID (BelongsToStore のスコープで見えない) は 404。
 * - 顧客の担当者が m と一致しない、または m が無いときは顧客は未選択 (旧ホームと同じ)。
 */
class ResolvePosContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $manager = $this->find(Manager::class, $request->query('m'));
        $customer = $this->find(Customer::class, $request->query('c'));

        if ($customer !== null && ($manager === null || (int) $customer->manager_id !== (int) $manager->id)) {
            $customer = null;
        }

        $request->attributes->set('manager', $manager);
        $request->attributes->set('customer', $customer);

        return $next($request);
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    private function find(string $class, mixed $id): mixed
    {
        if ($id === null || $id === '') {
            return null;
        }
        if (! is_string($id) || ! ctype_digit($id)) {
            abort(404);
        }

        return $class::findOrFail((int) $id);
    }
}
