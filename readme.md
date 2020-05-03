<h3> Create REST API With Passport Authentication</h3>
<ol>
    <li> git clone git@github.com:arvind632/Laravel-REST-API-With-Authentication.git</li>
    <li> composer update</li>
    <li> setup DB and .env file</li>
    <li> php artisan migrate</li>
</ol>  


<h3> Creating a new project </h3>

<h3> Table of Contents </h3>
<h3> 1. Creating a new project 
 <b>  composer create-project --prefer-dist laravel/laravel blog 6 </b>
<h3>2. Install Package</h3>
We need to install Laravel Passport package via the composer dependency manager. Run the following command to require the package.
composer require laravel/passport
<h3>3. Adding Laravel Passport</h3>
<b>In config/app.php file add the code .</b>
<b>
a) 'providers' => [
    
    Laravel\Passport\PassportServiceProvider::class,
]
</b>

<br>
 Service Provider
Migration and Installation
<br>
b) php artisan migrate
<br> c) php artisan passport:install
<br> Passport Configure
<br> d) app/User.php

<br> <?php
 
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

<br> e) AuthServiceProvider
<br><br> <?php
 
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
<br><br>
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
<br><br>
g) Create Route

Route::post('login', 'PassportController@login');
Route::post('register', 'PassportController@register');
 
Route::middleware('auth:api')->group(function () {
    Route::get('user', 'PassportController@details');
 
    Route::resource('products', 'ProductController');
});
<br><br>
h) Create Controller for Authentication
php artisan make:controller API/RegisterController




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

<br><br><br>
g) Create Product CRUD


namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Product;
use Validator;


class ProductController extends BaseController
{
   
    public function index()
    {  
        $products = Product::all();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
        
    }


    

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
<br><br>
Testing
php artisan serve
