<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;


class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */

    public $successStatus = 200;

    public function register(Request $request)
    {
        

        $validator = Validator::make($request->all(), [ 
            'name' => 'required', 
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'c_password' => 'required|same:password', 
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        $success['name'] =  $user->name;
        return response()->json(['success'=>$success], $this-> successStatus);



    }


    public function login(Request $request)
    {
      
        
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        

        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')-> accessToken;
            // $success['token'] =  $user->createToken('TutsForWeb')-> accessToken; 
            return response()->json(['success' => $success], $this-> successStatus); 
        } 
        else{ 
            return response()->json(['error'=>'Unauthorised'], 401); 
        } 

 
        
    }



    public function details()
    {
        $user = Auth::user(); 
        return response()->json(['success' => $user], $this-> successStatus);
    }

}