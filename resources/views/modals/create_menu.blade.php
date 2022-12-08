<!-- Modal -->
<div class="modal fade" id="menu-create-modal" tabindex="-1" role="dialog" aria-labelledby="menu-create-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menu-create-modal-label">{{ true ? 'カテゴリー': '衣類' }}情報入力</h5>
                {{-- <button type="button" class="close btn btn-danger" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h5">×</span>
                </button> --}}
            </div>
            <form wire:submit.prevent="save">
                <div class="modal-body">
                    <div class="form-group col-12 mx-auto mb-2">
                        <label for="posts_name" class="col-form-label">{{ true ? 'カテゴリー': '衣類' }}名</label>
                        <input type="text" class="form-control form-control-lg" wire:model.defer="posts.name" id="posts_name" placeholder=""/>
                    </div>
                    @if (!true)
                    <div class="form-group col-12 mx-auto">
                        <label for="posts_price" class="col-form-label">料金</label>
                        <input type="number" class="form-control form-control-lg" wire:model.defer="posts.price" id="posts_price" placeholder=""/>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>