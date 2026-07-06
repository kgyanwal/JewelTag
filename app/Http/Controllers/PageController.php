<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function privacy()
    {
        return view('privacy');
    }

    public function documentation()
    {
        return view('docs');
    }

    // This is the method Laravel couldn't find!
    public function apiReference()
    {
        return view('api');
    }
    public function about()
{
    return view('about');
}
public function contact()
{
    return view('contact');
}
}