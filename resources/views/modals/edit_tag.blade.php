<div class="modal fade" id="tag-edit-modal" tabindex="-1" role="dialog" aria-labelledby="tag-edit-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border border-4 border-dark">
            <div class="modal-header tag-field">
                <h5 class="modal-title fs-26 fw-bold" id="tag-edit-modal-label">タグ番号更新</h5>
                {{-- <button type="button" class="close btn cbtn-red" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h5">×</span>
                </button> --}}
            </div>
            <form method="POST" action="{{ route('tag.update') }}">
                @csrf
                <div class="modal-body">
                    <div class="col-12 h4">
                        次に発行されるタグ: {{ Utility::convertTagFormat($tag) }}
                    </div>
                    <hr>
                    <div class="form-group col-12 mx-auto mt-2">
                        <div class="col-6 d-flex mx-auto">
                            <input type="number" class="form-control form-control-lg" name="tag_head" id="tag-head" min="1" max="10"/>
                            <span id="tag-bar">-</span>
                            <input type="number" class="form-control form-control-lg col-8" name="tag_body" id="tag-body" min="0" max="999"/>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary fs-20" data-bs-dismiss="modal">閉じる</button>
                    <button type="submit" class="btn btn-primary fs-20">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>