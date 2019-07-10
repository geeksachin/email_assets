<?php

namespace App\Http\Controllers;

use Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Post;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Socialite;
use Mail;

class HomeController  extends Controller
{

   

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $googleUser = Socialite::driver('google')->user();
        dd($googleUser);
        return view('front.homes.index');
    }

    /**
     * Forgot password
     *
     * @param  email
     * @return response
     */
    public function forgotPassword(Request $request)
    {
      $this->validation($request->all(), [
        "email" => "required",
      ]);

      $userinfo =  User::where('email',strtolower($request->input('email')))->first(); 

    // attempt to do the login
      if ($userinfo) {

        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $verify_token = sha1(substr(str_shuffle(str_repeat($pool, 8)), 0, 8));

        $userinfo->remember_token = (string) $verify_token;
        if($userinfo->save()){

          $pass_url = url('/auth/resetPass?user='.$userinfo->id.'&usertoken='.$verify_token);

          $send = Mail::send('emails.forgot_password', ['username' => ($userinfo->first_name) ? $userinfo->first_name : 'User', 'pass_url'=>$pass_url], function ($m) use ($userinfo) {
            $m->from('no-reply@example.com', 'My Project');

            $m->to($userinfo->email, $userinfo->first_name)->subject('My Project : Reset Password');
          });

          $this->success('Verification email has been sent. Please check your inbox.', '');
        } else {
          $this->error('Please try again.');
        }

      } else {        

        $this->error('Email does not exist!! Please enter valid email.');
      }
      
    }

    /**
     * Reset password by confirming email
     *
     * @return response
     */  
    public function resetPassword(Request $request) {

        $user = $request->input('user');
        $usertoken = $request->input('usertoken');

        if(!empty($user) && !empty($usertoken))
        {
            $check_token =  User::where(['id'=>$request->input('user'), 'remember_token'=>$request->input('usertoken')])->first();
            if ($check_token) {

                return view('emails.resetpass', compact('user', 'usertoken'));

            }else{
                return view('emails.expire_link');
            }
        }else{
            return view('emails.alert');
        }
    }


    /**
     * Save Reset password by confirming email
     *
     * @return response
     */ 
    public function saveResetPassword(Request $request)
    {
        $request->validate([
            "user" => "required",
            "usertoken" => "required",
        ]);


        $user = $request->input('user');
        $usertoken = $request->input('usertoken');
        $newPassword = password_hash($request->input('password'), PASSWORD_DEFAULT);

        $check_token =  User::where(['id'=>$user, 'remember_token'=>$usertoken])->first();
        if ($check_token) {

            $update =  User::where(['id'=>$user, 'remember_token'=>$usertoken])->update(['remember_token'=>'', 'password'=>$newPassword]);

            if($update){
                return view('emails.thankyou', compact('user', 'usertoken'));
            }else{
                Session::flash('message', 'Somthing went wrong, Please try again!');
                Session::flash('alert-class', 'alert-danger'); 
                return view('emails.resetpass', compact('user', 'usertoken'));
            }
        }else{

            return view('emails.alert');
        }
    }

    
}