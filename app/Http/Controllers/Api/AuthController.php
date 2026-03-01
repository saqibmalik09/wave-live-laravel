<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function simplelogin(Request $request)
    {
        // this method will be used for email and password login if user is registered with email and password not with google or facebook or apple login
        $credentials = $request->only('email', 'password');

        // if user is not found return error not invalid credentials error 
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return ApiResponse::error('Invalid credentials', 200);
        }
        if ($user && !$user->hasVerifiedEmail()) {
            return ApiResponse::error('Please verify your email first', 200);
        }
        if ($user->status === 'email_banned') {
            return ApiResponse::error('Account is banned due to email violation. please contact support', 200);
        }
        if ($user->status == 'deleted') {
            // return ApiResponse::error("User account not exists. please register", 200);
            // if account status deleted and deleted at time is withing 30 days contiue login and update status to active and remove deleted at time and return login successful response so that user can get access to account and if user want to register again with same email then we can restore account for them
            if ($user->deleted_at && $user->deleted_at->diffInDays(now()) < 30) {
                $user->status = 'active';
                $user->deleted_at = null;
                $user->save();
            } else {
                return ApiResponse::error("User account not exists. please register", 200);
            }
        }
        if ($user->status == 'device_banned') {
            return ApiResponse::error("  Device is banned. please contact support", 200);
        }
        // if (!$user) {
        //     return ApiResponse::error('User account does not exist', 200);
        // } removed because security reasons we should not reveal that user account does not exist or password is incorrect we should return same error message for both cases
        // check user registered with email and password or with google or facebook or apple login if user registered with google or facebook or apple login then return error that user is registered with google or facebook or apple login and ask user to login with google or facebook or apple login
        if ($user->auth_provider !== 'email_and_password') {
            // remove any _ from auth provider name and make it more readable
            $authProvider = str_replace('_', ' ', $user->auth_provider);
            return ApiResponse::error('User is registered with ' . $authProvider . ' login. Please login using your ' . $authProvider . ' account.', 200);
        }


        //if user exists then check credentials
        if (!$token = auth('api')->attempt($credentials)) {
            return ApiResponse::error('Invalid credentials', 200);
        }
        $user = auth('api')->user();

        return ApiResponse::success([
            'token' => $token,
            'user' => $user,
            'expires_in' => (int) auth('api')->factory()->getTTL()." Minutes"
        ], 'Login successful');
    }
    public function simpleregister(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            // must start with + and contain 1-3 digits
            'country_id' => 'nullable|numeric|min:1',  
            'contact_number' => 'nullable|numeric|digits_between:7,15',
        ], [
            'country_id.numeric' => 'The country id must be a number.',
            'country_id.min' => 'The country id must be at least 1.',
            'contact_number.digits_between' => 'The contact number must be between 7 and 15 digits.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 200);
        }
        try {
            DB::beginTransaction();
            $public_id = rand(10000, 99999);

            // Check if it exists, if so, keep generating until unique
            while (User::where('public_id', $public_id)->exists()) {
                $public_id = rand(10000, 99999);
            }
            $user = User::create([
                'public_id' => $public_id,
                'name' => $request->name,
                'email' => $request->email, // normalize
                'password' => Hash::make($request->password),
                'auth_provider' => 'email_and_password',
                'country_code' => $request->country_code,
                'contact_number' => $request->contact_number,
            ]);

            $token = auth('api')->login($user);

            DB::commit();

            return ApiResponse::success([
                'token' => $token,
                'user'=>$user,
                'expires_in' => (int) auth('api')->factory()->getTTL()." Minutes"
            ], 'Registration successful');
        } catch (\Illuminate\Database\QueryException $e) {

            DB::rollBack();

            return ApiResponse::error($e->getMessage(), 409);
        }
    }

    public function logout()
    {
        auth('api')->logout();

        return ApiResponse::success([], 'Logged out successfully');
    }

    public function userProfile()
    {
        return ApiResponse::success(auth('api')->user());
    }
    //delete user account with all related data like sessions and password reset tokens but we will not delete user record from database we will just update status to deleted and remove email and contact number and country code and profile photo and about and set name to deleted user and set nick name to null and set coins and beans to 0 so that we can keep record of user for analytics and also we can restore user account if user want to register again with same email or contact number in future
    public function deleteAccount()
    {
        $user = auth('api')->user();

        $user->status = 'deleted';
        $user->deleted_at = now();
        $user->save();
        // logout session

        return ApiResponse::success([], 'Request submitted. Your account and associated data will be deleted within 30 days. Meantime you can get your account at anytime');
    }
}
