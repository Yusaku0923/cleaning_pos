@extends('layouts.app')
@section('content')
<delivery-sp-entry-component
    :customers="{{ json_encode($customers) }}"
    :token="{{ json_encode(\App\Services\Utility::fetchApiToken()) }}"
></delivery-sp-entry-component>
@endsection
