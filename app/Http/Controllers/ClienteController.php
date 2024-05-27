<?php

namespace easyCRM\Http\Controllers\Auth;

use Carbon\Carbon;
use easyCRM\Accion;
use easyCRM\App;
use easyCRM\Carrera;
use easyCRM\Ciclo;
use easyCRM\Cliente;
use easyCRM\ClienteMatricula;
use easyCRM\ClienteSeguimiento;
use easyCRM\Distrito;
use easyCRM\Enterado;
use easyCRM\Estado;
use easyCRM\Exports\ClientesExport;
use easyCRM\Fuente;
use easyCRM\HistorialReasignar;
use easyCRM\Horario;
use easyCRM\Imports\ClientesImport;
use easyCRM\Mes;
use easyCRM\Modalidad;
use easyCRM\Notification;
use easyCRM\PresencialSede;
use easyCRM\Profile;
use easyCRM\Provincia;
use easyCRM\Sede;
use easyCRM\Semestre;
use easyCRM\TipoOperacion;
use easyCRM\Turno;
use easyCRM\User;
use Illuminate\Http\Request;
use easyCRM\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ClienteController extends Controller
{

    public function partialView()
    {
        $Provincias = Provincia::orderBy('name', 'asc')->get();
        $Modalidades = Modalidad::orderBy('name', 'asc')->get();
        $Fuentes = Fuente::whereNotIn('id', [App::$FUENTES_GOOGLE_ADS, App::$FUENTES_FACEBOOK_ADS])->orderBy('name', 'asc')->get();
        $Enterados = Enterado::orderBy('name', 'asc')->get();
        $Carreras = Carrera::all();
        $Ciclos = Ciclo::all();

        return view('auth.cliente._Mantenimiento', ['Provincias' => $Provincias, 'Modalidades' => $Modalidades,
            'Fuentes' => $Fuentes, 'Enterados' => $Enterados, 'Ciclos' => $Ciclos]);
    }

    public function createView()
    {
        $Provincias = Provincia::orderBy('name', 'asc')->get();
        $Modalidades = Modalidad::orderBy('name', 'asc')->get();
        $Fuentes = Fuente::whereNotIn('id', [App::$FUENTES_GOOGLE_ADS, App::$FUENTES_FACEBOOK_ADS])->orderBy('name', 'asc')->get();
        $Enterados = Enterado::orderBy('name', 'asc')->get();

        return view('auth.cliente.Mantenimiento', ['Provincias' => $Provincias, 'Modalidades' => $Modalidades,
            'Fuentes' => $Fuentes, 'Enterados' => $Enterados]);
    }

    public function store(Request $request)
    {
        $status = false; $user = null; $register = false; $update = false; $duplicado = false; $message = null; $validator = null;
        $userTurnId = null; $reintento = false;

        try{

        DB::beginTransaction();

        /*if(in_array($request->provincia_id, [1,2])){*/

            if (in_array(Auth::guard('web')->user()->profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_RESTRINGIDO, App::$PERFIL_PROVINCIA]))
            {
                $userTurnId = Auth::guard('web')->user()->id;
            }else
            {
                $user = DB::table('users')->select('id')->where('profile_id', App::$PERFIL_VENDEDOR)
                    ->where('id', '!=', App::$USUARIO_PROVINCIA)->where('turno', true)->first();

                $userTurnId = $user != null ? $user->id : DB::table('users')->select('id')->first()->id;
            }

        /*}else{
            $user = DB::table('users')->select('id')->where('id', App::$USUARIO_PROVINCIA)->first();
            $userTurnId = $user != null ? $user->id : DB::table('users')->select('id')->first()->id;
        }*/

        $clienteExist = Cliente::where('dni', $request->dni)
        ->orWhere('email', $request->email)
        ->orWhere('celular', $request->celular)
        ->orderby('created_at', 'desc')->first();

        if($clienteExist != null)
        {
            $fecha_inicio_act_camp = Carbon::now()->format('d') >= 16 ? Carbon::now()->firstOfMonth()->addDay(15) : Carbon::now()->subMonth('1')->firstOfMonth()->addDay(15);
            $fecha_final_act_camp = Carbon::now()->format('d') >= 16 ? Carbon::now()->endOfMonth()->addDay(15) : Carbon::now()->subMonth('1')->endOfMonth()->addDay(15);

            if(($clienteExist->created_at >= $fecha_inicio_act_camp  && $clienteExist->created_at <= $fecha_final_act_camp))
            {
                if( ($request->modalidad_id == App::$MODALIDAD_CURSO) ||
                    ($request->modalidad_id == App::$MODALIDAD_CARRERA && $clienteExist->modalidad_id == App::$MODALIDAD_CURSO))
                {
                    $Cliente_Cursos = Cliente::where('dni', $request->dni)->orWhere('email', $request->email)->orWhere('celular', $request->celular)
                        ->pluck('carrera_id')->toArray();

                    $Cliente_Carreras = Cliente::where('dni', $request->dni)->orWhere('email', $request->email)->orWhere('celular', $request->celular)
                        ->pluck('modalidad_id')->toArray();

                    if (
                        ($request->modalidad_id == App::$MODALIDAD_CURSO && !in_array($request->carrera_id, $Cliente_Cursos)) ||
                        ($request->modalidad_id == App::$MODALIDAD_CARRERA &&!in_array(App::$MODALIDAD_CARRERA, $Cliente_Carreras))
                       ) {
                        $register = true;
                    }else {
                        $duplicado = true;
                    }
                }else{
                    $duplicado = true;
                }

            }else {

                $Cliente_Cursos = Cliente::where('dni', $request->dni)
                    ->orWhere('email', $request->email)
                    ->orWhere('celular', $request->celular)
                    ->pluck('carrera_id')->toArray();

                if (!in_array($request->carrera_id, $Cliente_Cursos)) {
                    $register = true;
                } else if (!in_array($clienteExist->estado_id, [App::$ESTADO_CIERRE])) {
                    $reintento = true;
                    $update = true;
                } else {
                    $duplicado = true;
                }
            }
        }else{
            $register = true;
        }

        $request->merge([
            'user_id' =>  $userTurnId,
            'estado_id' => App::$ESTADO_NUEVO,
            'estado_detalle_id' => App::$ESTADO_DETALLE_NUEVO,
            'proviene_id' => App::$LLAMADA,
            'ultimo_contacto' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'provincia' => in_array($request->provincia_id, [1,2]) ? false : true,
            'reasignado' => false,
            'fuente_id' => $reintento ? App::$FUENTE_REINTENTO : $request->fuente_id
        ]);

        if(Auth::guard('web')->user()->profile_id == App::$PERFIL_CALL){
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'nombres' => 'required',
                'apellidos' => 'required',
                'dni' => ['required','min:8','max:10'],
                'celular' => ['required', 'min:9', 'max:15'],
                'email' => ['required','email'],
                'provincia' => 'required',
                'provincia_id' => 'required',
                'distrito_id' => 'required',
                'modalidad_id' => 'required',
                'carrera_id' => 'required',
                'fuente_id' => 'required',
                'enterado_id' => 'required',
                'estado_id' => 'required',
                'estado_detalle_id' => 'required'
            ]);
        }else if(Auth::guard('web')->user()->profile_id == App::$PERFIL_RESTRINGIDO) {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'nombres' => 'required',
                'apellidos' => 'required',
                'dni' => ['required','min:8','max:10'],
                'celular' => ['required', 'min:9', 'max:15'],
                'email' => ['required','email'],
                'provincia' => 'required',
                'provincia_id' => 'required',
                'distrito_id' => 'required',
                'modalidad_id' => 'required',
                'carrera_id' => 'required',
                'fuente_id' => 'required',
                'ciclo_id' => 'required',
                'estado_id' => 'required',
                'estado_detalle_id' => 'required'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'nombres' => 'required',
                'apellidos' => 'required',
                'dni' => ['required','min:8','max:10'],
                'celular' => ['required', 'min:9', 'max:15'],
                'email' => ['required','email'],
                'provincia' => 'required',
                'provincia_id' => 'required',
                'distrito_id' => 'required',
                'modalidad_id' => 'required',
                'carrera_id' => 'required',
                'fuente_id' => 'required',
                'enterado_id' => 'required',
                'estado_id' => 'required',
                'estado_detalle_id' => 'required'
            ]);
        }

        if (!$validator->fails())
        {
            if($duplicado) {
                return response()->json(['Success' => $status, 'Message' => "El cliente ya se encuentra registrado en esta campaña"]);
            }

            if($clienteExist != null && $update)
            {
                $Cliente = Cliente::find($clienteExist->id);
                $Cliente->estado_id = App::$ESTADO_REINTENTO;
                $Cliente->estado_detalle_id = App::$ESTADO_DETALLE_REINTENTO;
                $Cliente->fuente_id = App::$FUENTE_REINTENTO;
                if($Cliente->save()){ $status = true;}

            }else if($register)
            {
                $Cliente = Cliente::create($request->all());

                if (in_array(Auth::guard('web')->user()->profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_RESTRINGIDO, App::$PERFIL_PROVINCIA]))
                    $status = true;
                else{
                    $usersAllIds = DB::table('users')->whereNull('deleted_at')->where('profile_id', App::$PERFIL_VENDEDOR)
                        ->where('activo', true)->pluck('id')->toArray();

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
                        $status = true;
                        User::where('id', '!=', $user->id)->where('profile_id', App::$PERFIL_VENDEDOR)
                            ->update(['turno' => false]);
                    }
                }

                if($status){
                    $HistorialReasignar = new HistorialReasignar();
                    $HistorialReasignar->cliente_id =  $Cliente->id;
                    $HistorialReasignar->user_id =  1;
                    $HistorialReasignar->vendedor_id =  $Cliente->user_id;
                    $HistorialReasignar->observacion = "Registro";

                    if($HistorialReasignar->save()) $status = true;
                }
            }
        }

        if($status){ DB::commit(); }else { DB::rollBack(); }

        }catch (\Exception $e){
            $message = $e->getMessage();
            DB::rollBack();
        }

        return response()->json(['Success' => $status , 'Errors' => $validator != null ? $validator->errors() : null, 'Message' => $message ]);
    }

    public function partialViewMatriculado($id)
    {
        $Cliente = Cliente::find($id);

        if(in_array(Auth::guard('web')->user()->profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_RESTRINGIDO]) &&
            $Cliente->user_id != Auth::guard('web')->user()->id) return null;

        $Modalidades = Modalidad::all();
        $Carreras = Carrera::where('modalidad_id', $Cliente->modalidad_id)->get();
        $Turnos = Turno::whereNotIn('id', [App::$TURNO_GLOABAL])->get();
        $Horarios = Horario::where('carrera_id', $Cliente->carrera_id)->where('turno_id', $Cliente->turno_id)->get();

        return view('auth.cliente._Matriculado', ['Cliente' => $Cliente, 'Turnos' => $Turnos, 'Horarios' => $Horarios,
            'Modalidades' => $Modalidades, 'Carreras' => $Carreras]);
    }

    public function updateMatriculado(Request $request)
    {
        $Status = false;

        $Cliente = Cliente::find($request->id);

        if( !(in_array(Auth::guard('web')->user()->profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_RESTRINGIDO]) &&
            $Cliente->user_id != Auth::guard('web')->user()->id) ){

            $validator = Validator::make($request->all(), [
                'modalidad_id' => 'required',
                'carrera_id' => 'required',
                'turno_id' => 'required',
                'horario_id' => 'required'
            ]);

            if(!$validator->fails()){
                $Cliente->modalidad_id = $request->modalidad_id;
                $Cliente->carrera_id = $request->carrera_id;
                $Cliente->turno_id = $request->turno_id;
                $Cliente->horario_id = $request->horario_id;
                if ($Cliente->save()) $Status = true;
            }

        }

        return response()->json(['Success' => $Status, 'Errors' => $validator->errors()]);
    }

    public function partialViewSeguimiento($id)
    {
        $Cliente = Cliente::find($id);

        if(in_array(Auth::guard('web')->user()->profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_RESTRINGIDO, App::$PERFIL_PROVINCIA]) &&
            $Cliente->user_id != Auth::guard('web')->user()->id) return null;

        if(Auth::guard('web')->user()->profile_id == App::$PERFIL_RESTRINGIDO)
            $Estados = Estado::whereNotIn('id', [App::$ESTADO_NUEVO, App::$ESTADO_CIERRE, App::$ESTADO_OTROS])->get();
        else
            $Estados =  Estado::whereNotIn('id', [App::$ESTADO_NUEVO, App::$ESTADO_REINGRESO])->get();

        $Acciones = Accion::orderBy('name', 'asc')->get();
        $Turnos = Turno::whereNotIn('id', [App::$TURNO_GLOABAL])->get();
        $Sedes = Sede::all();
        $PresencialSedes = PresencialSede::all();
        $Modalidades = Modalidad::all();
        $Carreras = Carrera::all();
        $TipoOperaciones = TipoOperacion::all();
        $Provincias = Provincia::all();
        $Distritos = Distrito::where('provincia_id', $Cliente->provincia_id)->get();
        $Meses = Mes::all();
        $SemestreTermino = Semestre::whereIn('division', [App::$DIVISION_SEMESTRE_TERMINO, App::$DIVISION_SEMESTRE_COMPARTIDO])->get();
        $SemestreInicio = Semestre::whereIn('division', [App::$DIVISION_SEMESTRE_INICIO, App::$DIVISION_SEMESTRE_COMPARTIDO])->get();
        $Ciclos = Ciclo::all();
        $HistorialReasignar = HistorialReasignar::where('cliente_id', $id)->where('observacion', '!=','Registro')->orderby('created_at', 'desc')->get();
        $VendedorRegistrado = HistorialReasignar::where('cliente_id', $id)->where('observacion', 'Registro')->orderby('created_at', 'desc')->first();

        return view('auth.cliente._Seguimiento', ['Cliente' => $Cliente, 'Acciones' => $Acciones, 'Estados' => $Estados, 'Modalidades' => $Modalidades,
        'Turnos' => $Turnos, 'Sedes' => $Sedes, 'PresencialSedes' => $PresencialSedes, 'Carreras' => $Carreras, 'TipoOperaciones' => $TipoOperaciones, 'Provincias' => $Provincias,
        'Distritos' => $Distritos, 'Ciclos' => $Ciclos, 'Meses' => $Meses, 'SemestreTermino' => $SemestreTermino, 'SemestreInicio' => $SemestreInicio,
        'HistorialReasignar' => $HistorialReasignar, 'VendedorRegistrado' => $VendedorRegistrado]);
    }

    public function search_course($id)
    {
        $Turnos = Turno::whereNotIn('id', [App::$TURNO_GLOABAL])->get();
        $PresencialSedes = PresencialSede::all();
        return response()->json(['data' => Carrera::find($id), 'turnos' =>$Turnos, 'PresencialSedes' => $PresencialSedes]);
    }

    public function updateDatosContacto(Request $request)
    {

        $Exist = false; $Status = false; $Title = "Error"; $Message = "Algo salio mal, verifique los campos ingresados.";

        $Cliente = Cliente::find($request->id);

        if(in_array($Cliente->estado_detalle_id, [App::$ESTADO_DETALLE_MATRICULADO, App::$ESTADO_DETALLE_TRASLADO])){
            $validator = Validator::make($request->all(), [
                'nombres' => 'required',
                'apellidos' => 'required',
                'provincia_id' => 'required',
                'distrito_id' => 'required',
                'dni' => 'required|min:8|max:10',
                'celular' => 'required|min:9|max:15',
                'email' => 'required|email',
                'whatsapp' => 'nullable|min:9|max:15',
                'direccion' => 'required'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'nombres' => 'required',
                'apellidos' => 'required',
                'carrera_id' => 'required',
                'provincia_id' => 'required',
                'distrito_id' => 'required',
                'dni' => 'required|min:8|max:10',
                'celular' => 'required|min:9|max:15',
                'email' => 'required|email',
                'whatsapp' => 'nullable|min:9|max:15'
            ]);
        }

        $errors = []; $ErrorsModel = [];

        if (!$validator->fails()) {

            $validatorDNI = DB::table('clientes')->select('id')->where('modalidad_id', $Cliente->modalidad_id)->where('dni', $request->dni)->first();

            if($validatorDNI && $validatorDNI->id != $Cliente->id ){
                array_push($errors, ['dni' => ['El dni ya está en uso.']]);
            }

            $validatorCelular = DB::table('clientes')->select('id')->where('modalidad_id', $Cliente->modalidad_id)->where('celular', $request->celular)->first();

            if($validatorCelular && $validatorCelular->id != $Cliente->id ){
                array_push($errors, ['celular' => ['El celular ya está en uso.']]);
            }

            $validatorEmail = DB::table('clientes')->select('id')->where('modalidad_id', $Cliente->modalidad_id)->where('email', $request->email)->first();

            if($validatorEmail && $validatorEmail->id != $Cliente->id ){
                array_push($errors, ['email' => ['El email ya está en uso.']]);
            }

            $validatorWhatsapp = DB::table('clientes')->select('id')->where('modalidad_id', $Cliente->modalidad_id)->where('whatsapp', $request->whatsapp)->first();

            if ($request->whatsapp && $validatorWhatsapp && $validatorWhatsapp->id != $Cliente->id){
                array_push($errors, ['whatsapp' => ['El whatsapp ya está en uso.']]);
            }
        }

        if (count($errors) > 0) {
            $Exist = true; $errors = $errors[0];
        }else{ $errors = $validator->errors();}

        if (!$Exist && !$validator->fails()){
            $Cliente->nombres = $request->nombres;
            $Cliente->apellidos = $request->apellidos;
            $Cliente->dni = $request->dni;
            $Cliente->celular = $request->celular;
            $Cliente->whatsapp = $request->whatsapp;
            $Cliente->email = $request->email;
            $Cliente->fecha_nacimiento = $request->fecha_nacimiento;

            if($Cliente->estado_detalle_id != App::$ESTADO_DETALLE_MATRICULADO) {
                $Cliente->carrera_id = $request->carrera_id;
                $Cliente->modalidad_id = Carrera::find($request->carrera_id)->modalidad_id;
            }

            if(in_array($Cliente->estado_detalle_id, [App::$ESTADO_DETALLE_MATRICULADO, App::$ESTADO_DETALLE_TRASLADO])) {
                $Cliente->direccion = $request->direccion;
            }

            $Cliente->provincia_id = $request->provincia_id;
            $Cliente->distrito_id = $request->distrito_id;
            $Cliente->updated_at = Carbon::now();

            if ($Cliente->save()) {
                $Status = true;$Title = "Datos Actualizados";$Message = "Se han guardado los cambios realizados.";
            }
        }

        return response()->json(['Success' => $Status, 'Title' => $Title, 'Message' => $Message, 'Errors' => $errors]);
    }

    public function storeSeguimiento(Request $request)
    {
        $status = false;

        try{

            DB::beginTransaction();

            $request->merge([
            'cliente_id' => $request->id
        ]);

        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required',
            'accion_id' => 'required',
            'estado_id' => 'required',
            'estado_detalle_id' => 'required',
            'comentario' => 'required'
        ]);

        $CarreraPresencial = Carrera::find($request->carrera_hidden_id);

        if(in_array($request->estado_detalle_id, [App::$ESTADO_DETALLE_MATRICULADO, App::$ESTADO_DETALLE_TRASLADO])){

            if($CarreraPresencial != null && $CarreraPresencial->semipresencial)
            {
                $validator = Validator::make($request->all(), [
                    'cliente_id' => 'required',
                    'accion_id' => 'required',
                    'estado_id' => 'required',
                    'comentario' => 'required',
                    'estado_detalle_id' => 'required',
                    'turno_id' => 'required',
                    'horario_id' => 'required',
                    'sede_id' => 'required',
                    'presencial_turno_id' => 'required',
                    'presencial_horario_id' => 'required',
                    'presencial_sede_id' => 'required',
                    'tipo_operacion_id' => 'required',
                    'nro_operacion' => 'required|max:15',
                    'monto' => 'required|numeric',
                    'nombre_titular' => 'required',
                    'codigo_alumno' => 'required',
                    'promocion' => 'required',
                    'observacion' => 'required',
                    'direccion' => 'required'
                ]);
            }else{
                $validator = Validator::make($request->all(), [
                    'cliente_id' => 'required',
                    'accion_id' => 'required',
                    'estado_id' => 'required',
                    'comentario' => 'required',
                    'estado_detalle_id' => 'required',
                    'turno_id' => 'required',
                    'horario_id' => 'required',
                    'sede_id' => 'required',
                    'tipo_operacion_id' => 'required',
                    'nro_operacion' => 'required|max:15',
                    'monto' => 'required|numeric',
                    'nombre_titular' => 'required',
                    'codigo_alumno' => 'required',
                    'promocion' => 'required',
                    'observacion' => 'required',
                    'direccion' => 'required'
                ]);
            }
        }else if($request->estado_detalle_id == App::$ESTADO_DETALLE_REINGRESO){
            $validator = Validator::make($request->all(), [
                'codigo_reingreso' => 'required',
                'semestre_termino_id' => 'required',
                'ciclo_termino_id' => 'required',
                'semestre_inicio_id' => 'required',
                'ciclo_inicio_id' => 'required',
                'mes' => 'required',
                'cursos_jalados' => 'required'
            ]);
        }

        if (!$validator->fails()){
            $Seguimiento = ClienteSeguimiento::create($request->all());
            if($Seguimiento){
                $status = true;
                $Cliente = Cliente::find($request->cliente_id);
                $Cliente->estado_id = $request->estado_id;
                $Cliente->estado_detalle_id = $request->estado_detalle_id;
                $Cliente->ultimo_contacto = Carbon::now();

                if(in_array($request->estado_detalle_id, [App::$ESTADO_DETALLE_MATRICULADO, App::$ESTADO_DETALLE_TRASLADO]))
                {
                    $Cliente->direccion = $request->direccion;
                    $Cliente->turno_id = $request->turno_id;
                    $Cliente->sede_id = $request->sede_id;
                    $Cliente->horario_id = $request->horario_id;
                    $Cliente->tipo_operacion_id = $request->tipo_operacion_id;
                    $Cliente->nro_operacion = $request->nro_operacion;
                    $Cliente->monto = $request->monto;
                    $Cliente->nombre_titular = $request->nombre_titular;
                    $Cliente->codigo_alumno = $request->codigo_alumno;
                    $Cliente->promocion = $request->promocion;
                    $Cliente->observacion = $request->observacion;

                    if($CarreraPresencial != null && $CarreraPresencial->semipresencial)
                    {
                        $Cliente->presencial_sede_id = $request->presencial_sede_id;
                        $Cliente->presencial_turno_id = $request->presencial_turno_id;
                        $Cliente->presencial_horario_id = $request->presencial_horario_id;
                    }

                }else if($request->estado_detalle_id == App::$ESTADO_DETALLE_REINGRESO){
                    $Cliente->codigo_reingreso = $request->codigo_reingreso;
                    $Cliente->semestre_termino_id = $request->semestre_termino_id;
                    $Cliente->ciclo_termino_id = $request->ciclo_termino_id;
                    $Cliente->semestre_inicio_id = $request->semestre_inicio_id;
                    $Cliente->ciclo_inicio_id = $request->ciclo_inicio_id;
                        $Cliente->mes = $request->mes;
                    $Cliente->cursos_jalados = $request->cursos_jalados;
                }else if($request->estado_id == App::$ESTADO_OTROS){

                    $user = DB::table('users')->select('id')->where('profile_id', App::$PERFIL_RESTRINGIDO)->where('turno', true)->first();

                    $usersAllIds = DB::table('users')->whereNull('deleted_at')->where('profile_id', App::$PERFIL_RESTRINGIDO)
                        ->where('activo', true)->pluck('id')->toArray();

                    $userId = $user != null ? $user->id : DB::table('users')->select('id')->where('profile_id', App::$PERFIL_RESTRINGIDO)->first()->id;

                    $minId = array_search(min($usersAllIds), $usersAllIds);
                    $maxId = array_search(max($usersAllIds), $usersAllIds);

                    $userTurnId = array_search($userId, $usersAllIds) + 1;

                    if($userTurnId > $maxId)
                        $userTurnId = $usersAllIds[$minId];
                    else
                        $userTurnId = $usersAllIds[$userTurnId];

                    $userNew = User::find($userTurnId);
                    if($userNew != null) {
                        $userNew->turno = true;
                        if ($userNew->save()) {
                            $status = true;
                            if ($userNew->id != $userId) {
                                $userAnterior = User::find($userId);
                                $userAnterior->turno = false;
                                if (!$userAnterior->save()) $status = false;
                            }
                        }
                    }

                    if($status) $Cliente->user_id = $userTurnId;
                }

                $Cliente->save();
            }
        }

        if($status){

            $Notification = Notification::where('cliente_id', $Cliente->id)->where('estado', false)
                ->orderby('id', 'desc')->first();
            if($Notification != null){
                $Notification->estado = true;
                $Notification->save();
            }

            if($Seguimiento->fecha_accion_realizar != null && $Seguimiento->fecha_accion_realizar == Carbon::parse(Carbon::now())->format('Y-m-d')){
                $Notification = new Notification();
                $Notification->cliente_id = $Cliente->id;
                $Notification->cliente_seguimiento_id = $Seguimiento->id;
                $Notification->estado = false;
                $Notification->save();
            }

            DB::commit(); }else { DB::rollBack(); }

        }catch (\Exception $e){
            DB::rollBack();
        }

        return response()->json(['Success' => $status , 'Errors' => $validator->errors() ]);

    }

    public function storeSeguimientoAdicional(Request $request)
    {
        $status = false; $validator = null;

        try{

            DB::beginTransaction();

            $request->merge([
                'cliente_id' => $request->id,
            ]);

            $CarreraPresencial = Carrera::find($request->carrera_adicional_id);

                if($CarreraPresencial != null && $CarreraPresencial->semipresencial)
                {
                    $validator = Validator::make($request->all(), [
                        'modalidad_adicional_id' => 'required',
                        'carrera_adicional_id' => 'required',
                        'sede_adicional_id' => 'required',
                        'turno_adicional_id' => 'required',
                        'horario_adicional_id' => 'required',
                        'presencial_adicional_sede_id' => 'required',
                        'presencial_adicional_turno_id' => 'required',
                        'presencial_adicional_horario_id' => 'required',
                        'tipo_operacion_adicional_id' => 'required',
                        'nro_operacion_adicional' => 'required|max:15',
                        'monto_adicional' => 'required|numeric',
                        'nombre_titular_adicional' => 'required',
                        'codigo_alumno_adicional' => 'required',
                        'promocion_adicional' => 'required',
                        'observacion_adicional' => 'required',
                    ]);
                }else{
                    $validator = Validator::make($request->all(), [
                        'modalidad_adicional_id' => 'required',
                        'carrera_adicional_id' => 'required',
                        'sede_adicional_id' => 'required',
                        'turno_adicional_id' => 'required',
                        'horario_adicional_id' => 'required',
                        'tipo_operacion_adicional_id' => 'required',
                        'nro_operacion_adicional' => 'required|max:15',
                        'monto_adicional' => 'required|numeric',
                        'nombre_titular_adicional' => 'required',
                        'codigo_alumno_adicional' => 'required',
                        'promocion_adicional' => 'required',
                        'observacion_adicional' => 'required',
                    ]);
                }

            if (!$validator->fails()) {

                if(count(Cliente::where('id', $request->id)->where('modalidad_id', $request->modalidad_adicional_id)->where('carrera_id', $request->carrera_adicional_id)->get()) > 0 ||
                   count(ClienteMatricula::where('cliente_id', $request->id)->where('modalidad_adicional_id', $request->modalidad_adicional_id)->where('carrera_adicional_id', $request->carrera_adicional_id)->get()) > 0){
                    return response()->json(['Success' => $status , 'Message' => 'Ya se encuentra matriculado en '. Modalidad::find($request->modalidad_adicional_id)->name .' de '. Carrera::find($request->carrera_adicional_id)->name ]);
                }

                $seguimientoAdicional = ClienteMatricula::create($request->all());
                if($seguimientoAdicional){ $status = true; DB::commit(); }
            }

        }catch (\Exception $e){
            DB::rollBack();
        }

        return response()->json(['Success' => $status , 'Errors' => $validator != null ? $validator->errors() : [] ]);

    }

    public function list_filter_seguimiento(Request $request){
        $Seguimientos = ClienteSeguimiento::with('acciones')->where('cliente_id', $request->id)
            ->with('clientes.users')->with('clientes')->with('clientes.turnos')->with('clientes.sedes')->with('clientes.carreras')->with('clientes.modalidades')
            ->with('clientes.horarios')->with('clientes.semestreInicio')->with('clientes.cicloInicio')
            ->with('estados')->with('estadoDetalle')->orderby('created_at', 'desc')->get();
        return response()->json(['data' => $Seguimientos]);
    }

    public function list_filter_seguimiento_adicional(Request $request){
        $SeguimientosAdicionales = ClienteMatricula::where('cliente_id', $request->id)
            ->with('clientes')->with('turnos')->with('sedes')->with('carreras')->with('tipoOperaciones')
            ->with('modalidades')->with('horarios')->orderby('created_at', 'desc')->get();
        return response()->json(['data' => $SeguimientosAdicionales]);
    }

    public function partialViewExport()
    {
        $Estados =  !in_array(Auth::guard('web')->user()->profile_id, [App::$PERFIL_VENDEDOR, App::$PERFIL_PROVINCIA]) ? Estado::all() : Estado::where('id','!=', App::$ESTADO_OTROS)->get();
        $Vendedores = User::whereIn('profile_id', [App::$PERFIL_VENDEDOR, App::$PERFIL_PROVINCIA, App::$PERFIL_RESTRINGIDO])->orderby('turno_id', 'asc')->get();
        $Modalidades = Modalidad::all();
        $Turnos = Turno::whereNotIn('id', [App::$TURNO_GLOABAL])->get();

        return view('auth.cliente._Exportar', ['Estados' => $Estados, 'Vendedores' => $Vendedores, 'Modalidades' => $Modalidades, 'Turnos' => $Turnos]);
    }

    public function exportExcel($fechaInicio, $fechaFinal, $estado, $vendedor, $modalidad, $carrera, $turno)
    {
        return Excel::download(new ClientesExport($fechaInicio, $fechaFinal, $estado, (in_array(Auth::guard('web')->user()->profile_id,[App::$PERFIL_ADMINISTRADOR, App::$PERFIL_CAJERO]) ? $vendedor : Auth::guard('web')->user()->id), $modalidad, $carrera, $turno), 'ExportLeads.xlsx');
    }

    public function partialViewImport()
    {
        $Vendedor = Profile::whereIn('id', [App::$PERFIL_VENDEDOR, App::$PERFIL_RESTRINGIDO])->get();
        return view('auth.cliente.import.excel', ['Vendedor' => $Vendedor]);
    }

    public function importExcel(Request $request)
    {
        if($request->file('import_archivo_id') == null || $request->file('import_archivo_id')->getClientOriginalExtension() != "xlsx"){
            return response()->json(['Success' => false, 'Error' => 'Por favor, seleccione un archivo xls válido.']);
        }else{
            $import = new ClientesImport($request->import_perfil_id);
            $import->import($request->file('import_archivo_id'));

            return response()->json(['Success' => count($import->getErrors()) > 0 ? false : true, 'Errors' => $import->getErrors() ]);
        }
    }

    public function notifications()
    {
        $notifications = Notification::with('clientes')->with('cliente_seguimientos')->with('cliente_seguimientos.accionRealizar')
            ->where('estado', false)->whereHas('clientes', function ($q) { $q->where('user_id', Auth::guard('web')->user()->id);})->get();

        return response()->json(['data' => $notifications]);
    }

    public function delete(Request $request)
    {
        $status = false;

        $entity = Cliente::find($request->id);

        if($entity->delete()) $status = true;

        return response()->json(['Success' => $status]);
    }

}
