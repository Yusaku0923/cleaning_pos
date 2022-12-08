<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Clothes;

class ClothesController extends Controller
{
    public $category_id = NULL;

    public $posts;

    protected $rules = [
        'posts.name' => 'string',
        'posts.price' => 'number'
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (is_null($this->category_id)) {
            $category = '';
            $cards = Category::all();
        } else {
            $category = Category::where('id', $this->category_id)->value('name');
            $cards = Clothes::where('category_id', $this->category_id)->get();
        }

        // return view('livewire.create-clothes-menu')->with([
        // ]);
        return view('clothes.create')->with([
            'title' => '商　品　マ　ス　タ',
            // 'category' => $category,
            'cards' => $cards,
            // 'category_id' => 
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
