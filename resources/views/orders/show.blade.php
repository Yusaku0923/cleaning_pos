@extends('layouts.app')

@section('content')

<history-component
    :customer="{{ json_encode($customer) }}"
    :orders="{{ json_encode($orders) }}"
></history-component>

@endsection
