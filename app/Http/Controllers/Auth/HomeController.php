<?php

namespace easyCRM\Http\Controllers\Auth;

use easyCRM\App;
use easyCRM\Carrera;
use easyCRM\Cliente;
use easyCRM\Enterado;
use easyCRM\Estado;
use easyCRM\Fuente;
use easyCRM\Menu;
use easyCRM\Modalidad;
use easyCRM\Provincia;
use easyCRM\User;
use Illuminate\Http\Request;
use easyCRM\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    protected $clientes_per_page = 40;

    public function index(Request $request)
    {
        if(Auth::guard('web')->user()->profile_id == App::$PERFIL_CALL)
            return redirect(route('user.client.createView'));

        if($request->ajax()){

            $profile_id = Auth::guard('web')->user()->profile_id;

            //(in_array($profile_id, [App::$PERFIL_ADMINISTRADOR, App::$PERFIL_VENDEDOR]))  &&

            $clientes = Cliente::where(function ($q) use ($request){ if($request->name){
                $q->where(DB::raw("CONCAT(clientes.nombres,' ',clientes.apellidos)"), 'like', '%'.$request->name. '%')->orWhere('dni', 'like', '%'.$request->name. '%')->orWhere('celular', 'like', '%'.$request->name. '%')->orWhere('email', 'like', '%'.$request->name. '%');}})
                ->where(function ($q) use ($request){ if($request->estado_id){ $q->where('estado_id', $request->estado_id); }})
                ->where(function ($q) use ($request){ if($request->fecha_inicio && !$request->name){ $q->whereDate('ultimo_contacto', '>=', $request->fecha_inicio); }})
                ->where(function ($q) use ($request){ if($request->fecha_final && !$request->name){ $q->whereDate('ultimo_contacto', '<=', $request->fecha_final);}})

                ->whereHas('users', function ($query) use ($profile_id, $request){
                    if( (in_array($profile_id, [App::$PERFIL_ADMINISTRADOR, App::$PERFIL_VENDEDOR]))  && (!$request->fecha_inicio && !$request->name) ||
                        ($request->fecha_inicio && !$request->name && (!$request->estado_id && !$request->vendedor_id))
                       ){$query->whereIn('profile_id',  [App::$PERFIL_VENDEDOR, App::$PERFIL_PROVINCIA]); }
                })

                ->where(function ($q) use ($profile_id, $request){ if(in_array($profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_PERDIDOS, App::$PERFIL_RESTRINGIDO]) && !$request->name){ $q->where('user_id', Auth::guard('web')->user()->id); } })
                ->where(function ($q) use ($request){ if($request->vendedor_id && $request->vendedor_id != "undefined"){ $q->where('user_id', $request->vendedor_id);}})

                //->where(function ($q) use ($profile_id, $request){ if(in_array($profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_PERDIDOS, App::$PERFIL_RESTRINGIDO])){ $q->where('provincia', '!=', true); } })
                ->where(function ($q) use ($profile_id, $request){ if(in_array($profile_id, [App::$PERFIL_PROVINCIA])){ $q->where('user_id', Auth::guard('web')->user()->id); } })

                ->join('estados', 'estados.id', '=', 'clientes.estado_id')
                ->select(['clientes.*', 'estados.order'])
                ->orderBy('estados.order', 'asc')->orderBy('updated_at', 'desc')
                ->paginate($this->clientes_per_page);

            return [
                'clientes' => view('auth.cliente.ajax.listado')->with(['clientes' => $clientes, 'name' => $request->name, 'i' => ($this->clientes_per_page*($clientes->currentPage()-1)+1) ])->render(),
                'next_page' => $clientes->nextPageUrl()
            ];
        }

        $Estados =  Auth::guard('web')->user()->profile_id != App::$PERFIL_VENDEDOR ? Estado::all() : Estado::where('id','!=', App::$ESTADO_OTROS)->get();
        $Vendedores = User::whereIn('profile_id', [App::$PERFIL_VENDEDOR, App::$PERFIL_PROVINCIA, App::$PERFIL_RESTRINGIDO])->where('activo', true)->orderby('turno_id', 'asc')->get();

        return view('auth.home.index', ['Estados' => $Estados, 'Vendedores' => $Vendedores]);
    }


    /*public function restore()
    {
      try{


      $user = DB::table('users')->select('id')->where('profile_id', App::$PERFIL_VENDEDOR)
          ->where('id', '!=', App::$USUARIO_PROVINCIA)->where('turno', true)->first();


          $userTurnId = $user != null ? $user->id : DB::table('users')->select('id')->first()->id;


          $usersAllIds = DB::table('users')->whereNull('deleted_at')->where('profile_id', App::$PERFIL_VENDEDOR)
              ->where('activo', true)->orderBy('id', 'asc')->pluck('id')->toArray();

          $minId = array_search(min($usersAllIds), $usersAllIds);
          $maxId = array_search(max($usersAllIds), $usersAllIds);

          $userTurnId = array_search($userTurnId, $usersAllIds) + 1;

          if($userTurnId > $maxId)
              $userTurnId = $usersAllIds[$minId];
          else
              $userTurnId = $usersAllIds[$userTurnId];

          $user = User::find($userTurnId);
          $user->turno = true;
          if ($user->save()) {
              User::where('id', '!=', $user->id)->where('profile_id', App::$PERFIL_VENDEDOR)
                  ->update(['turno' => false]);
          }
      }catch (\Exception $e)
      {
          dd($e->getMessage());
      }
    }*/
}
