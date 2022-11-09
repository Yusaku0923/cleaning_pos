@extends('layouts.app')

@section('content')
<div class="col-12 px-4">
    <div class="col-12 d-flex border-bottom border-2 mt-5 pb-2 dr-header">
        <div class="col-4 px-3 dr-earnings text-center">
            <span class="dr-earnings-label">売上： {{ number_format($daily_sum) }} 円</span>
        </div>
        <div class="col-4 dr-date text-center">
            <span>{{ $date === date('Y-m-d') ? '本日': date('Y年m月d日', strtotime($date)) }}の日報</span>
        </div>
        <div class="col-4">
            <daily-report-search-component
                :date="{{ json_encode($date) }}"
            ></daily-report-search-component>
        </div>
    </div>
</div>

@endsection

