@extends('layouts.app')

@section('content')
<clothes-mst-component
    :category_clothes="{{ json_encode($category_clothes) }}"
    :token="{{ json_encode(Utility::fetchApiToken()) }}"
></clothes-mst-component>
@endsection
