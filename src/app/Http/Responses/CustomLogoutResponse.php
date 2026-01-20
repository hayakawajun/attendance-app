<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class CustomLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if($request->routeIs('admin.logout') || $request->is('admin/*')){
            return redirect('/admin/login');
        }
        return redirect('/login');
    }
}