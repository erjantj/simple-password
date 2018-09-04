<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use JWTAuth;

use App\Models\User;

class UserController extends Controller
{


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->broker = 'user';
        $this->middleware('auth', ['only' => [
            'me', 
            'logout',
            'changePassword',
            'changeMasterPassword'
        ]]);

        Validator::extend('password', function($attribute, $value, $parameters) {
            return Hash::check($value, $parameters[0]);
        });
    }


    /**
     * @SWG\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="User login",
     *     description="User login",
     *     consumes={"application/json"},
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         description="User email",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         description="User password",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Autorization token",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         ref="$/responses/UnprocessableEntity"
     *     ),
     * )
     * 
     * User login
     * 
     * @param  Request  $request request
     * @return string   json response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if ($user && $user->checkPassword($password)) {
            try {
                $credentials = [
                    'email' => $email,
                    'password' => $password
                ];

                if ($token = JWTAuth::attempt($credentials)) {
                    $data = ['api_key' => $token];
                    return response()->json($data);       
                }
            } catch (JWTException $e) {
                abort(422, 'Failed to login, please try again');
            }
        }

        abort(422, trans('validation.invalid_auth_data'));
    }

    /**
     * @SWG\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="User logout",
     *     description="User logout",
     *     consumes={"application/json"},
     *     @SWG\Response(
     *         response="default",
     *         description="User logged out",
     *     ),
     *     @SWG\Response(
     *         response=403,
     *         ref="$/responses/Forbidden"
     *     ),
     * )
     * 
     * User logout
     * 
     * @param  Request  $request request
     * @return string   json response
     */
    public function logout(Request $request)
    {
        if (Auth::logout(true)) {
            return response()->json();
        }

        throw new \Exception('Problem logging out user');
    }

    /**
     * @SWG\Post(
     *     path="/change-password",
     *     tags={"Auth"},
     *     summary="Change user password",
     *     description="Change user password",
     *     consumes={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             ref="#/definitions/ChangePassword"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="Password changed",
     *     ),
     *     @SWG\Response(
     *         response=403,
     *         description="Forbidden",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *        ref="$/responses/RecordNotFound"
     *     ),
     *     security={{
     *         "apiKey": {}
     *     }}
     * )
     *
     * Authorized user data
     *
     * @param  Request  $request request
     * @return string   json response
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $this->validateChangePassword($request, $user->password_hash);

        $newPassword = $request->input('new_password');
        $user->password_hash = Hash::make($newPassword);
        
        if ($user->save()) 
        {
            $data = ['message' => trans('messages.password_changed')];
            return response()->json($data);
        }

        throw new \Exception(trans('messages.error_saving_user'));
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        return response()->json($user);
    }

    public function changeMasterPassword(Request $request)
    {
        $user = Auth::user();
        $this->validateChangePassword($request, $user->master_password);

        $newPassword = $request->input('new_password');
        $user->master_password = Hash::make($newPassword);
        
        if ($user->save()) 
        {
            $data = ['message' => trans('messages.password_changed')];
            return response()->json($data);
        }

        throw new \Exception(trans('messages.error_saving_user'));
    }

    private function validateLogin($request)
    {
        $this->validate($request, [
            'password' => 'required|max:255',
            'email' => 'required|max:255|email',
        ],[],
        [
            'email' => trans('messages.email'),
            'password' => trans('messages.password'),
        ]);
    }

    private function validateChangePassword($request, $oldPasswordHash)
    {
        $this->validate($request, [
            'old_password' => 'required|max:255|min:6|password:'.$oldPasswordHash,
            'new_password' => 'required|max:255|min:6|confirmed',
            'new_password_confirmation' => 'required|max:255|min:6'
        ],[],[
            'old_password' => trans('messages.current_password'),
            'new_password' => trans('messages.new_password'),
            'new_password_confirmation' => trans('messages.new_password_confirmation')
        ]);
    }
}
