<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class WelcomeController extends Controller
{
    public function index()
    {
        return response()->format(Response::HTTP_OK, "Welcome to API version 1");
    }
}
