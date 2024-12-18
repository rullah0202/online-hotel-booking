<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Mail\Websitemail;
use Auth;
use Hash;

class AdminLoginController extends Controller
{
    public function login(){

        return view('admin.login');
    }

    public function login_submit(Request $request){
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credential=[
            'email' =>$request->email,
            'password' =>$request->password
        ];

        if(Auth::guard('admin')->attempt($credential)){
            return redirect()->route('admin_home');
        }
        else{
            return redirect()->back()->with('error','Information is incorrect');
        }
    }

    public function logout(){
        Auth::guard('admin')->logout();
        return redirect()->route('admin_login');
    }

    public function forget_password(){
        return view('admin.forget_password');
    }

    public function reset_password(Request $request){

        $request->validate([
            'email' => 'required|email',
        ]);

        $admin_data = Admin::where('email',$request->email)->first();
        if(!$admin_data){
            return redirect()->route('admin_forget_password')->with('error','Email Not Found');
        }

            $token = hash('sha256',time());
            $admin_data->token = $token;
            $admin_data->update();

            $subject="Reset Password";
            $reset_link = url('admin/reset-password/'.$token .'/'.$request->email);
            $message = 'Please click on the following link: <br>';
            $message.= '<a href="'.$reset_link.'">Click here</a>';

            \Mail::to($request->email)->send(new Websitemail($subject,$message));

            return redirect()->route('admin_login')->with('success', 'Please check your email for reset password');


    }

    public function reset_password_verify($token,$email){

        $admin_data = Admin::where('token',$token)->where('email',$email)->first();
        if(!$admin_data) {
            return redirect()->route('admin_login');
        }
        return view('admin.reset_password',compact('token','email'));
    }

    public function reset_password_submit(Request $request,$token,$email){

        $request->validate([
            'password' => 'required',
            'retype_password' => 'required|same:password'
        ]);

        $admin_data = Admin::where('token',$token)->where('email',$email)->first();

        if(!$admin_data){
            return redirect()->route('admin_login')->with('error', 'Something went wrong');
        }
        $admin_data->password = Hash::make($request->password);
        $admin_data->token = '';
        $admin_data->update();

        return redirect()->route('admin_login')->with('success', 'Password reset successfully');
    }
}
