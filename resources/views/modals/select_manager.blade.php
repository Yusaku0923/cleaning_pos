<!-- Modal -->
<div class="modal fade" id="manager-select-modal" tabindex="-1" role="dialog" aria-labelledby="manager-select-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manager-select-modal-label">Modal title</h5>
                <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h5">×</span>
                </button>
            </div>
            <form method="POST" action="{{ route('manager.update') }}" class="form-horizontal" autocomplete="off">
                {{ csrf_field() }}
                <div class="modal-body">
                    <div class="form-group">
                        @foreach($managers as $manager)
                        <div class="form-check mb-4">
                            <input class="form-check-input col-2" type="radio" name="id" value="{{ $manager->id }}" id="manager_{{ $manager->id }}">
                            <label class="form-check-label col-10" for="manager_{{ $manager->id }}">
                                <div class="card h3 p-3 text-right">{{ $manager->name }}</div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">閉じる</button>
                    <button type="submit" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>