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
        $request->session()->put('theme_header', $manager->theme_header);
        $request->session()->put('theme_body', $manager->theme_body);

        return redirect()->route('home');
    }
}
