<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeEmail;
use Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();

        return response()->json([
            'status' => 200,
            'message' => 'OK',
            'data' => $users
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'mobile' => ['required', 'numeric'],
            'address' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
                'status' => 400,
                'message' => 'Validation error'
            ]);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->address = $request->address;
        $user->password = Hash::make($request->password);
        $user->save();

        Mail::to($user->email)->send(new WelcomeEmail($user));

        return response()->json([
            'status' => 200,
            'message' => 'OK! User Created'
        ]);

    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                'message' => 'User not found',
                'status' => 0,
            ];
        }else{
            $response = [
                'message' => 'User found',
                'status' => 1,
                'data' => $user
            ];
        }
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $user = User::find($id);
        if(is_null($user)){
            return response()->json([
                'status' => 0,
                'message' => 'User does not exits'
            ], 400);
        }else{
            DB::beginTransaction();
            try{
                $user ->name = $request['name'];
                $user ->email = $request['email'];
                $user ->mobile = $request['mobile'];
                $user ->address = $request['address'];
                $user->save();
                DB::commit();
            }catch(\Exception $err){
                DB::rollback();
                $user = null;
            }

            if(is_null($user)){
                return response()->json([
                    'status' => 500,
                    'message' => 'Internal Server Error',
                    'error_msg' => $err->getMessage()
                ]);
            }else{
                return response()->json([
                    'status' => 200,
                    'message' => 'Data Updated Succesfully'
                ] );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            $response = [
                'message' => 'User does not exists',
                'status' => 404
            ];
        }else{
            DB::beginTransaction();
            try{
                $user->delete();
                DB::commit();
                $response = [
                    'message' => 'User deleted succesfully',
                    'status' => 200
                ];
            }catch(\Exception $err){
                DB::rollback();
                $response = [
                    'message' => 'Internal server error',
                    'status' => 500
                ];
            }
            return response()->json($response);
        }
    }
}
