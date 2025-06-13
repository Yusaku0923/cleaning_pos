@extends('layouts.app')

@section('content')
<div class="col-12 p-4">
    <div class="col-12 mb-3">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
    </div>
    
    <div class="col-12">
        @if(count($customers) > 0)
            @foreach($customers as $customer)
                <div class="card col-12 mb-2 p-3">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="fs-20 fw-bold">{{ $customer->name }}</div>
                            <div class="fs-16 text-muted">{{ $customer->name_kana }}</div>
                        </div>
                        <div class="col-4 text-end">
                            <a href="{{ route('customer.select', $customer->id) }}" 
                               class="btn btn-primary me-2 fs-16">選択</a>
                            <button type="button" 
                                    class="btn btn-danger fs-16"
                                    onclick="confirmDelete({{ $customer->id }}, '{{ $customer->name }}')">削除</button>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="card col-12 p-4 text-center">
                <div class="fs-18 text-muted">登録されている顧客がありません。</div>
            </div>
        @endif
    </div>
    
    <div class="col-12 mt-4 text-center">
        <a href="{{ route('home') }}" class="btn btn-secondary fs-18 px-4">戻る</a>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">削除確認</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(customerId, customerName) {
    document.getElementById('deleteMessage').textContent = 
        `「${customerName}」を削除してもよろしいですか？`;
    document.getElementById('deleteForm').action = 
        `/customer/delete/${customerId}`;
    
    // jQueryを使ってモーダルを表示
    $('#deleteModal').modal('show');
}
</script>
@endsection 