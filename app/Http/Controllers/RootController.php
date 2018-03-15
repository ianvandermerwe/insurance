<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RootController extends Controller
{
    public function index() {
        echo env('INSURANCE_API_KEY');
    }
}
