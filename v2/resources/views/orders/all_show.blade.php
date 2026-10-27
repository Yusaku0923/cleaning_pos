@extends('layouts.app')

@section('content')

<all-history-component
    :info="{{ json_encode([]) }}"
></all-history-component>

@endsection
