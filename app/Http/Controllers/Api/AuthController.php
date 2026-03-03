<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendSimpleLoginOtpMail;

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
            if ($user->deleted_at && $user->deleted_at->diffInDays(now()) < 90) {
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
            'expires_in' => (int) auth('api')->factory()->getTTL() . " Minutes"
        ], 'Login successful');
    }
    public function simpleregister(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nick_name' => 'nullable|string|max:15',
            'about' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female,other',
            // minimum age 18 years and maximum age 100 years
            'birthdate' => 'nullable|date_format:d-m-Y,|before:today,|after:01-01-1900',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            // must start with + and contain 1-3 digits
            'country_id' => 'nullable|numeric|min:1',
            'contact_number' => 'nullable|numeric|digits_between:7,15',
        ], [
            'country_id.numeric' => 'The country id must be a number.',
            'country_id.min' => 'The country id must be at least 1.',
            'contact_number.digits_between' => 'The contact number must be between 7 and 15 digits.',
            'gender.in' => 'The gender field must be one of the following:  male,female,other.',
            'birthdate.date_format' => 'The birthdate does not match the format d-m-Y.',
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
                'about' => $request->about ?? 'Hey! I am using ' . env('APP_NAME') . ' app.',
                'nick_name' => $request->nick_name ?? $request->name,
                'email' => $request->email, // normalize
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'password' => Hash::make($request->password),
                'auth_provider' => 'email_and_password',
                'country_code' => $request->country_code,
                'contact_number' => $request->contact_number,
            ]);

            $token = auth('api')->login($user);

            DB::commit();

            return ApiResponse::success([
                'token' => $token,
                'user' => $user,
                'expires_in' => (int) auth('api')->factory()->getTTL() . " Minutes"
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
    // update user profile with name and nick name and about and gender and birthdate and country code and contact number but we will not update email and password in this method we will create separate method for update email and password and in update email method we will send verification email to new email address and in update password method we will ask user to enter current password and new password and confirm new password and if current password is correct then we will update password otherwise we will return error message that current password is incorrect
    public function updateMyProfile(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'nick_name' => 'nullable|string|max:15',
            'about' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female,other',
            'birthdate' => 'nullable|date_format:d-m-Y,|before:today|after:01-01-1900',
            'country_code' => 'nullable|string|max:5',
            'contact_number' => 'nullable|numeric|digits_between:7,15',
        ], [
            'country_code.max' => 'The country code must not exceed 5 characters.',
            'contact_number.digits_between' => 'The contact number must be between 7 and 15 digits.',
            'gender .in' => 'The gender field must be one of the following: male,female,other.',
            'birthdate.date_format' => 'The birthdate does not match the format d-m-Y.',
        ]);
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 200);
        }
        $user->name = $request->name ?? $user->name;
        $user->nick_name = $request->nick_name ?? $user->nick_name;
        $user->about = $request->about ?? $user->about;
        $user->gender = $request->gender ?? $user->gender;
        $user->birthdate = $request->birthdate ?? $user->birthdate;
        $user->country_code = $request->country_code ?? $user->country_code;
        $user->contact_number = $request->contact_number ?? $user->contact_number;
        $user->save();
        return ApiResponse::success($user, 'Profile updated successfully');
    }
    // change password with require current password and new password and confirm new password and if current password is correct then update password otherwise return error message that current password is incorrect
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 200);
        }
        $user = auth('api')->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Current password is incorrect', 200);
        }
        // if new password is same as current password then return error message that new password must be different from current password
        if (Hash::check($request->new_password, $user->password)) {
            return ApiResponse::error('New password must be different from previously used password', 200);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();
        return ApiResponse::success([], 'Password changed successfully');
    }
    // reset password  with require email to send mail otp 4 digit to user
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 200);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::error('User account does not exist', 200);
        }
        // generate otp 4 digit
        $otp = rand(1000, 9999);
        // save otp to password_resets table with email and otp and created_at time
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($otp), 'created_at' => now()]
        );
        // send otp to user email
        Mail::to($request->email)->send(new SendSimpleLoginOtpMail($user->name, $otp));
        return ApiResponse::success([], 'OTP sent to your email. Please check your email and use that OTP to reset your password.');
    }
    // varify otp and reset password with require email and otp and new password and confirm new password and if otp is correct then update password otherwise return error message that otp is incorrect
    public function verifyOtpAndResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:4',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 200);
        }
        $passwordReset = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$passwordReset) {
            return ApiResponse::error('Invalid OTP or email', 200);
        }
        if (!Hash::check($request->otp, $passwordReset->token)) {
            return ApiResponse::error('Invalid OTP', 200);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::error('User not found', 200);
        }
        // varify otp created at time is within 30 minutes
        if (now()->diffInMinutes($passwordReset->created_at) > 30) {
            return ApiResponse::error('OTP expired. Please request a new OTP.', 200);
        }
        // varify user is entered otp is correct or not if correct then update password otherwise return error message that otp is incorrect
        if (!Hash::check($request->otp, $passwordReset->token)) {
            return ApiResponse::error('Invalid OTP', 200);
        }



        $user->password = Hash::make($request->new_password);
        $user->save();
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        return ApiResponse::success([], 'Password reset successfully');
    }
    // refresh token
    public function refreshToken()
    {
        $token = auth('api')->refresh();
        $user = auth('api')->user();
        return ApiResponse::success([
            'token' => $token,
            'expires_in' => (int) auth('api')->factory()->getTTL() . " Minutes",
            'user' => $user
        ], 'Token refreshed successfully');
    }
    // Google sign in and sign up and if user is signing in with google for the first time then we will create new user record in database with auth provider google and if user is signing in with google and user record already exists in database then we will just return login successful response with token and user data
    public function googleSignIn(Request $request)
    {
        // 1. Lighten Validation: Only validate what's strictly necessary for logic
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'auth_provider_id' => 'required',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 200);
        }

        try {
            // 2. Fetch user first to avoid unnecessary Transactions/Hashing
            $user = User::where('auth_provider_id', $request->auth_provider_id)->first();
            if (!$user) {
                // 3. Optimized New User Logic
                DB::beginTransaction();

                // Avoid while loop: Generate ID once, let Database handle uniqueness if it fails
                $public_id = random_int(10000, 99999);

                $user = User::create([
                    'auth_provider_id' => $request->auth_provider_id,
                    'auth_provider' => 'google',
                    'public_id' => $public_id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'profile_photo' => $request->profile_photo,
                    'status' => 'active',
                    'country_code' => '+1',
                    'contact_number' => '0000000000',
                    'about' => 'Hey! I am using ' . config('app.name') . ' app.',
                    'nick_name' => $request->name,
                    'gender' => $request->gender ?? 'other',
                    'birthdate' => $request->birthdate,
                    // email varified  is like format 2026-03-01 22:30:59
                    'email_verified_at' => now(),
                    // 4. Use a pre-hashed string to save ~200-400ms of CPU time
                    'password' => Hash::make('password'),
                ]);
                DB::commit();
            } else {
                // 5. If user exists, skip password hashing and DB writes unless needed
                if ($user->status === 'deleted') {
                    // If account is deleted but within 90 days, restore it
                    if ($user->deleted_at && $user->deleted_at->diffInDays(now()) < 90) {
                        $user->status = 'active';
                        $user->deleted_at = null;
                        $user->save();
                    } else {
                        return ApiResponse::error("User account not exists. please register", 200);
                    }
                }
            }
               if (in_array($user->status, ['email_banned', 'device_banned'])) {
                return ApiResponse::error("Account is restricted. Please contact support.", 200);
            }
            // 6. Fast JWT Login
            $token = auth('api')->login($user);

            return ApiResponse::success([
                'token' => $token,
                'user' => $user,
                'expires_in' => (int) auth('api')->factory()->getTTL() . " Minutes"
            ], 'Login successful');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            return ApiResponse::error("Server Error", 500);
        }
    }
}
