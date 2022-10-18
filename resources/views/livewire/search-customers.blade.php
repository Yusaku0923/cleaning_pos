<div>
    <input wire:model="search" type="text" placeholder="Search users..."/>

    <ul>
        @foreach($customers as $customer)
            <li>{{ $customer->name }}</li>
        @endforeach
    </ul>
</div>