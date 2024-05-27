<div class="row">
    <div class="col-md-12">
        <div class="user content-card">
            <ul>
                <li><h5>{{ $Cliente->nombres." ".$Cliente->apellidos }}</h5></li>

                @if($Cliente->estado_detalle_id != \easyCRM\App::$ESTADO_DETALLE_MATRICULADO)
                <li>Interesado en
                    <select name="carrera_id" id="carrera_id" class="form-input valor_carrera_id">
                        @foreach($Carreras as $q)
                            <option value="{{ $q->id }}" {{ $Cliente->carrera_id == $q->id ? "selected" : ""}}>{{ $q->name }}</option>
                        @endforeach
                    </select>
                </li>
                @endif

                <li>Viene desde <span class="text-lowercase">{{ $Cliente->fuentes != null ? $Cliente->fuentes->name : "-"  }}</span></li>
            </ul>
            <h5> </h5>

        </div>
    </div>
</div>
<form enctype="multipart/form-data" action="{{ in_array($Cliente->estado_detalle_id, [\easyCRM\App::$ESTADO_DETALLE_MATRICULADO , \easyCRM\App::$ESTADO_DETALLE_REINGRESO]) ? route('user.client.storeSeguimientoAdicional') : route('user.client.storeSeguimiento') }}" id="registroSeguimiento" method="POST"
      data-ajax="true" data-close-modal="true" data-ajax-loading="#loading" data-ajax-success="OnSuccessRegistroSeguimiento" data-ajax-failure="OnFailureRegistroSeguimiento">
    @csrf
    <input type="hidden" id="id" name="id" value="{{ $Cliente->id }}">
    <input type="hidden" id="carrera_hidden_id" name="carrera_hidden_id" value="{{ $Cliente->carrera_id }}">
    <div class="row">
    <div class="col-md-4">
        <div class="user-info content-card information">
            <div class="sub-title text-center">
                <p>Sales Pipeline</p>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="progress-line active"></div></div>
                <div class="col-md-3"><div class="progress-line {{ in_array($Cliente->estado_id, [\easyCRM\App::$ESTADO_SEGUIMIENTO, \easyCRM\App::$ESTADO_OPORTUNUDAD, \easyCRM\App::$ESTADO_CIERRE]) ? "active" : "" }}"></div></div>
                <div class="col-md-3"><div class="progress-line {{ in_array($Cliente->estado_id, [\easyCRM\App::$ESTADO_OPORTUNUDAD, \easyCRM\App::$ESTADO_CIERRE]) ? "active" : "" }}"></div></div>
                <div class="col-md-3"><div class="progress-line {{ in_array($Cliente->estado_id, [\easyCRM\App::$ESTADO_CIERRE]) ? "active" : "" }}"></div></div>
            </div>
            <hr>
            <table>
                <tbody>
                    <tr>
                        <td><label for="nombres">Nombres: </label></td>
                        <td><input type="text" class="form-input" id="nombres" name="nombres" value="{{ $Cliente->nombres }}" autocomplete="off" required>
                            <span data-valmsg-for="nombres"></span>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="apellidos">Apellidos: </label></td>
                        <td><input type="text" class="form-input" id="apellidos" name="apellidos" value="{{ $Cliente->apellidos }}" autocomplete="off" required>
                            <span data-valmsg-for="apellidos"></span>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dni">DNI: </label></td>
                        <td><input type="text" class="form-input" name="dni" id="dni" minlength="8" maxlength="10" value="{{ $Cliente->dni }}" autocomplete="off" onkeypress="return isNumberKey(event)" required>
                            <span data-valmsg-for="dni"></span>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="fecha_nacimiento">Fecha Nacimiento: </label></td>
                        <td><input type="date" class="form-input" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ $Cliente->fecha_nacimiento != null ? \Carbon\Carbon::parse($Cliente->fecha_nacimiento)->format('Y-m-d') : "-" }}" autocomplete="off" >
                            <span data-valmsg-for="fecha_nacimiento"></span>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="celular">Celular: </label></td>
                        <td><input type="text" class="form-input" name="celular" id="celular" minlength="9" maxlength="15" value="{{ $Cliente->celular }}" autocomplete="off" onkeypress="return isNumberKey(event)" required>
                            <span data-valmsg-for="celular"></span>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="whatsapp">Whatsapp: </label></td>
                        <td><input type="text" class="form-input" name="whatsapp" id="whatsapp" minlength="9" maxlength="15" value="{{ $Cliente->whatsapp != null ? $Cliente->whatsapp : "" }}" autocomplete="off" onkeypress="return isNumberKey(event)">
                            <span data-valmsg-for="whatsapp"></span>
                        </td>
                        <td><a href="javascript:sendMessage({{ $Cliente->whatsapp }})" id="whatsapp_link" title="Enviar un mensjae a {{ $Cliente->whatsapp }}" data-message="{{ $Cliente->whatsapp }}"><img src="/auth/image/icon/whatsApp.png" alt=""></a></td>
                    </tr>
                    <tr>
                        <td><label for="email">Email: </label></td>
                        <td><input type="email" class="form-input" name="email" id="email" value="{{ $Cliente->email }}" autocomplete="off"  required>
                            <span data-valmsg-for="email"></span>
                        </td>
                        <td><a href="mailto:{{ $Cliente->email }}" id="gmail" title="Enviar un correo a {{ $Cliente->email }}" data-mail="{{ $Cliente->email }}"><img src="/auth/image/icon/Mail.png" alt=""></a></td>
                    </tr>
                    <tr>
                        <td><label for="provincia_id">Provincia: </label></td>
                        <td><select name="provincia_id" id="provincia_id" class="form-input">
                                @foreach($Provincias as $q)
                                    <option value="{{ $q->id }}" {{ $Cliente->provincia_id == $q->id ? "selected" : ""}}>{{ $q->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="distrito_id">Distrito: </label></td>
                        <td><select name="distrito_id" id="distrito_id" class="form-input">
                                @foreach($Distritos as $q)
                                    <option value="{{ $q->id }}" {{ $Cliente->distrito_id == $q->id ? "selected" : ""}}>{{ $q->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr class="direccion_matricula {{ $Cliente->estado_detalle_id == \easyCRM\App::$ESTADO_DETALLE_MATRICULADO ? "" : "hidden" }}">
                        <td><label for="direccion">Dirección: </label></td>
                        <td><input type="text" class="form-input" name="direccion" id="direccion" value="{{ $Cliente->direccion }}" autocomplete="off">
                            <span data-valmsg-for="direccion"></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-8">

        <div class="user-action content-card">

            <div class="content-actions-client">
                @if($Cliente->estado_detalle_id != \easyCRM\App::$ESTADO_DETALLE_MATRICULADO)
                <div id="accionRealizada">
                <h5>Tarea realizada</h5>
                <hr>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-4">
                            <select name="accion_id" class="form-input"  id="accion_id" required>
                                <option value="">-- Acción --</option>
                                @foreach($Acciones as $q)
                                    <option value="{{ $q->id }}">{{ $q->name }}</option>
                                @endforeach
                            </select>
                            <span data-valmsg-for="accion_id"></span>
                        </div>
                        <div class="col-md-4">
                            <select name="estado_id" class="form-input"  id="estado_id" required>
                                <option value="">-- Estado --</option>
                                @foreach($Estados as $q)
                                    <option value="{{ $q->id }}">{{ $q->name }}</option>
                                @endforeach
                            </select>
                            <span data-valmsg-for="estado_id"></span>
                        </div>
                        <div class="col-md-4">
                            <select name="estado_detalle_id" class="form-input"  id="estado_detalle_id" required>
                                <option value="">-- Estado Detalle --</option>
                            </select>
                            <span data-valmsg-for="estado_detalle_id"></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-3">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-12">
                            <textarea name="comentario" id="comentario" class="form-input" cols="30" rows="2" placeholder="Comentario" required></textarea>
                            <span data-valmsg-for="comentario"></span>
                        </div>
                    </div>
                </div>
                </div>
                    <div id="proximaAccion" class="form-group">
                        <h5>Nueva tarea</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <select name="accion_realizar_id" class="form-input"  id="accion_realizar_id">
                                    <option value="">-- Acción a Realizar --</option>
                                    @foreach($Acciones as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="accion_realizar_id"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="fecha_accion_realizar" class="form-input" id="fecha_accion_realizar">
                                <span data-valmsg-for="fecha_accion_realizar"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="hora_accion_realizar" class="form-input" id="hora_accion_realizar">
                                    <option value="">-- Hora --</option>
                                    <option value="8:00">8:00 HRS</option>
                                    <option value="9:00">9:00 HRS</option>
                                    <option value="10:00">10:00 HRS</option>
                                    <option value="11:00">11:00 HRS</option>
                                    <option value="12:00">12:00 HRS</option>
                                    <option value="13:00">13:00 HRS</option>
                                    <option value="14:00">14:00 HRS</option>
                                    <option value="15:00">15:00 HRS</option>
                                    <option value="16:00">16:00 HRS</option>
                                    <option value="17:00">17:00 HRS</option>
                                    <option value="18:00">18:00 HRS</option>
                                    <option value="19:00">19:00 HRS</option>
                                    <option value="20:00">20:00 HRS</option>
                                    <option value="21:00">21:00 HRS</option>
                                </select>
                                <span data-valmsg-for="hora_accion_realizar"></span>
                            </div>
                        </div>
                    </div>

                {{-- aca es --}}
                @if(\Illuminate\Support\Facades\Auth::guard('web')->user()->profile_id != \easyCRM\App::$PERFIL_RESTRINGIDO)
                    <div id="datosAdicionales" class="form-group hidden">
                        <h5>Datos adicionales</h5>
                        <hr>

                        <div id="datosSemiPresencial" class="row {{$Cliente->carreras != null && $Cliente->carreras->semipresencial ? "" : "hidden" }}">
                            <div class="col-md-4">
                                <select name="presencial_sede_id" class="form-input" id="presencial_sede_id">
                                    <option value="">-- Sede --</option>
                                    @foreach($PresencialSedes as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="presencial_sede_id"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="presencial_turno_id" class="form-input" id="presencial_turno_id">
                                    <option value="">-- Turno --</option>
                                    @foreach($Turnos as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="presencial_turno_id"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="presencial_horario_id" class="form-input" id="presencial_horario_id">
                                    <option value="">-- Horario --</option>
                                </select>
                                <span data-valmsg-for="presencial_horario_id"></span>
                            </div>
                        </div>

                        <div class="form-group row mt-15">
                            <div class="col-md-4">                          
                                <span class="form-input modalidad_curso_a" readonly>
                                </span>
                            </div>
                            <div class="col-md-4">
                                <select name="sede_id" class="form-input" id="sede_id">
                                        <option value="">-- Sede --</option>
                                    @foreach($Sedes as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="sede_id"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="local_id" class="form-input" id="local_id">
                                    <option value="">-- Modo --</option>
                                </select>
                                <span data-valmsg-for="local_id"></span>
                            </div>

                        </div>

                        <div class="form-group row mt-15">
                            <div class="col-md-4">
                                <select name="turno_id" class="form-input" id="turno_id">
                                    <option value="">-- Turno --</option>
                                    @foreach($Turnos as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="turno_id"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="horario_id" class="form-input" id="horario_id">
                                    <option value="">-- Horario --</option>
                                </select>
                                <span data-valmsg-for="horario_id"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="tipo_operacion_id" class="form-input" id="tipo_operacion_id">
                                    <option value="">-- Tipo Operación --</option>
                                    @foreach($TipoOperaciones as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="tipo_operacion_id"></span>
                            </div>

                        </div>
                        <div class="form-group row mt-15">
                            <div class="col-md-4">
                                <input type="text" name="nro_operacion" class="form-input" maxlength="15" id="nro_operacion" placeholder="Nro Operación" autocomplete="off">
                                <span data-valmsg-for="nro_operacion"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="monto" class="form-input decimal" id="monto" placeholder="Monto" autocomplete="off">
                                <span data-valmsg-for="monto"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="nombre_titular" class="form-input" id="nombre_titular" placeholder="Nombre Titular" autocomplete="off">
                                <span data-valmsg-for="nombre_titular"></span>
                            </div>

                        </div>
                        <div class="form-group row mt-15">
                            <div class="col-md-4">
                                <input type="text" name="codigo_alumno" class="form-input" id="codigo_alumno" placeholder="Código Alumno" autocomplete="off">
                                <span data-valmsg-for="codigo_alumno"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="promocion" class="form-input" id="promocion" placeholder="Promoción" autocomplete="off">
                                <span data-valmsg-for="promocion"></span>
                            </div>
                        </div>
                        <div class="form-group mt-15">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="text" name="observacion" class="form-input" id="observacion" placeholder="Observación" autocomplete="off">
                                    <span data-valmsg-for="observacion"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div id="datosAdicionales" class="form-group hidden">
                        <h5>Datos adicionales</h5>
                        <hr>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <input type="text" class="form-input" id="codigo_reingreso" name="codigo_reingreso" placeholder="CÓDIGO">
                                <span data-valmsg-for="codigo_reingreso"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="semestre_termino_id" class="form-input" id="semestre_termino_id">
                                    <option value="">SEMESTRE QUE TERMINO</option>
                                    @foreach($SemestreTermino as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="semestre_termino_id"></span>
                            </div>
                            <div class="col-md-4">
                                <select name="ciclo_termino_id" class="form-input" id="ciclo_termino_id">
                                    <option value="">CICLO QUE TERMINO</option>
                                    @foreach($Ciclos as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="ciclo_termino_id"></span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <select name="semestre_inicio_id" class="form-input" id="semestre_inicio_id">
                                    <option value="">SEMESTRE REINICIO</option>
                                    @foreach($SemestreInicio as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="semestre_inicio_id"></span>
                            </div>
                            <div class="col-md-3">
                                <select name="ciclo_inicio_id" class="form-input" id="ciclo_inicio_id">
                                    <option value="">CICLO QUE INICIO</option>
                                    @foreach($Ciclos as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="ciclo_inicio_id"></span>
                            </div>
                            <div class="col-md-3">
                                <select name="mes" class="form-input" id="mes">
                                    <option value="">MES</option>
                                    @foreach($Meses as $q)
                                        <option value="{{ $q->id }}">{{ $q->name }}</option>
                                    @endforeach
                                </select>
                                <span data-valmsg-for="mes"></span>
                            </div>
                            <div class="col-md-3">
                                <select name="cursos_jalados" class="form-input" id="cursos_jalados">
                                    <option value="">CURSOS JALADOS</option>
                                    <option value="1">SI</option>
                                    <option value="0">NO</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mt-15">
                            <div class="col-md-4">
                                <input type="text" name="nombre_titular" class="form-input" id="nombre_titular" placeholder="Nombre Titular" autocomplete="off">
                                <span data-valmsg-for="nombre_titular"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="codigo_alumno" class="form-input" id="codigo_alumno" placeholder="Código Alumno" autocomplete="off">
                                <span data-valmsg-for="codigo_alumno"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="promocion" class="form-input" id="promocion" placeholder="Promoción" autocomplete="off">
                                <span data-valmsg-for="promocion"></span>
                            </div>
                        </div>
                        <div class="form-group mt-15">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="text" name="observacion" class="form-input" id="observacion" placeholder="Observación" autocomplete="off">
                                    <span data-valmsg-for="observacion"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="form-group text-right">
                    <button type="submit" class="btn-primary mb-20"><i class="fa fa-pencil-square-o"></i> Registrar Tarea</button>
                </div>
                @endif
            </div>

            <div class="mt-20 pb-5">
                <h5>Historial de acciones</h5>
                <hr>
                <div id="content-history">
                    <p>No existe historial registrada actualmente.</p>
                </div>
                @if(in_array($Cliente->estado_detalle_id,[\easyCRM\App::$ESTADO_DETALLE_MATRICULADO, \easyCRM\App::$ESTADO_DETALLE_REINGRESO]))
                    <h5>Nueva oportunidad</h5>
                    <hr>
                    <div id="content-history-adicional">
                        <p>Aún no tienes nuevas oportunidades.</p>
                    </div>
                    <div class="cursosAdicionales">
                        <div class="form-group text-right">
                            <button type="button" class="btn-primary mb-20"><i class="fa fa-plus"></i> Agregar Curso o Carrera</button>
                        </div>

                        <div id="datosAdicionales" class="form-group hidden">
                            <h5>Datos adicionales</h5>
                            <hr>
                            <div class="form-group row">
                                <div class="col-md-4">
                                    <select name="modalidad_adicional_id" class="form-input" id="modalidad_adicional_id">
                                        <option value="">-- Modalidad --</option>
                                        @foreach($Modalidades as $q)
                                            <option value="{{ $q->id }}">{{ $q->name }}</option>
                                        @endforeach
                                    </select>
                                    <span data-valmsg-for="modalidad_adicional_id"></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="carrera_adicional_id" class="form-input" id="carrera_adicional_id">
                                        <option value="">-- Carrera o Curso --</option>
                                    </select>
                                    <span data-valmsg-for="carrera_adicional_id"></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="sede_adicional_id" class="form-input" id="sede_adicional_id">
                                        <option value="">-- Método --</option>
                                        @foreach($Sedes as $q)
                                            <option value="{{ $q->id }}">{{ $q->name }}</option>
                                        @endforeach
                                    </select>
                                    <span data-valmsg-for="sede_adicional_id"></span>
                                </div>
                            </div>


                            <div id="datosSemiPresencialAdicional" class="row hidden">
                                <div class="col-md-4">
                                    <select name="presencial_adicional_sede_id" class="form-input" id="presencial_adicional_sede_id">
                                        <option value="">-- Sede --</option>
                                    </select>
                                    <span data-valmsg-for="presencial_adicional_sede_id"></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="presencial_adicional_turno_id" class="form-input" id="presencial_adicional_turno_id">
                                        <option value="">-- Turno --</option>
                                    </select>
                                    <span data-valmsg-for="presencial_adicional_turno_id"></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="presencial_adicional_horario_id" class="form-input" id="presencial_adicional_horario_id">
                                        <option value="">-- Horario --</option>
                                    </select>
                                    <span data-valmsg-for="presencial_adicional_horario_id"></span>
                                </div>
                            </div>


                            <div class="form-group row mt-15">

                                {{-- se ha desabilitado y ocultado este campo --}}
                                <div class="col-md-4" hidden>
                                    <select name="local_adicional_id" class="form-input" id="local_adicional_id" disabled>
                                        <option value="">-- Local --</option>
                                    </select>
                                    <span data-valmsg-for="local_adicional_id"></span>
                                </div>
                                {{-- end --}}


                                <div class="col-md-4">
                                    <select name="turno_adicional_id" class="form-input" id="turno_adicional_id">
                                        <option value="">-- Turno --</option>
                                        @foreach($Turnos as $q)
                                            <option value="{{ $q->id }}">{{ $q->name }}</option>
                                        @endforeach
                                    </select>
                                    <span data-valmsg-for="turno_adicional_id"></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="horario_adicional_id" class="form-input" id="horario_adicional_id">
                                        <option value="">-- Horario --</option>
                                    </select>
                                    <span data-valmsg-for="horario_adicional_id"></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="tipo_operacion_adicional_id" class="form-input" id="tipo_operacion_adicional_id">
                                        <option value="">-- Tipo Operación --</option>
                                        @foreach($TipoOperaciones as $q)
                                            <option value="{{ $q->id }}">{{ $q->name }}</option>
                                        @endforeach
                                    </select>
                                    <span data-valmsg-for="tipo_operacion_adicional_id"></span>
                                </div>
                            </div>


                            <div class="form-group row mt-15">

                                <div class="col-md-4">
                                    <input type="text" name="nro_operacion_adicional" class="form-input" maxlength="15" id="nro_operacion_adicional" placeholder="Nro Operación" autocomplete="off">
                                    <span data-valmsg-for="nro_operacion_adicional"></span>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="monto_adicional" class="form-input decimal" id="monto_adicional" placeholder="Monto" autocomplete="off">
                                    <span data-valmsg-for="monto_adicional"></span>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="nombre_titular_adicional" class="form-input" id="nombre_titular_adicional" placeholder="Nombre Titular" autocomplete="off">
                                    <span data-valmsg-for="nombre_titular_adicional"></span>
                                </div>
                            </div>



                            <div class="form-group row mt-15">

                                <div class="col-md-4">
                                    <input type="text" name="codigo_alumno_adicional" class="form-input" id="codigo_alumno_adicional" placeholder="Código Alumno" autocomplete="off">
                                    <span data-valmsg-for="codigo_alumno_adicional"></span>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="promocion_adicional" class="form-input" id="promocion_adicional" placeholder="Promoción" autocomplete="off">
                                    <span data-valmsg-for="promocion_adicional"></span>
                                </div>
                            </div>
                            <div class="form-group mt-15">
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="text" name="observacion_adicional" class="form-input" id="observacion_adicional" placeholder="Observación" autocomplete="off">
                                        <span data-valmsg-for="observacion_adicional"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group text-right">
                                <button type="submit" class="btn-primary mb-20"><i class="fa fa-pencil-square-o"></i> Registrar Matricula</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>


            <div class="mt-20 pb-5">
                <h5>Historial del registro</h5>
                <hr>
                <div id="content-history-registro">
                <?php $count = 1; ?>
                @for($i = 0; $i < count($HistorialReasignar); $i++ )
                    <div class="item">
                        <div class="number-image">
                            <div>
                                <span>{{ $count++ }}</span>
                            </div>
                        </div>
                        <div class="info-details">
                            <div>
                                <p class="info-details-title">{{ \easyCRM\App::formatDateStringSpanish($HistorialReasignar[$i]->created_at) }}</p>
                                <p>{{ ($HistorialReasignar[$i]['users']->name. " ". $HistorialReasignar[$i]['users']->last_name). ", reasigno este registro a la ASESORA: ". ($HistorialReasignar[$i]['vendedores']->name. " ". $HistorialReasignar[$i]['vendedores']->last_name) }}</p>
                            </div>
                        </div>
                    </div>
                @endfor

                <div class="item">
                    <div class="number-image">
                        <div>
                            <span>{{ $count++ }}</span>
                        </div>
                    </div>
                    <div class="info-details">
                        <div>
                            <p class="info-details-title">{{ \easyCRM\App::formatDateStringSpanish($Cliente->created_at) }}</p>
                            <p>Se registró este lead {{  $VendedorRegistrado != null ? (" a ". $VendedorRegistrado->vendedores->name. " ".$VendedorRegistrado->vendedores->last_name) : "" }}</p>
                        </div>
                    </div>
                </div>

                </div>
            </div>


        </div>

    </div>
</div>
</form>

<script type="text/javascript" src="auth/plugins/inputmask/dist/min/jquery.inputmask.bundle.min.js"></script>
<script type="text/javascript" src="auth/js/cliente/_Seguimiento.js"></script>


<script>

    function valor_carrera_id(){
        var valore = $(".valor_carrera_id").val()
        if(valore == 52){
            tiempo = "3 MESES"
            // $(".modalidad_curso_a").val(tiempo)
            $(".modalidad_curso_a").html(tiempo)
        }else if (valore == 53){
            tiempo = "5 MESES"
            // $(".modalidad_curso_a").val(tiempo)
            $(".modalidad_curso_a").html(tiempo)
        }
        
    }

    $(document).on("change", ".valor_carrera_id", function(){
        valor_carrera_id()
    });

    valor_carrera_id()
    
</script>
