@extends('layouts.app')

@section('content')

<order-component :categories='{{ $list_json }}'></order-component>
@endsection
