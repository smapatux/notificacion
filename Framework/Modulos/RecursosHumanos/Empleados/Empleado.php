<?php
/**
 *
 *
 */
namespace Modulos\RecursosHumanos\Empleados;

# Herramientas
use \Controlador\Respuestas\Respuesta;
use \Controlador\Respuestas\RespuestaJson;

# Respuestas disponibles
use \Controlador\Respuestas\RespuestaXlsx;
# Validacion
use \Controlador\__Base;
use \Herramientas\DbFormat as extraDb;
use \Herramientas\Generales\Auditing;
use \Herramientas\Herramientas;
use \Herramientas\ValidacionFormat;
use \Modulos\Administracion\Generales\Geografico\Municipio;
use \Modulos\Administracion\Generales\Personas\EstadoCivil;
use \Modulos\Administracion\Generales\Personas\Genero;
use \Modulos\Administracion\Generales\Personas\Parentesco;
use \Modulos\Administracion\Generales\Personas\TipoPersona;
use \Modulos\Administracion\Generales\Personas\TipoResidencia;
use \Modulos\Administracion\Generales\Personas\Titulo;
use \Modulos\Administracion\Generales\Telefonos;
use \Modulos\Administracion\GestionComercial\AreaTecnica\DivisionesTecnicas;
use \Modulos\Administracion\GestionComercial\Geografico\Sucursales;
use \Modulos\Administracion\RecursosHumanos\Departamentos\Areas;
use \Modulos\Administracion\RecursosHumanos\Departamentos\Direccion as dptoDireccion;
use \Modulos\Administracion\RecursosHumanos\Departamentos\EmpresaDivisiones;
use \Modulos\Administracion\RecursosHumanos\Departamentos\Puesto;
use \Modulos\Administracion\RecursosHumanos\Departamentos\SubDireccion;
use \Modulos\Administracion\RecursosHumanos\Personal\EstatusLaboral;
use \Modulos\Administracion\RecursosHumanos\Personal\Profesiones;
use \Modulos\Configuracion\Grupos\Grupos;
use \Modulos\Configuracion\Grupos\Usuarios as UsuarioGrupo;
use \Modulos\Configuracion\Usuario\Usuario;
use \Modulos\Home\Perfil\Perfil;
use \Modulos\RecursosHumanos\Empleados\ArchivoAlta\AltaEmpleado;
use \Modulos\RecursosHumanos\Empleados\CategoriaEmpleado;
use \Modulos\RecursosHumanos\Empleados\Comisionados\Comisionado;
use \Modulos\RecursosHumanos\Empleados\Escolaridad\Escolaridad;
use \Modulos\RecursosHumanos\Empleados\Escolaridad\EscolaridadEstado;
use \Modulos\RecursosHumanos\Empleados\Escolaridad\EscolaridadTipo;
use \Modulos\RecursosHumanos\Empleados\GuardiaCovid\Tipo as GCovidTipo;
use \Modulos\RecursosHumanos\Empleados\Hijos\Hijo;
use \Modulos\RecursosHumanos\Empleados\Movimientos;
use \Modulos\RecursosHumanos\Empleados\Principal;
use \Modulos\RecursosHumanos\Empleados\TipoEmpleado;
use \Modulos\RecursosHumanos\Empleados\Vacaciones\Periodo;

class Empleado extends __Base {
	public $datos    = [];
	public $errores  = [];
	private $seccion = 'Administracion';
	public function __construct() {

	}

	public function crearPersonal() {
		$plantilla = "RecursosHumanos/Empleados/alta.html";
		return new Respuesta($plantilla, $this->datos, $this->seccion);
	}

	public function detalle() {
		global $__sam;

		$validar = new ValidacionFormat("get");

		$id = $validar->v_clavePrincipal(true, "id");

		$listaEmpleado = new Principal();
		$listaEmpleado = $listaEmpleado->listar();
		$listaEmpleado = $listaEmpleado->datos["respuesta"]["Empleados"];

		if ($validar->hayError() || sizeof($listaEmpleado) == 0) {
			$this->redireccion('errores/404');
		} else {
			$this->datos["empleado"]   = $this->detallesEmpleado($id);
			$this->datos["empleadoId"] = $id;

			$perfilImagen = (new Perfil())->getImagenPerfil($this->datos["empleado"]->usuario_id);

			$this->datos["empleado"]->imagenPerfil = $perfilImagen;
			$this->datos["empleado"]->antiguedad   = $this->antiguedad($id);

			# Municipio
			$_GET                     = ['estado' => 1];
			$this->datos["Municipio"] = (new Municipio())->listar()->getDatos();

			# Genero
			$_GET                  = [];
			$this->datos["Genero"] = (new Genero())->listar()->getDatos();

			# Estado ciVil
			$_GET                       = [];
			$this->datos["EstadoCivil"] = (new EstadoCivil())->listar()->getDatos();

			# Titulo
			$_GET                  = [];
			$this->datos["Titulo"] = (new Titulo())->listar()->getDatos();

			# Telefonos
			$_GET                             = [];
			$this->datos["TipoPersonaFiscal"] = (new TipoPersona())->listar()->getDatos();

			# Persona fiscal
			$_GET                         = [];
			$this->datos["TiposTelefono"] = (new Telefonos())->listar()->getDatos();

			# Profesion
			$_GET                       = [];
			$this->datos["Profesiones"] = (new Profesiones())->listar()->getDatos();

			# Puestos
			$_GET                   = [];
			$this->datos["Puestos"] = (new Puesto())->listar()->getDatos();

			# dptoDireccion
			$_GET                       = [];
			$this->datos["Direcciones"] = (new dptoDireccion())->listar()->getDatos();

			# SubDirecciones
			$_GET                          = [];
			$this->datos["SubDirecciones"] = (new SubDireccion())->listar()->getDatos();

			# Areas
			$_GET                 = [];
			$this->datos["Areas"] = (new Areas())->listar()->getDatos();

			# Sucursales
			$_GET                      = [];
			$this->datos["Sucursales"] = (new Sucursales())->listar()->getDatos();

			# EstatusLaboral
			$_GET                          = [];
			$this->datos["EstatusLaboral"] = (new EstatusLaboral())->listar()->getDatos();

			# Division tecnica
			$_GET                              = [];
			$this->datos["DivisionesTecnicas"] = (new DivisionesTecnicas())->listar()->getDatos();

			# Empresa divisiones
			$_GET                             = [];
			$this->datos["EmpresaDivisiones"] = (new EmpresaDivisiones())->listar()->getDatos();

			$_GET                         = [];
			$this->datos["tiposEmpleado"] = (new TipoEmpleado())->listar()->getDatos();

			$this->datos["categoriasEmpleado"] = (new CategoriaEmpleado())->listar()->getDatos();

			$this->datos['token_form'] = $__sam->IdStorage->set(['id' => $id]);

			# Validacion de datos por secciones. Ej:
			# permiso -> Vacaciones:
			$this->datos['vacaciones']             = [];
			$this->datos['vacaciones']['periodos'] = Periodo::get();
			$this->datos['listaParentescos']       = (new Parentesco())->listar()->getDatos();
			$this->datos['listaResidencias']       = (new TipoResidencia())->listar()->getDatos();

			$this->datos["comisionado"] = (new Comisionado())->getComisionActiva($id);

			$_GET                 = ["personalId" => $id];
			$this->datos["hijos"] = (new Hijo())->listar()->getDatos();

			$_GET                              = ["personalId" => $id];
			$this->datos["escolaridad"]        = (new Escolaridad())->listar()->getDatos();
			$this->datos['escolaridadEstados'] = (new EscolaridadEstado())->listar()->getDatos();
			$this->datos['escolaridadTipos']   = (new EscolaridadTipo())->listar()->getDatos();
			// $_GET = ['id' => $id ];
			// $this->datos['vacaciones']['vacaciones'] =
			// 	(new VacacionesPrincipal())->getVacaciones()->getDatos()['vacaciones'];

			$this->datos['gCovidTipo'] = (new GCovidTipo())->listar()->getDatos();

			$plantilla = "RecursosHumanos/Empleados/detalles.html";
			return new Respuesta($plantilla, $this->datos, $this->seccion);
		}
	}

