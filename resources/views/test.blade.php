@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-12 mx-auto">
        <div class="clothes-bar col-5 border border-secondary position-relative" style="height: 50dvh">
            <div class="card border border-secondary clothes-card position-relative p-2 mt-3">
                <div class="text-nowrap overflow-auto">
                    Yシャツ
                </div>
                <div class="position-absolute bottom-0 text-primary">
                    100 円
                </div>
                <div class="position-absolute odcreate-clothes-tag odcreate-clothes-tag-one">
                    <i class="fa-solid fa-tag"></i>
                </div>
            </div>

            <div class="card border border-secondary clothes-card position-relative p-2">
                <div class="text-nowrap overflow-auto">
                    Yシャツ
                </div>
                <div class="position-absolute bottom-0 text-primary">
                    100 円
                </div>
                <div class="position-absolute odcreate-clothes-tag odcreate-clothes-tag-two">
                    <i class="fa-solid fa-tag"></i>
                </div>
            </div>

            <div class="card border border-secondary clothes-card position-relative p-2">
                <div class="text-nowrap overflow-auto">
                    Yシャツ
                </div>
                <div class="position-absolute bottom-0 text-primary">
                    100 円
                </div>
                <div class="position-absolute odcreate-clothes-tag odcreate-clothes-tag-more">
                    <i class="fa-solid fa-tag"></i>
                </div>
            </div>

            <div class="col-12 card position-absolute bottom-0">
                <div class="col-12 d-flex fs-18">
                    <div class="col-3 fw-bold">
                        タグ枚数
                    </div>
                    <div class="col-3 odcreate-clothes-tag odcreate-clothes-tag-one">
                        <i class="fa-solid fa-tag"></i><span class="ps-1">:１枚</span>
                    </div>
                    <div class="col-3 odcreate-clothes-tag odcreate-clothes-tag-two">
                        <i class="fa-solid fa-tag"></i><span class="ps-1">:２枚</span>
                    </div>
                    <div class="col-3 odcreate-clothes-tag odcreate-clothes-tag-more">
                        <i class="fa-solid fa-tag"></i><span class="ps-1">:３枚以上</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
