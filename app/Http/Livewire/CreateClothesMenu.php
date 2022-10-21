<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\Category;
use App\Models\Clothes;

use Illuminate\Support\Facades\Log;

class CreateClothesMenu extends Component
{
    public $category_id = NULL;

    public $posts;

    protected $rules = [
        'posts.name' => 'string',
        'posts.price' => 'number'
    ];

    public function render()
    {
        if (is_null($this->category_id)) {
            $category = '';
            $cards = Category::all();
        } else {
            $category = Category::where('id', $this->category_id)->value('name');
            $cards = Clothes::where('category_id', $this->category_id)->get();
        }

        return view('livewire.create-clothes-menu')->with([
            'category' => $category,
            'cards' => $cards,
        ]);
    }

    public function save()
    {
        if (is_null($this->category_id)) {
            Category::create([
                'store_id' => Auth::id(),
                'name' => $this->posts['name'],
            ]);
        } else {
            Clothes::create([
                'store_id' => Auth::id(),
                'category_id' => $this->category_id,
                'name' => $this->posts['name'],
                'price' => $this->posts['price'],
            ]);
        }

        $this->render();
    }

    public function select($id)
    {
        $this->category_id = $id;
    }

    public function back()
    {
        $this->category_id = NULL;
    }
}
