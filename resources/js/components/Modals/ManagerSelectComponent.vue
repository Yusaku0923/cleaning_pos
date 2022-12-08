<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark">
                <div class="modal-content">
                    <div class="modal-header manager-field">
                        <h5 class="modal-title fs-26 fw-bold" id="manager-select-modal-label">担当者変更</h5>
                    </div>
                    <form method="POST" action="{{ route('manager.update') }}" class="form-horizontal" autocomplete="off">
                        <div class="modal-body">
                            <div class="form-group">
                                @foreach($managers as $manager)
                                <div class="form-check mb-4">
                                    <input class="form-check-input col-2" type="radio" name="id" value="{{ $manager->id }}" id="manager_{{ $manager->id }}">
                                    <label class="form-check-label col-10" for="manager_{{ $manager->id }}">
                                        <div class="card h3 p-3 text-right">{{ }}</div>
                                    </label>
                                </div>
                                @endforeach
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
    </transition>
</template>