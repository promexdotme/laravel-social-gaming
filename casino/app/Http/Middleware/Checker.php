<?php 
namespace VanguardLTE\Http\Middleware
{
    class Checker
    {
        public function handle($request, \Closure $next)
        {
            if( !auth()->check() ) 
            {
                return $next($request);
            }
            if( $request->session()->has('beforeUser') ) 
            {
                return $next($request);
            }
            $user = \VanguardLTE\User::find(auth()->user()->id);
            $user->update(['last_online' => date('Y-m-d H:i:s')]);
            return $next($request);
        }
    }

}
