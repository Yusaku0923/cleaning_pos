<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Customer;

class SearchCustomers extends Component
{
    public $search = '';

    public function render()
    {
        $customers = [];
        if (!empty($this->search)) {
            $customers = Customer::where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')->orWhere('name_kana', 'like', '%'.$this->search.'%');
            })->where('manager_id', session()->get('manager_id'))
            ->get();
        }

        return view('livewire.search-customers', [
            'customers' => $customers,
        ]);
    }
}