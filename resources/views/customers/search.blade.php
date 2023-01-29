@extends('layouts.app')

@section('content')

<customer-search-component
    :token="{{ json_encode(Utility::fetchApiToken()) }}"
    :manager_id="{{ json_encode($manager_id) }}"
></customer-search-component>

@endsection