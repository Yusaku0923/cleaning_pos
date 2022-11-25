@extends('layouts.app')

@section('content')

<history-component
    :customer="{{ json_encode($customer) }}"
></history-component>

@endsection
