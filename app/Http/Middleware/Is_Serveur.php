<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class Is_Serveur
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        //If the status is not approved redirect to login 
        $role_id = Auth::user()->role_id;
        $role = Role::findOrFail($role_id);
        if(!$request->is('app/pos') && $role->name == 'Serveur'){
            return redirect('/app/pos');
        }
        return $response;
    }
}