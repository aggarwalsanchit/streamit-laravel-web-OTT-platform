<?php

namespace Modules\NotificationTemplate\Http\Middleware;

use App\Traits\Menu;
use Closure;

class GenerateMenus
{
    use Menu;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       
    }
}