	public function apiDetalles() {
		global $__sam;
		$validar = new ValidacionFormat("get");
		$id = $validar->v_clavePrincipal(false, "id");
		$withPicture = $validar->v_checkBox(false, 'picture');

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$this->detallesEmpleado($id);

			if ($withPicture == 1) {
				$perfil = new Perfil();
				$imagen = $perfil->getImagenPerfil($perfil->getUserId($id), 71.5, 92);
				// $imagen = str_replace('data:image/jpeg;base64,', '', $imagen);
				$this->datos['datos']->img_perfil = $imagen;
			}
			$this->datos['token_form'] = $__sam->IdStorage->set(['id' => $id]);
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function altaPersonal() {
		global $dbGlobal;
		$validar = new ValidacionFormat("post");

		#Datos personales
		$nombre          = $validar->v_texto(true, "nombre");
		$titulo_id       = $validar->v_texto(false, "titulo");
		$apellidoPaterno = $validar->v_texto(true, "apellido_paterno");
		$apellidoMaterno = $validar->v_texto(true, "apellido_materno");
		$fechaNacimiento = $validar->v_fecha(false, "fecha_nacimiento");
		$genero_id       = $validar->v_claveForanea(false, "genero");
		$estadoCivil_id  = $validar->v_claveForanea(false, "estadoCivil");
		$curp            = $validar->v_curp(false, "curp");
		$clave_electoral = $validar->v_claveElectoral(false, "clave_electoral");
		$email           = $validar->v_email(false, "email");

		$titulo_id       = $validar->v_claveForanea(false, "titulo");
		$profesion_id    = $validar->v_claveForanea(false, "profesion");
		$especialidad_id = $validar->v_claveForanea(false, "especialidad");

		#Telefonos
		$telefonos = array();
		if (isset($_POST["telefonos"])) {
			foreach ($_POST["telefonos"] as $key => $value) {
				array_push($telefonos, array(
					"tipo_telefono" => $validar->v_claveForanea(true, $value["tipo_telefono"], "Tipo de telefono", array("nombre" => "telefonos[$key][tipo_telefono]")),
					"numero"        => $validar->v_telefono(true, $value["numero"], "Numero telefonico", array("nombre" => "telefonos[$key][numero]")),
				));
			}
		}

		if (isset($_POST["direccion"]) && $_POST["direccion"]['calle'] > 0) {
			$direccion = $_POST['direccion'];
		}

		#Datos Laborales
		$clave_empleado     = $validar->v_claveEmpleado(true, "clave_empleado");
		$area_id            = $validar->v_claveForanea(false, "laboralArea");
		$puesto_id          = $validar->v_claveForanea(false, "puesto");
		$divisionTecnica_id = $validar->v_claveForanea(false, "divisionTecnica");

		$empresaDivisiones_id = $validar->v_claveForanea(false, "empresaDivisiones");
		$ingreso_fecha        = $validar->v_fecha(false, "fecha_ingreso");

		$sucursal_id       = $validar->v_claveForanea(false, "sucursal");
		$estatusLaboral_id = $validar->v_claveForanea(false, "estatusLaboral");
		$esExterno         = $validar->v_checkbox(false, "esExterno");

		#Datos fiscles
		$razonSocial          = $validar->v_texto(false, "razon_social");
		$tipoPersonaFiscal_id = $validar->v_claveForanea(false, "tipoPersonaFiscal");
		$rfc                  = $validar->v_rfc(true, "rfc");

		if (isset($_POST["direccionFiscal"]) && $_POST["direccionFiscal"]['calle'] > 0) {
			$direccionFiscal = $_POST['direccionFiscal'];
		}

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$dbGlobal->iniciarTransaccion();

			$existeClave = $dbGlobal->getValue("SELECT COUNT(id_personal) FROM RecursosHumanos.Personal WHERE clave_empleado = '$clave_empleado'");
			if ($existeClave > 0) {
				$validar->anadirError('clave_empleado', 'Clave empleado', 'La clave de empleado ya existe.');
			} else {
				#----------------------------------------------------------------------------------
				#Datos personales
				#Agregar validacion
				$personal = array(
					'nombre'             => $nombre,
					'clave_empleado'     => $clave_empleado,
					'apellidoPaterno'    => $apellidoPaterno,
					'apellidoMaterno'    => $apellidoMaterno,
					'fechaNacimiento'    => Herramientas::formatoFechaBd($fechaNacimiento),
					'ingreso_fecha'      => Herramientas::formatoFechaBd($ingreso_fecha),
					'genero_id'          => $genero_id,
					'estadoCivil_id'     => $estadoCivil_id,
					'curp'               => $curp,
					'clave_electoral'    => $clave_electoral,
					'email'              => $email,
					'usuario_creador_id' => $_SESSION['id'],
					'profesion_id'       => $profesion_id,
					'especialidad_id'    => $especialidad_id,
					'titulo_id'          => $titulo_id,
					'area_id'            => $area_id,
					'divisionTecnica_id' => $divisionTecnica_id,
					'empresaDivision_id' => $empresaDivisiones_id,
					'sucursal_id'        => $sucursal_id,
					'estatusLaboral_id'  => $estatusLaboral_id,
					'esExterno'          => $esExterno,
					'puesto_id'          => $puesto_id,
					"aplicaHorario"      => 1,
				);

				# Crear direccion
				if (isset($direccion)) {
					unset($_POST);
					$Direccion = new Direccion();
					$_POST     = $direccion;
					$Direccion = $Direccion->agregar();
					if (count($Direccion->datos["errores"]) == 0) {
						$personal['direccion_id'] = $Direccion->datos["respuesta"]["datos"]->id;
					} else {
						$validar->anadirArrayError('direccion', $Direccion->datos["errores"]);
					}

				}

				$idEmpleado = $dbGlobal->qqInsert("RecursosHumanos.Personal", $personal);
				self::insertAplicaHorario($idEmpleado, 1, date('Y-m-d'));

				# Telefonos
				foreach ($telefonos as $telefono) {
					$dbGlobal->run("EXEC Generales.agregarTelefono $idEmpleado, $telefono[numero], $telefono[tipo_telefono], 0");
				}

				#----------------------------------------------------------------------------------
				#Datos Fscales
				$datosFiscales = array(
					'razonSocial'          => $razonSocial,
					'tipoPersonaFiscal_id' => $tipoPersonaFiscal_id ?? 1,
					'rfc'                  => $rfc,
					'personal_id'          => $idEmpleado,
				);

				# Crear direccion fiscal
				if (isset($direccionFiscal)) {
					unset($_POST);
					$Direccion = new Direccion();
					$_POST     = $direccionFiscal;
					$Direccion = $Direccion->agregar();
					if (count($Direccion->datos["errores"]) == 0) {
						$datosFiscales['direccion_id'] = $Direccion->datos["respuesta"]["datos"]->id;
					} else {
						$validar->anadirArrayError('direccionFiscal', $Direccion->datos["errores"]);
					}

				}
				$idFiscal = $dbGlobal->qqInsert("RecursosHumanos.Personal_DatosFiscales", $datosFiscales);

				# Crear usuario en sistema
				$usuario = new Usuario();
				$_POST   = [
					'idEmpleado'        => $idEmpleado,
					'usuario'           => $clave_empleado,
					'password'          => $clave_empleado . '+' . date('m'),
					'confirmarPassword' => $clave_empleado . '+' . date('m'),
				];
				$usuario = $usuario->crearCuenta();

				if (count($usuario->datos['errores']) == 0) {
					#Insert usuario en grupo
					$grupo = new Grupos();
					unset($_GET);
					$_GET['nombre'] = 'basico';
					$grupo          = $grupo->listar();

					if (isset($grupo->datos["respuesta"]["Grupos"][0])) {
						$grupo = $grupo->datos["respuesta"]["Grupos"][0];
						$_POST = [
							'grupo' => $grupo['id'],
							'id'    => $usuario->datos["respuesta"]["Usuario"]["id"],
						];
						$usuario = new UsuarioGrupo();
						$usuario = $usuario->guardar();
					}

				} else {
					$validar->combinarErrores($usuario->datos["errores"]);
				}

			}

			if ($validar->hayError()) {
				$dbGlobal->cancelarTransaccion();
				$this->errores = $validar->infoErrores();
			} else {
				$dbGlobal->guardarTransaccion();
				$this->datos["datos"]   = ['id' => $idEmpleado];
				$this->datos["mensaje"] = "Datos exitosamente cargados";
			}
		}

		return new RespuestaJson(
			["respuesta" => $this->datos, "errores" => $this->errores],
			$this->seccion
		);
	}

