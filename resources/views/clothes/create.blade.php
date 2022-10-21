@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-10 mx-auto">

        @livewire('create-clothes-menu')
        @livewireScripts

    </div>
</div>
@endsection
