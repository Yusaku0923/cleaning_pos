@php
    $cutoff = range(0, 28);
    $cutoff[99] = '末';
    // array_shift($cutoff);
@endphp

<div class="modal fade" id="customer-edit-modal" tabindex="-1" role="dialog" aria-labelledby="customer-edit-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content mx-auto border border-4 border-dark">
            <div class="modal-header detail-field">
                <h5 class="modal-title fs-26 fw-bold" id="customer-edit-modal-label">顧客情報編集</h5>
                {{-- <button type="button" class="close btn cbtn-red" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h5">×</span>
                </button> --}}
            </div>
            <form method="POST" action="{{ route('customer.update', $customer->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="col-12 form-group mb-2">
                        <label for="name" class="form-label fs-20 fw-bold">名前</label>
                        <input type="text" id="name" class="form-control fs-24" name="name" value="{{ $customer->name }}">

                    </div>
                    <div class="col-12 form-group mb-2">
                        <label for="name_kana" class="form-label fs-20 fw-bold">名前(カナ)</label>
                        <input type="text" id="name_kana" class="form-control fs-24" name="name_kana" value="{{ $customer->name_kana }}">
                    </div>
                    <div class="col-12 form-group mb-2">
                        <label for="phone_number" class="form-label fs-20 fw-bold">電話番号</label>
                        <input type="tel" id="name_kana" class="form-control fs-24" name="phone_number" value="{{ $customer->phone_number }}">
                    </div>
                    @foreach ($errors->all() as $error)
                    <li>{{$error}}</li>
                    @endforeach
                    <hr>
                    <div class="col-12 form-group mb-2">
                        <label for="is_invoice" class="form-label fs-20 fw-bold">請求書払い</label>
                        <div class="col-8 d-flex fs-20">
                            <div class="col-6">
                                <input type="radio" id="is_invoice_true" class="form-check-input" name="is_invoice" value="1" {{ (boolean)$customer->is_invoice ? 'checked': '' }} />
                                <label for="is_invoice_true">する</label>
                            </div>
                            <div class="col-6">
                                <input type="radio" id="is_invoice_false" class="form-check-input" name="is_invoice" value="0" {{ (boolean)$customer->is_invoice ? '': 'checked' }} />
                                <label for="is_invoice_false">しない</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 form-group mb-2">
                        <label for="check_payment" class="form-label fs-20 fw-bold">入金確認</label>
                        <div class="col-8 d-flex fs-20">
                            <div class="col-6">
                                <input type="radio" id="check_payment_true" class="form-check-input" name="check_payment" value="1" {{ (boolean)$customer->needs_payment_confimation ? 'checked': '' }} />
                                <label for="check_payment_true">する</label>
                            </div>
                            <div class="col-6">
                                <input type="radio" id="check_payment_false" class="form-check-input" name="check_payment" value="0" {{ (boolean)$customer->needs_payment_confimation ? '': 'checked' }} />
                                <label for="check_payment_false">しない</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label for="cutoff_date" class="form-label fs-20 fw-bold">締め日</label>
                        <select id="cutoff_date" name="cutoff_date" class="form-select" aria-label="締め日">
                            @foreach ($cutoff as $key => $date)
                            @if ($key === 0)
                            <option value="{{ $key }}" disabled>日付を選択してください</option>
                            @else
                            <option value="{{ $key }}" {{ $customer->cutoff_date === $key ? 'selected': '' }}>{{ $date }}日</option>
                            @endif
                            @endforeach
                        </select>
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