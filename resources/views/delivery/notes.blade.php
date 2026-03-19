@extends('layouts.app')
@section('content')
<delivery-note-component
    :customer="{{ json_encode($customer->load('departments.products')) }}"
    :token="{{ json_encode(\App\Services\Utility::fetchApiToken()) }}"
></delivery-note-component>
@endsection
