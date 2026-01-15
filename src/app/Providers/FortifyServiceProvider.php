<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Http\Responses\CustomRegisterResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\CustomLoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\CustomLogoutResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use App\Http\Responses\CustomVerifyEmailResponse;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if(request()->is('admin/*')){
            config(['fortify.guard' => 'admin']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);

        Fortify::registerView(function(){
            return view('auth.register');
        });

        Fortify::loginView(function(){
            return view('auth.login');
        });

        if(request()->is('admin/*')){
            Fortify::loginView(function(){
                return view('auth.admin_login');
            });
        }

        RateLimiter::for('login',function(Request $request){
            $email = (string)$request->email;
            return Limit::perMinute(10)->by($email.$request->ip());
        });

        $this->app->bind(FortifyLoginRequest::class,LoginRequest::class);

        Fortify::verifyEmailView( function (){
            return view('auth.verify_email');
        });

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)){

                if (!$user->hasVerifiedEmail()) {
                    return null;
                }
                return $user;
            }
        });

        $this->app->singleton(
            RegisterResponseContract::class,
            CustomRegisterResponse::class
        );

        $this->app->singleton(
            LoginResponseContract::class,
            CustomLoginResponse::class
        );

        $this->app->singleton(
            LogoutResponseContract::class,
            CustomLogoutResponse::class
        );

        $this->app->singleton(
            VerifyEmailResponseContract::class,
            CustomVerifyEmailResponse::class
        );
    }
}
