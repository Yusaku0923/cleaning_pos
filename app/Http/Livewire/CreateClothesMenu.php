<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Category;
use App\Models\Clothes;

class CreateClothesMenu extends Component
{
    public $category_id = NULL;

    public function render()
    {
        if (is_null($this->category_id)) {
            $category = '';
            $cards = Category::all();
        } else {
            $category = Category::where('id', $this->category_id)->value('category');
            $cards = Clothes::where('category_id', $this->category_id)->get();
        }

        return view('livewire.create-clothes-menu')->with([
            'category' => $category,
            'cards' => $cards,
        ]);
    }

    public function select($id)
    {
        $this->category_id = $id;

        $this->render();
    }
}
