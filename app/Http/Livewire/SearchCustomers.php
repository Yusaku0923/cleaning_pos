<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Customer;

class SearchCustomers extends Component
{
    public $search = '';

    public function render()
    {
        return view('livewire.search-customers', [
            'customers' => Customer::where('name', 'like', '%'.$this->search.'%')->get(),
        ]);
    }
}