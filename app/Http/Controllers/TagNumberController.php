<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TagNumber;

class TagNumberController extends Controller
{
    public function update(Request $request) {
        if (session()->exists('manager_id')) {
            $tag = $request->tag_head * 1000 + $request->tag_body;
            TagNumber::where('manager_id', session()->get('manager_id'))
                    ->update([
                        'tag_number' => $tag
                    ]);
        }
        return redirect()->route('home');
    }
}
