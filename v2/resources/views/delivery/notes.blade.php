@extends('layouts.app')
@section('content')
<delivery-note-component
    :customer="{{ json_encode($customer->load('departments.products')) }}"
></delivery-note-component>
@endsection
