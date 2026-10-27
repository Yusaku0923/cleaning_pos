@extends('layouts.app')
@section('content')
<delivery-sp-entry-component
    :customers="{{ json_encode($customers) }}"
></delivery-sp-entry-component>
@endsection
