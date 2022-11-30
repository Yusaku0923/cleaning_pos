<div class="modal fade" id="order-cancel-modal" tabindex="-1" role="dialog" aria-labelledby="order-cancel-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h3 fw-bold" id="order-cancel-modal-label">直前預り取り消し</h5>
                <button type="button" class="close btn btn-danger" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h5">×</span>
                </button>
            </div>
            <form method="POST" action="{{ route('order.delete', $latest_order['id']) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="col-12 h4">
                        以下の預り内容を削除します。
                    </div>
                    <hr>
                    <div class="col-11 mx-auto mt-2" style="font-size: 20px;">
                        <div class="col-12">伝票番号: <span class="fw-bold">{{ $latest_order['id'] }}</span></div>
                        <div class="col-12">金額: <span class="fw-bold">{{ number_format($latest_order['amount']) }}円</span></div>
                        <div class="col-12">内訳</div>
                        <hr class="my-2">
                        <div class="col-12 d-flex">
                            <div class="col-2">タグ</div>
                            <div class="col-7">商品名</div>
                            <div class="col-3">金額</div>
                        </div>
                        <div class="col-12 overflow-scroll border" style="max-height: 300px">
                            @foreach ($latest_order['items'] as $item)
                            <div class="col-12 d-flex">
                                <div class="col-2 fw-bold">{{ $item['tag'] }}</div>
                                <div class="col-7 fw-bold">{{ $item['name'] }}</div>
                                <div class="col-3 fw-bold">{{ number_format($item['price']) }}円</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary fs-20" data-bs-dismiss="modal">閉じる</button>
                    <button type="submit" class="btn btn-danger fs-20">削除</button>
                </div>
            </form>
        </div>
    </div>
</div>