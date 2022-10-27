@extends('layouts.app')

@section('content')

<order-component 
    :categories='{{ $list_json }}'
    {{-- :route='{{ route('home') }}' --}}
></order-component>
@endsection
