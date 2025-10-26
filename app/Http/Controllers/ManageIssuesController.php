<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageIssuesController extends Controller
{
    /**
     * Display the manage issues page
     */
    public function index()
    {
        $user = Auth::user();

        return view('manage-issues.index');
    }
}
