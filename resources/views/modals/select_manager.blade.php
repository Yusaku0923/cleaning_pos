<!-- Modal -->
<div class="modal fade" id="manager-select-modal" tabindex="-1" role="dialog" aria-labelledby="manager-select-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border border-4 border-dark">
            <div class="modal-header manager-field">
                <h5 class="modal-title fs-26 fw-bold" id="manager-select-modal-label">担当者変更</h5>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    @foreach($managers as $manager)
                    <div class="card p-3 mb-3 fs-24 border border-3 border-primary manager-select-btn" data-manager-id={{ $manager->id }}>
                        {{ $manager->name }}
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fs-20" data-bs-dismiss="modal">閉じる</button>
                <a href="/manager/update/1" class="btn btn-primary fs-20">更新</a>
            </div>
        </div>
    </div>
</div>