	public function detallesEmpleado($id) {
		global $dbGlobal;
		$formatDb = new extraDb($filtros);

		if (!empty($id)) {
			$formatDb->where("id", $id, "AND");
		}

		$this->datos["mensaje"] = "Datos exitosamente cargados";
		$this->datos["datos"]   = $dbGlobal->getArray
			(
			"SELECT
					[usuario_id]
					,[id]
					,[clave_empleado]
					,[nombreCompleto]
					,[nombre]
					,[apellidoPaterno]
					,[apellidoMaterno]
					,[email]
					,[telefonos]
					,[clave_electoral]
					,[curp]
					,[fechaNacimiento]
					,[observacion]
					,[comentario]
					,[esExterno]
					,[esCliente]
					,[ingreso_fecha]
					,[fechaBaja]
					,[JefeInmediato.id]
					,[JefeInmediato.clave_empleado]
					,[JefeInmediato.nombre]
					,[Genero.id]
					,[Genero.nombre]
					,[Titulo.id]
					,[Titulo.nombre]
					,[EstadoCivil.id]
					,[EstadoCivil.nombre]
					,[Area.id]
					,[Area.nombre]
					,[Area.clave]
					,[SubDireccion.id]
					,[SubDireccion.nombre]
					,[SubDireccion.clave]
					,[DireccionLaboral.id]
					,[DireccionLaboral.nombre]
					,[DireccionLaboral.clave]
					,[Puesto.id]
					,[Puesto.clave]
					,[Puesto.nombre]
					,[Profesion.id]
					,[Profesion.nombre]
					,[Especialidad.id]
					,[Especialidad.nombre]
					,[sucursal_id]
					,[EstatusLaboral.id]
					,[EstatusLaboral.nombre]
					,[calendarioPersonal_id]
					,[EmpresaDivision.id]
					,[EmpresaDivision.nombre]
					,[DivisionTecnica.id]
					,[DivisionTecnica.nombre]
					,[DatosFiscales.rfc]
					,[DatosFiscales.razonSocial]
					,[DatosFiscales.tipoFiscal]
					,[DatosFiscales.TipoFiscal.id]
					,[DatosFiscales.TipoFiscal.nombre]
					,[Sucursal.id]
					,[Sucursal.nombre]
					,[TipoEmpleado.id]
					,[TipoEmpleado.nombre]
					,[hayEmailPapeleta]
					,[TipoEmpleadoCategoria.id]
					,[TipoEmpleadoCategoria.nombre]
					,[id_datosPersonales]
					,[calle]
					,[noExterior]
					,[noInterior]
					,[entreCalles]
					,[referencias]
					,[LocalidadColonia.id]
					,[LocalidadColonia.Nombre]
					,[codigoPostal]
					,[LocalidadMunicipio.id]
					,[LocalidadMunicipio.nombre]
					,[LocalidadEstado.id]
					,[LocalidadEstado.nombre]
					,[LocalidadPais.id]
					,[LocalidadPais.nombre]
					,[telefonoEmergencia]
					,[nombreEmergencia]
					,[parentescoEmergencia.id]
					,[parentescoEmergencia.nombre]
					,[tipoResidencia.id]
					,[tipoResidencia.nombre]
					,[aplicaHorario]
				  FROM RecursosHumanos.v_Personal" . $formatDb->getConditionComplete()
		);

		$this->datos["datos"] = $formatDb->queryResult2Object($this->datos["datos"]);
		$this->datos["datos"] = $this->datos["datos"][0];

		$this->datos["datos"]->Telefonos = $formatDb->queryResult2Object($dbGlobal->getArray("SELECT
						[id]
						,[numero]
						,[Personal.id]
						,[TelefonoTipo.id]
						,[TelefonoTipo.nombre]
				FROM RecursosHumanos.v_Personal_Telefonos WHERE [Personal.id] = $id"));

		$direccion                       = $dbGlobal->getArray("select * from AtencionPublico.v_Direcciones where id=(select direccion_id  from RecursosHumanos.Personal where id_personal=$id)");
		$direccion                       = $formatDb->queryResult2Object($direccion);
		$this->datos["datos"]->Direccion = $direccion[0] ?? [];

		$grupos = $dbGlobal->getArray("SELECT
					ug.idUsuario
					,sg.Descripcion
					,sg.Estatus
					,sg.Grupo
					,sg.alias
					,sg.IDGrupo
				FROM Sistema_Usuario_Grupos ug
				INNER JOIN Sistema_Grupos sg ON ug.IDGrupo=sg.IDGrupo
				INNER JOIN usuarios u ON u.id = ug.idUsuario
				INNER JOIN RecursosHumanos.Personal per ON per.id_personal = u.id_empleado
				WHERE per.id_personal= $id");
		$this->datos["datos"]->Grupos = $grupos ?? [];

		$this->datos["datos"]->tieneFotografia = $dbGlobal->getValue("SELECT IIF ( imagenPerfil = NULL , 'NO', 'SI' ) FROM RecursosHumanos.Personal WHERE id_personal = $id");

		return $this->datos["datos"];
	}

	public function listarPersonalTecnico() {
		global $dbGlobal;
		$validar = new ValidacionFormat("get");

		$id = $validar->v_clavePrincipal(false, "id");

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$formatDb = new extraDb($filtros);

			if (!empty($id)) {
				$formatDb->where("id", $id, "AND");
			}

			$this->datos["mensaje"] = "Datos exitosamente cargados";
			$this->datos["datos"]   = $dbGlobal->getArray
				(
				"SELECT [id]
						,[clave_empleado]
						,CONCAT(clave_empleado,' - ',nombreCompleto) as identificador
						,[nombreCompleto]
						,[nombre]
						,[apellidoPaterno]
						,[apellidoMaterno]
						,[email]
						,[clave_electoral]
						,[curp]
						,[fechaNacimiento]
						,[observacion]
						,[comentario]
						,[esExterno]
						,[esCliente]
						,[ingreso_fecha]
						,[Genero.id]
						,[Genero.nombre]
						,[Titulo.id]
						,[Titulo.nombre]
						,[EstadoCivil.id]
						,[EstadoCivil.nombre]
						,[Area.id]
						,[Area.nombre]
						,[Puesto.id]
						,[Puesto.nombre]
						,[Profesion.id]
						,[Profesion.nombre]
						,[Especialidad.id]
						,[Especialidad.nombre]
						,[sucursal_id]
						,[EstatusLaboral.id]
						,[EstatusLaboral.nombre]
						,[calendarioPersonal_id]
						,[EmpresaDivision.id]
						,[EmpresaDivision.nombre]
						,[DivisionTecnica.id]
						,[DivisionTecnica.nombre]
					  FROM RecursosHumanos.v_Personal_Tecnico" . $formatDb->getConditionComplete()
			);
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function editarDatosPersonales() {
		global $dbGlobal;
		global $__sam;

		$validar = new ValidacionFormat("post");
		#---------------------------------------------------------
		# Token valido
		$token_form = $validar->v_tokenform(true, "token_form");
		if (!$validar->hayError()) {
			$datos = $__sam->IdStorage->get($token_form ?? null);
			if (is_null($datos)) {
				$validar->anadirError("token_form", "", "Token invalido o Erroneo !!");
			} else {
				$id = $datos['id'];
			}

		}

		#Datos personales
		$nombre          = $validar->v_texto(true, "nombre");
		$titulo_id       = $validar->v_texto(false, "titulo");
		$apellidoPaterno = $validar->v_texto(false, "apellido_paterno");
		$apellidoMaterno = $validar->v_texto(false, "apellido_materno");
		$fechaNacimiento = $validar->v_fecha(false, "fecha_nacimiento");
		$genero_id       = $validar->v_claveForanea(false, "genero");
		$estadoCivil_id  = $validar->v_claveForanea(false, "estadoCivil");
		$curp            = $validar->v_curp(false, "curp");
		$clave_electoral = $validar->v_claveElectoral(false, "clave_electoral");
		$email           = $validar->v_email(false, "email");

		$titulo_id        = $validar->v_claveForanea(false, "titulo");
		$profesion_id     = $validar->v_claveForanea(false, "profesion");
		$especialidad_id  = $validar->v_claveForanea(false, "especialidad");
		$hayEmailPapeleta = $validar->v_checkbox(false, "hayEmailPapeleta");
		$jefeInmediato_id = $validar->v_claveForanea(false, "jefeInmediato_id");

		//$codigoPostal = $validar->v_entero( false,"codigoPostal" );
		//$regionEstado = $validar->v_texto( false,"regionEstado" );
		//$delegacion = $validar->v_texto( false,"delegacion" );
		$colonia              = $validar->v_claveForanea(false, "colonia");
		$calle                = $validar->v_texto(false, "calle");
		$noExterior           = $validar->v_texto(false, "noExterior");
		$noInterior           = $validar->v_texto(false, "noInterior");
		$entreCalles          = $validar->v_texto(false, "entreCalles");
		$referencias          = $validar->v_texto(false, "referencias");
		$nombreEmergencia     = $validar->v_texto(false, "nombreEmergencia");
		$telefonoEmergencia   = $validar->v_texto(false, "telefonoEmergencia");
		$parentescoEmergencia = $validar->v_claveForanea(false, "parentescoEmergencia");
		$tipoResidencia       = $validar->v_claveForanea(false, "tipoResidencia");

		#Telefonos
		$telefonos = array();
		if (isset($_POST["telefonos"])) {
			foreach ($_POST["telefonos"] as $key => $value) {
				array_push($telefonos, array(
					"tipo_telefono" => $validar->v_claveForanea(true, $value["tipo_telefono"], "Tipo de telefono", array("nombre" => "telefonos[" . $key . "][tipo_telefono]")),
					"numero"        => $validar->v_telefono(true, $value["numero"], "Numero telefonico", array("nombre" => "telefonos[" . $key . "][numero]")),
				));
			}
		}

		if (isset($_POST["direccion"]) && $_POST["direccion"]['calle'] > 0) {
			$direccion = $_POST['direccion'];
		}

		#Datos Laborales

		#Clave de empleado valida
		/*--
		$clave_empleado = $validar->v_claveEmpleado(true, "clave_empleado");
		if (!$validar->hayError()) {

			if (!($this->claveEmpleadoDisponible($id, $clave_empleado))) {
				$validar->anadirError("clave_empleado", $clave_empleado, "La clave de empleado no esta disponible");
			}

		}
		--*/

		$ingreso_fecha        = $validar->v_fecha(false, "fecha_ingreso");
		$fecha_baja           = $validar->v_fecha(false, "fecha_baja");
		$area_id              = $validar->v_claveForanea(false, "laboralArea");
		$puesto_id            = $validar->v_claveForanea(false, "puesto");
		$divisionTecnica_id   = $validar->v_claveForanea(false, "divisionTecnica");
		$empresaDivisiones_id = $validar->v_claveForanea(false, "empresaDivisiones");
		$sucursal_id          = $validar->v_claveForanea(false, "sucursal");
		$estatusLaboral_id    = $validar->v_claveForanea(false, "estatusLaboral");
		$esExterno            = $validar->v_checkbox(false, "esExterno");
		$observaciones        = $validar->v_texto(false, "observaciones");
		$tipoEmpleado         = $validar->v_claveForanea(false, "tipoEmpleado");
		$categoriaEmpleado    = $validar->v_claveForanea(false, "categoriaEmpleado");

		#Datos fiscles
		$razonSocial          = $validar->v_texto(false, "razon_social");
		$tipoPersonaFiscal_id = $validar->v_claveForanea(false, "tipoPersonaFiscal");
		$rfc                  = $validar->v_texto(false, "rfc");
		$aplicaHorario        = $validar->v_checkbox(false, "aplicaHorario");

		#HIJOS

		$hijosArray = $_POST['hijos'] ?? [];
		foreach ($hijosArray as $key => $value) {
			$validar->v_nombre(
				true,
				$value["nombre"],
				"nombre",
				["nombre" => "hijos[$key][nombre]"]
			);

			$validar->v_nombre(
				true,
				$value["ap_pat"],
				"apellido paterno",
				["nombre" => "hijos[$key][ap_pat]"]
			);

			$validar->v_nombre(
				true,
				$value["ap_mat"],
				"apellido materno",
				["nombre" => "hijos[$key][ap_mat]"]
			);

			$validar->v_numeroPositivo(
				true,
				$value["edad"],
				"edad",
				["nombre" => "hijos[$key][edad]"]
			);

			$validar->v_claveForanea(
				true,
				$value["generoId"],
				"sexo",
				["nombre" => "hijos[$key][generoId]"]
			);
		}

		#ESCOLARIDAD
		$escolaridadArray = $_POST['escolaridad'] ?? [];
		foreach ($escolaridadArray as $key => $value) {
			$validar->v_claveForanea(
				true,
				$value["tipoId"],
				"escolaridad",
				["nombre" => "escolaridad[$key][tipoId]"]
			);

			$validar->v_claveForanea(
				true,
				$value["estadoId"],
				"estado",
				["nombre" => "escolaridad[$key][estadoId]"]
			);

			$validar->v_claveForanea(
				false,
				$value["profesionId"],
				"titulo/profesion",
				["nombre" => "escolaridad[$key][profesionId]"]
			);

			$validar->v_texto(
				false,
				$value["profesionOtro"],
				"titulo/profesion",
				["nombre" => "escolaridad[$key][profesionOtro]"]
			);
		}

		if (isset($_POST["direccionFiscal"]) && $_POST["direccionFiscal"]['calle'] > 0) {
			$direccionFiscal = $_POST['direccionFiscal'];
		}

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$dbGlobal->iniciarTransaccion();

			$audit = new Auditing();

			$audit->query(__METHOD__, "SELECT
						id
						,clave_empleado
						,nombreCompleto
						,[EstadoCivil.id]
						,[EstadoCivil.nombre]
						,[Area.id]
						,[Area.nombre]
						,[DireccionLaboral.id]
						,[DireccionLaboral.nombre]
						,[Puesto.id]
						,[Puesto.nombre]
						,[EstatusLaboral.id]
						,[EstatusLaboral.nombre]
						,[DatosFiscales.rfc]
						,[TipoEmpleado.id]
						,[TipoEmpleado.nombre]
						,[TipoEmpleadoCategoria.id]
						,[TipoEmpleadoCategoria.nombre]
					from
						RecursosHumanos.v_Personal
					where
						id = $id", ['id' => $id]);

			#----------------------------------------------------------------------------------
			#Datos personales
			$personal = [
				'id_personal'              => $id,
				'nombre'                   => $nombre,
				//--'clave_empleado'           => $clave_empleado,
				'apellidoPaterno'          => $apellidoPaterno,
				'apellidoMaterno'          => $apellidoMaterno,
				'fechaNacimiento'          => Herramientas::formatoFechaBd($fechaNacimiento),
				'ingreso_fecha'            => Herramientas::formatoFechaBd($ingreso_fecha),
				'fechaBaja'                => Herramientas::formatoFechaBd($fecha_baja),
				'curp'                     => $curp,
				'clave_electoral'          => $clave_electoral,
				'email'                    => $email,
				'usuario_creador_id'       => $_SESSION['id'],
				'genero_id'                => $genero_id,
				'profesion_id'             => $profesion_id,
				'estadoCivil_id'           => $estadoCivil_id,
				'especialidad_id'          => $especialidad_id,
				'titulo_id'                => $titulo_id,
				'area_id'                  => $area_id,
				'divisionTecnica_id'       => $divisionTecnica_id,
				'empresaDivision_id'       => $empresaDivisiones_id,
				'sucursal_id'              => $sucursal_id,
				'estatusLaboral_id'        => $estatusLaboral_id,
				'esExterno'                => $esExterno,
				'observacion'              => $observaciones,
				'puesto_id'                => $puesto_id,
				'tipoEmpleado_id'          => $tipoEmpleado,
				'tipoEmpleadoCategoria_id' => $categoriaEmpleado,
				"hayEmailPapeleta"         => $hayEmailPapeleta,
				"nombreEmergencia"         => $nombreEmergencia,
				"telefonoEmergencia"       => $telefonoEmergencia,
				"parentescoEmergencia_id"  => $parentescoEmergencia,
				"jefeInmediato_id"         => $jefeInmediato_id,
				"aplicaHorario"            => $aplicaHorario,
			];

			$hayRegistroDatosPersonales = $dbGlobal->getValue("SELECT id_datosPersonales from RecursosHumanos.personalDatosPersonales where personal_id = $id");

			$datosPersonales = [
				"colonia_id"        => $colonia,
				"calle"             => $calle,
				"noExterior"        => $noExterior,
				"noInterior"        => $noInterior,
				"entreCalles"       => $entreCalles,
				"referencias"       => $referencias,
				"tipoResidencia_id" => $tipoResidencia,
			];
			if ($hayRegistroDatosPersonales) {
				$datosPersonales['id_datosPersonales'] = $hayRegistroDatosPersonales;
				$dbGlobal->qqUpdate("RecursosHumanos.personalDatosPersonales", $datosPersonales);
			} else {
				$datosPersonales['personal_id'] = $id;
				$dbGlobal->qqInsert("RecursosHumanos.personalDatosPersonales", $datosPersonales);
			}
			/*--
				#Cambio la clave del empleado?
				$claveAnterior = $dbGlobal->getValue("
						SELECT
							ISNULL( clave_empleado, '' )
						FROM RecursosHumanos.Personal
						WHERE id_personal = $id AND clave_empleado != '$clave_empleado'");

				#Actualiza la clave de acceso al sistema
				if ($claveAnterior != '') {
					$usuario   = new Usuario();
					$idUsuario = $usuario->getIdByEmpleado($personal['id_personal']);

					$_POST = [
						'id'      => $idUsuario,
						'usuario' => $clave_empleado,
					];

					if (($usuario->editar())->hayError()) {
						$validar->anadirError("clave_empleado", $clave_empleado, "La clave de empleado no esta disponible");
					}

				}
			*/
			/*
				# Crear direccion
				if (isset( $direccion ))
				{
					unset($_POST);
					$Direccion = new Direccion();
					$_POST = $direccion;
					$Direccion = $Direccion->agregar();
					if ( count($Direccion->datos["errores"]) == 0 )
						$personal['direccion_id'] = $Direccion->datos["respuesta"]["datos"]->id;
					else
						$validar->anadirArrayError( 'direccion', $Direccion->datos["errores"] );
				}
				*/
			$datosAntes = (new Movimientos())->getJsonDatosEmpleado($id); //almacena los datos antes del cambio

			$dbGlobal->qqUpdate("RecursosHumanos.Personal", $personal);
			self::saveAplicaHorario($id, $aplicaHorario, date('Y-m-d'));
			# Telefonos
			$dbGlobal->run("
					DELETE FROM Generales.Telefonos
					WHERE id_telefono IN (
							SELECT telefono_id FROM Generales.Persona_Telefonos WHERE persona_id = $id and flag_persona = 0
						)");

			foreach ($telefonos as $telefono) {
				$dbGlobal->run("EXEC Generales.agregarTelefono $id, '$telefono[numero]', $telefono[tipo_telefono], 0");
			}

			#HIJOS
			(new Hijo())->setMany($id, $hijosArray);

			#ESCOLARIDAD
			(new Escolaridad())->setMany($id, $escolaridadArray);

			#----------------------------------------------------------------------------------
			#Datos Fscales
			$idDatosFiscales = $dbGlobal->getValue("select id_datosFiscales from RecursosHumanos.Personal_DatosFiscales where personal_id = $id");

			$audit->query(__METHOD__, "SELECT * FROM RecursosHumanos.Personal_DatosFiscales WHERE personal_id = $id");

			$dbGlobal->qqUpdate("RecursosHumanos.Personal_DatosFiscales", [
				'tipoPersonaFiscal_id' => $tipoPersonaFiscal_id,
				'id_datosFiscales'     => $idDatosFiscales,
				'razonSocial'          => $razonSocial,
				'rfc'                  => $rfc,
				'personal_id'          => $id,
			]);

			//almacena los datos antes del cambio
			$datosDespues = (new Movimientos())->getJsonDatosEmpleado($id);

			if ($datosAntes != $datosDespues) {
				//guardar el Movimiento para que se use como Historial
				$dbGlobal->qqInsert("RecursosHumanos.PersonalHistorial", [
					"personal_id"        => $id,
					"datosAntes"         => $datosAntes,
					"datosDespues"       => $datosDespues,
					"usuarioModifico_id" => $_SESSION['id'],
				]);
			}

			if ($validar->hayError()) {
				$dbGlobal->cancelarTransaccion();
				$this->errores = $validar->infoErrores();
			} else {
				$dbGlobal->guardarTransaccion();

				$audit->check();

				$this->datos["datos"]   = ['id' => $id];
				$this->datos["mensaje"] = "Datos exitosamente cargados";
			}

		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function validarArchivo() {
		global $dbGlobal;
		$_POST   = $_REQUEST;
		$validar = new ValidacionFormat("post");

		unset($_SESSION['RecursosHumanos']['AltaEmpleado']);

		if (!isset($_FILES['documento']['tmp_name']) || $_FILES['documento']['tmp_name'] == "") {
			$validar->anadirError("documento", "", "Error al cargar el archivo");
		} else {
			$datosCarga = new AltaEmpleado($_FILES['documento']['tmp_name']);
			$empleados  = $datosCarga->analizar();
		}

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$errorCargaEmpleado = false;
			foreach ($empleados as $key => $empleado) {
				$validarEmpleado = new Principal();
				$_GET['clave']   = $empleado['clave'];
				$e               = $validarEmpleado->listar();
				if (sizeof($e->datos['respuesta']['Empleados']) > 0) {
					$empleados[$key]["error"] = [
						'mensaje'  => 'La clave del usuario ya existe.',
						'detalles' => $e->datos['respuesta']['Empleados'][0],
					];

					$errorCargaEmpleado = true;
				}

				$empleados[$key]['nombreCompleto'] = "$empleado[nombre] $empleado[ap_paterno] $empleado[ap_materno]";
			}

			if (!$errorCargaEmpleado) {
				$_SESSION['RecursosHumanos']['AltaEmpleado'] = $empleados;
			}

			$this->datos["datos"]   = $empleados;
			$this->datos["mensaje"] = "Archivo cargador, verifique que los datos sean correctos.";

		}
		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function guardarArchivo() {
		set_time_limit(0);
		global $dbGlobal;
		$datos   = $errores   = [];
		$_POST   = $_REQUEST;
		$validar = new ValidacionFormat("post");

		if (!isset($_SESSION['RecursosHumanos']['AltaEmpleado'])) {
			$validar->anadirError("AltaEmpleado", "", "No se han cargado empleados o el archivo contiene errores");
		}

		if ($validar->hayError()) {
			$errores = $validar->infoErrores();
		} else {

			foreach ($_SESSION['RecursosHumanos']['AltaEmpleado'] as $key => $empleado) {
				unset($_POST);
				$_POST = [
					'nombre'           => $empleado['nombre'],
					'apellido_paterno' => $empleado['ap_paterno'],
					'apellido_materno' => $empleado['ap_materno'],
					'clave_empleado'   => $empleado['clave'],
					'rfc'              => $empleado['rfc'],
				];

				$datos["datos"][] = $this->altaPersonal();
			}
			$datos["mensaje"] = "Archivo guardado";

		}
		return new RespuestaJson(
			array("respuesta" => $datos, "errores" => $errores),
			$this->seccion
		);
	}

	public function generaExpediente() {
		ini_set('max_execution_time', 0);
		$validar  = new ValidacionFormat("get");
		$empleado = $validar->v_claveForanea(true, "empleado");
		if ($validar->hayError()) {
			$this->redireccion('errores/505');
		} else {
			$datosEnvio = $this->detallesEmpleado($empleado);
			return new RespuestaXlsx("RecursosHumanos/Empleados/generarExpediente.php", $datosEnvio);
		}
	}

	public function setCasoEmergencia() {
		global $dbGlobal;
		global $__sam;
		$datos = $errores = [];
		// $_POST = $_REQUEST;
		$validar = new ValidacionFormat("post");

		$nombreEmergencia   = $validar->v_texto(false, "nombreEmergencia");
		$telefonoEmergencia = $validar->v_texto(false, "telefonoEmergencia");
		$telefonoPersonal   = $validar->v_texto(false, "telefonoPersonal");

		#---------------------------------------------------------
		# Token valido
		$token_form = $validar->v_tokenform(true, "token_form");
		if (!$validar->hayError()) {
			$storage = $__sam->IdStorage->get($token_form ?? null);
			if (is_null($storage)) {
				$validar->anadirError("token_form", "", "Token invalido o Erroneo !!");
			}

		}

		if ($validar->hayError()) {
			$errores = $validar->infoErrores();
		} else {

			$dbGlobal->qqUpdate('RecursosHumanos.Personal', [
				'id_personal'        => $storage['id'],
				"nombreEmergencia"   => $nombreEmergencia,
				"telefonoEmergencia" => $telefonoEmergencia,
			]);

			$dbGlobal->run
				(
				"DELETE FROM
						Generales.Telefonos
					WHERE
						id_telefono IN ( SELECT telefono_id FROM Generales.Persona_Telefonos WHERE persona_id = $storage[id] and flag_persona = 0 and tipo = 3 )"
			);

			$dbGlobal->run("EXEC Generales.agregarTelefono $storage[id], $telefonoPersonal, 3, 0");

			$datos["datos"]   = $storage['id'];
			$datos["mensaje"] = "Datos actualizados correctamente";
		}

		return $this->json(
			array("respuesta" => $datos, "errores" => $errores),
			$this->seccion
		);
	}

	public function cargaImagenPerfil() {
		global $__sam;

		$datos = $errores = [];

		// $_POST = $_REQUEST;
		$validar = new ValidacionFormat("post");
		// $datos = $__sam->IdStorage->get( $_POST['token_form'] ?? '' );

		#---------------------------------------------------------
		# Token valido
		$token_form = $validar->v_tokenform(true, "token_form");
		if (!$validar->hayError()) {
			$storage = $__sam->IdStorage->get($token_form ?? null);
			if (is_null($storage)) {
				$validar->anadirError("token_form", "", "Token invalido o Erroneo !!");
			}

		}

		if ($validar->hayError()) {
			$errores = $validar->infoErrores();
		} else {

			$perfil = new Perfil();
			$perfil->setImagenPerfil(
				$perfil->getUserId($storage['id']),
				$_POST['imagenPerfil']
			);

			if (sizeof($perfil->errores)) {
				$validar->combinarErrores($datosGeneralesCliente->datos["errores"]);
			}

			$datos["datos"]   = $storage['id'];
			$datos["mensaje"] = "Archivo cargado correctamente.";
		}

		return new RespuestaJson(
			array("respuesta" => (object) $datos, "errores" => $errores),
			$this->seccion
		);
	}

	public function claveEmpleadoDisponible($id_personal, $clave_empleado) {
		global $dbGlobal;
		return $dbGlobal->getValue("
					SELECT
						COUNT(*)
					FROM RecursosHumanos.Personal
					WHERE id_personal <> $id_personal AND clave_empleado = '$clave_empleado'") == 0;

	}

	public function getImagenEmpleado() {
		$datos = $errores = [];

		$validar    = $this->setValidacion("get");
		$idEmpleado = $validar->v_clavePrincipal(true, "id");

		if ($validar->hayError()) {
			$errores = $validar->infoErrores();
		} else {

			$perfil                   = new Perfil();
			$datos['datos']['imagen'] = $perfil->getImagenPerfil($perfil->getUserId($idEmpleado), 428, 500);

		}

		return $this->json(
			array("respuesta" => $datos, "errores" => $errores),
			$this->seccion
		);

	}

	static function existeEmpleado(int $id) {
		global $dbGlobal;
		$id = intval($id);
		return $dbGlobal->getValue("
				SELECT
					count(id_personal)
				FROM RecursosHumanos.Personal
				WHERE id_personal = $id") != 0;
	}

	static function getClaveEmpleado(int $id) {
		global $dbGlobal;
		return $dbGlobal->getValue("
				SELECT
					clave_empleado
				FROM RecursosHumanos.Personal
				WHERE id_personal = $id");
	}

	static function getIdPersonal(string $claveEmpleado) {
		global $dbGlobal;
		return $dbGlobal->getValue("
				SELECT
					id_personal
				FROM RecursosHumanos.Personal
				WHERE clave_empleado = RIGHT('00000'+'$claveEmpleado', 5 )");
	}

	public function antiguedad(int $personal_id, string $fecha_termino = null) {
		global $dbGlobal;
		// Aun no se implemeta la fecha de termino
		$antiguedad = $dbGlobal->execProc('RecursosHumanos.empleadoAntiguedad', [
			'empleadoId'   => $personal_id,
			'fechaTermino' => null,
		]);
		return $antiguedad[0];
	}

	static function hayBaja(int $personalId) {
		global $dbGlobal;

		$estado = $dbGlobal->getValue("
				SELECT
					estatusLaboral_id
				FROM RecursosHumanos.Personal
				WHERE id_personal = $personalId");

		return $estado == 1 ? false : true;
	}

	static function saveAplicaHorario($idPersonal, $valor, $fecha) {
		global $dbGlobal;

		$actual = self::getAplicaHorario($idPersonal, $fecha);

		if ($actual !== $valor) {
			self::insertAplicaHorario($idPersonal, $valor, $fecha);
		}
	}

	static function getAplicaHorario($idPersonal, $fecha) {
		global $dbGlobal;

		return $dbGlobal->getValue("select RecursosHumanos.getAplicaFechaEmpleadoHorario('$fecha', $idPersonal) as aplicaHorario");
	}

	static function insertAplicaHorario($idPersonal, $valor, $fecha) {
		global $dbGlobal;

		$dbGlobal->run("insert into RecursosHumanos.personalAplicaHorariosHistorial
			(
				personal_id,
				aplicaHorario,
				fecha,
				creador_id
			)
			values
			(
				$idPersonal,
				$valor,
				'$fecha',
				$_SESSION[id]
			)");
	}
}
?>
