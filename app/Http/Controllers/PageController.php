<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function contact()
    {
        return view('pages.contact');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function faqs()
    {
        return view('pages.faqs');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function refund()
    {
        return view('pages.refund');
    }

    public function partners()
    {
        return view('pages.partners');
    }
}
