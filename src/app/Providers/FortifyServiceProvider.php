<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequestBase;
use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fortifyが使うLoginRequestを、あなたのLoginRequestに差し替える
        $this->app->bind(FortifyLoginRequestBase::class, LoginRequest::class);

        // ★追加：ログイン成功後の遷移（LoginResponse）を差し替える
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::verifyEmailView(function () {
            session(['url.intended' => url('/attendance')]); // ←追加
            return view('auth.verify-notice');
        });
        Fortify::registerView(fn() => view('auth.register'));
        Fortify::loginView(fn() => view('auth.login'));

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                // 管理者ログイン画面からのログインなら、管理者権限も必須
                if ($request->boolean('admin_login') && !$user->is_admin) {
                    throw ValidationException::withMessages([
                        'login' => ['ログイン情報が登録されていません'],
                    ]);
                }

                return $user;
            }

            throw ValidationException::withMessages([
                'login' => ['ログイン情報が登録されていません'],
            ]);
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return Limit::perMinute(10)->by($email . '|' . $request->ip());
        });
    }
}
