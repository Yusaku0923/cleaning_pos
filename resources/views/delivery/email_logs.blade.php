@extends('layouts.app')
@section('content')
<div class="col-12 px-3 mt-2">
    <h5>メール送信履歴</h5>
    @if(session('message'))
        <div class="alert alert-info">{{ session('message') }}</div>
    @endif
    <table class="table table-bordered mt-3">
        <thead class="table-light">
            <tr><th>送信日時</th><th>顧客</th><th>期間</th><th>送信先</th><th>No.</th><th>状態</th></tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
        <tr>
            <td>{{ $log->sent_at->format('Y/m/d H:i') }}</td>
            <td>{{ $log->customer->name }}</td>
            <td>{{ $log->period_start }} 〜 {{ $log->period_end }}</td>
            <td>{{ $log->sent_to }}</td>
            <td>No.{{ $log->note->note_number }}</td>
            <td>{{ $log->status === 'success' ? '✅ 成功' : '❌ 失敗' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
    <a href="{{ route('delivery.index') }}" class="btn btn-secondary mt-2">戻る</a>
</div>
@endsection
