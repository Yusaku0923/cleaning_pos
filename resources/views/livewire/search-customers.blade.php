<div class="card col-12 py-4">
    <div class="form-group col-6 mx-auto">
        <input wire:model="search" type="text" class="form-control form-control-lg" placeholder="名前、フリガナ等"/>
    </div>

    <div class="col-12">
        @foreach($customers as $customer)
            {{-- <li>{{ $customer->name }}</li> --}}
            <a href="{{ route('customer.select', $customer->id) }}" class="card col-8 py-4 my-2 mx-auto text-center cbtn">
                {{ $customer->name }}
            </a>
        @endforeach
    </div>

</div>