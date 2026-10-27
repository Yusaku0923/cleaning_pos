@extends('layouts.app')

@section('content')
<clothes-mst-component
    :category_clothes="{{ json_encode($category_clothes) }}"
></clothes-mst-component>
@endsection
