@extends('layouts.app')

@section('content')

<div class="col-12">
    <div class="col-10 mx-auto">
        <div class="card col-12 py-3 mb-4 h4 text-center">
            顧　客　検　索
        </div>
        @livewire('search-customers')
        @livewireScripts
    </div>
</div>

@endsection