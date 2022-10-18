@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-10 mx-auto">
        <div class="card col-12 py-2 mt-2 h4 text-center">
            会　員　情　報　入　力
        </div>
        <div class="card p-5 ">
            <form method="POST" action="{{ route('customer.store') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="customer_name" class="h4">お名前</label>
                    <input type="text" class="form-control form-control-lg" name="name" id="customer_name" placeholder="お名前">
                </div>
                <div class="form-group mt-2">
                    <label for="customer_name_kana" class="h4">お名前(カナ)</label>
                    <input type="text" class="form-control form-control-lg" name="name_kana" id="customer_name_kana" placeholder="お名前(カナ)">
                </div>
                <div class="form-group mt-2 d-flex justify-content-between">
                    <div class="col-9">
                        <label for="customer_birth_day" class="h4">お誕生日</label>
                        <input type="date" class="form-control form-control-lg" name="birth_day" id="customer_birth_day" placeholder="お誕生日">
                    </div>
                    <div class="col-2">
                        <label for="customer_sex" class="h4">性別</label>
                        <select class="form-control form-control-lg" name="sex" id="customer_sex" placeholder="お誕生日">
                            <option value="">-</option>
                            <option value="0">男</option>
                            <option value="1">女</option>
                            <option value="2">その他</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mt-5">
                    <button type="submit">登録する</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
