<?php

namespace App\Http\Controllers\API\v1;

use App\AuthKey;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Validation;
use Illuminate\Http\Response;

class AuthKeysController extends Controller
{

    public function index()
    {
        return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, "Unauthenticated");
    }

    /**
     * Register function
     *
     * To Register an external app to get data from Logistic
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        $param = ['name' => 'required'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $generateToken = bin2hex(openssl_random_pseudo_bytes(16));
            $user = AuthKey::create([
                'name' => $request->name,
                'token' => $generateToken
            ]);
            $response = response()->format(Response::HTTP_OK, true, ['auth_keys' => $user]);
        }
        return $response;
    }

    /**
     * Reset function
     *
     * To Reset an external app Key Token to get data from Logistic
     *
     * @param Request $request
     * @return void
     */
    public function reset(Request $request)
    {
        $param = [
            'name' => 'required',
            'token' => 'required',
            'retoken' => 'required'
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $generateToken = bin2hex(openssl_random_pseudo_bytes(16));
            $authKey = AuthKey::whereName($request->name)->whereToken($request->token)->update([
                'name' => $request->name,
                'token' => $generateToken
            ]);

            if (!$authKey) {
                $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, 'Data not Found!');
            } else {
                $authKeyData = [
                    'name' => $request->name,
                    'token' => $generateToken
                ];
                $response = response()->format(Response::HTTP_OK, true, ['auth_keys' => $authKeyData]);
            }
        }
        return $response;
    }
}
