<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\IncomingLetter;
use Illuminate\Http\Response;

class IncomingLetterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = IncomingLetter::getIncomingLetterList($request);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }
}
