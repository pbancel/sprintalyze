<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageInstancesController extends Controller
{
    /**
     * Display the manage instances page
     */
    public function index()
    {
        $user = Auth::user();

        return view('manage-instances.index');
    }
}
