<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Manager;

class ManagerController extends Controller
{
    public function update(Request $request) {
        $manager = Manager::find($request->id);
        $request->session()->put('manager_id', $manager->id);
        $request->session()->put('manager_name', $manager->name);

        return redirect()->route('home');
    }
}
