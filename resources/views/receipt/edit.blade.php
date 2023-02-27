@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="card col-10 py-5 mx-auto">
        <form method="POST" action="{{ route('receipt.update') }}">
            @csrf
            @method('POST')
            <div class="ms-5 ps-5 fs-24 fw-bold mb-2">プリンターのローカルIP</div>
            <div class="col-12 form-group d-flex justify-content-center">
                <div class="col-2">
                    <input class="form-control fs-24" type="number" name="ip[0]" value="{{ $ip[0] }}" min="0" max="255">
                </div>
                <span class="fw-bold fs-24 mx-2" style="line-height: 52px;">.</span>
                <div class="col-2">
                    <input class="form-control fs-24" type="number" name="ip[1]" value="{{ $ip[1] }}" min="0" max="255">
                </div>
                <span class="fw-bold fs-24 mx-2" style="line-height: 52px;">.</span>
                <div class="col-2">
                    <input class="form-control fs-24" type="number" name="ip[2]" value="{{ $ip[2] }}" min="0" max="255">
                </div>
                <span class="fw-bold fs-24 mx-2" style="line-height: 52px;">.</span>
                <div class="col-2">
                    <input class="form-control fs-24" type="number" name="ip[3]" value="{{ $ip[3] }}" min="0" max="255">
                </div>
            </div>
            <div class="mt-5 col-11 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary fs-20 me-2">閉じる</button>
                <button type="submit" class="btn btn-success text-white fs-20 ms-2 me-5">更新</button>
            </div>
        </form>
    </div>
</div>
@endsection
