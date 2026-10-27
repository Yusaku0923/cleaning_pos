@extends('layouts.app')

@section('content')
@php($posManager = request()->attributes->get('manager'))
<div class="col-12 text-end px-3 mb-2">
    <a href="{{ pos_url('delivery.index') }}" class="btn btn-outline-success btn-sm">📋 納品書管理</a>
</div>

{{-- 担当者・テーマは URL (?m=) の担当者から (旧はセッションの manager_id / theme_*)。API はセッション認証なので token は渡さない --}}
<invoice-component
    :manager_id="{{ json_encode($posManager->id) }}"
    :invoices="{{ json_encode($invoices) }}"
    :theme="{{ json_encode($posManager->theme_header ? $posManager->theme_header.'-header' : ($posManager->theme_body ? '-header' : '')) }}"
></invoice-component>

@endsection
