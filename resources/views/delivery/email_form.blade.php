@extends('layouts.app')
@section('content')
<div class="col-12 px-3 mt-2">
    <h5>メール送信確認</h5>
    <p>送信先: <strong>{{ $customer->email }}</strong></p>
    <p>納品書 No.{{ $note->note_number }}（{{ $note->period_start->format('Y/m/d') }} 〜 {{ $note->period_end->format('Y/m/d') }}）</p>
    <form action="{{ route('delivery.email_send', $customer) }}" method="POST">
        @csrf
        <input type="hidden" name="note_id" value="{{ $note->id }}">
        <div class="mb-3">
            <label class="form-label">メール本文</label>
            <textarea name="body" class="form-control" rows="10">{{ $defaultBody }}</textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">送信する</button>
            <a href="{{ route('delivery.notes', $customer) }}" class="btn btn-outline-secondary">キャンセル</a>
        </div>
    </form>
</div>
@endsection
