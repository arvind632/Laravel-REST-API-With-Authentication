<h3> Create REST API With Passport Authentication</h3>
<ol>
    <li> git clone git@github.com:arvind632/Laravel-REST-API-With-Authentication.git</li>
    <li> composer update</li>
    <li> php artisan migrate</li>
</ol>  


<h3> Creating a new project </h3>

<h3> Table of Contents </h3>
1. Creating a new project ( composer create-project --prefer-dist laravel/laravel blog 6 )
2. Install Package
We need to install Laravel Passport package via the composer dependency manager. Run the following command to require the package.
composer require laravel/passport
3. Adding Laravel Passport
In config/app.php file add the code .
a) 'providers' => [
    
    Laravel\Passport\PassportServiceProvider::class,
]


 Service Provider
Migration and Installation
b) php artisan migrate
c) php artisan passport:install
Passport Configure
d) app/User.php

<?php
 
namespace App;
 
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
 
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];
 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}

e) AuthServiceProvider
<?php
 
namespace App\Providers;
 
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
 
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];
 
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
 
        Passport::routes();
    }
}
f) config/auth.php
return [
    ....
 
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
 
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],
 
    ....
]
g) Create Route

Route::post('login', 'PassportController@login');
Route::post('register', 'PassportController@register');
 
Route::middleware('auth:api')->group(function () {
    Route::get('user', 'PassportController@details');
 
    Route::resource('products', 'ProductController');
});

h) Create Controller for Authentication
php artisan make:controller API/RegisterController

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
g) Create Product CRUD

<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Product;
use Validator;


class ProductController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {  
        $products = Product::all();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
        //return $this->sendResponse($products->toArray(), 'Products retrieved successfully.');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'detail' => 'required'
        ]);
 
        $input = $request->all();
        $product = Product::create($input);
 
        if ($product)
            return response()->json([
                'success' => true,
                'data' => $product->toArray()
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Product could not be added'
            ], 500);
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product with id ' . $id . ' not found'
            ], 400);
        }
 
        return response()->json([
            'success' => true,
            'data' => $product->toArray()
        ], 400);


        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }


        
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {



        
        $input = $request->all();


        $validator = Validator::make($input, [
            'name' => 'required',
            'detail' => 'required'
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }


        $product->name = $input['name'];
        $product->detail = $input['detail'];
        $product->save();


        return $this->sendResponse($product->toArray(), 'Product updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product with id ' . $id . ' not found'
            ], 400);
        }
 
        if ($product->delete()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product could not be deleted'
            ], 500);
        }


       
    }
}

Testing
php artisan serve
