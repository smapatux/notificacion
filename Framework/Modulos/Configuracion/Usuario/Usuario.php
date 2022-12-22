<?php
/**
 *
 */
namespace Modulos\Configuracion\Usuario;

# Herramientas
use Predis\Client;
use \Controlador\Respuestas\Respuesta;
use \Controlador\Respuestas\RespuestaJson;
use \Controlador\__Base;
use \Herramientas\DbFormat as extraDb;
use \Herramientas\Generales\DataUser;

# Respuestas disponibles
use \Herramientas\ValidacionFormat;
use \Herramientas\VariablesGlobales as SG;
use \Modulos\Configuracion\ControlModulos;
use \Modulos\Configuracion\Grupos\Grupos;
use \Modulos\Configuracion\Grupos\Usuarios as UsuarioGrupo;
use \Modulos\Configuracion\Usuario\Modulos;
use \Respect\Validation\Exceptions\ValidationException;
use \Respect\Validation\Validator as validacion;

class Usuario extends __Base {

	private $_cache;
	private $datos;
	private $errores = array();
	private $seccion = 'Configuracion';

	#Validadores
	private $validarPassword;
	private $validarUsername;

	/*
		Ver si son necesarios o mejor usar parametros de funciones para pasar los datos
		private $clave; #Id empleado
		private $usuario; #Nombre usado para logueo
		private $password; #Nombre usado para logueo
	*/

	function __construct()
	{
		$this->datos = new \stdClass;
		$this->validarPassword = validacion::password();
		$this->validarUsername = validacion::username();

		$this->_cache = new Client([
			'scheme'   => REDIS_SCHEME,
			'host'     => REDIS_HOST,
			'port'     => REDIS_PORT,
			'password' => REDIS_PASS,
		]);
	}

	public function listar() {
		$plantilla = "Configuracion/Usuarios/Usuarios.html";
		$datos     = array();
		return new Respuesta($plantilla, $datos, $this->seccion);
	}

	public function datosUsuario() {
		global $dbGlobal;
		$validar = new ValidacionFormat("get");
		$id      = $validar->v_clavePrincipal(false, "id");

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$formatDb = new extraDb($filtros);
			if (!empty($id)) {
				$formatDb->where("u.id", $id, "AND");
			}

			$datosArray = $dbGlobal->getArray("SELECT
							u.id as idUsuario,
							p.id as idPersonal
							,estatus
							,clave_empleado
							,nombreCompleto
							,p.nombre
							,p.apellidoPaterno
							,p.apellidoMaterno
							,email
							,clave_electoral
							,curp
							,fechaNacimiento
							,observacion
							,comentario
							,esExterno
							,esCliente
							,ingreso_fecha
							,[Genero.id]
							,[Genero.nombre]
							,[Titulo.id]
							,[Titulo.nombre]
							,[EstadoCivil.id]
							,[EstadoCivil.nombre]
							,[DireccionLaboral.id]
    						,[DireccionLaboral.nombre]
							,[SubDireccion.id]
							,[SubDireccion.nombre]
							,[Area.id]
							,[Area.nombre]
							,[Puesto.id]
							,[Puesto.nombre]
							,[Profesion.id]
							,[Profesion.nombre]
							,[Especialidad.id]
							,[Especialidad.nombre]
							,[EstatusLaboral.id]
							,[EstatusLaboral.nombre]
							,[EmpresaDivision.id]
							,[EmpresaDivision.nombre]
							,[DivisionTecnica.id]
							,[DivisionTecnica.nombre]
							,razonSocial
							,rfc
							,tipoPersonaFiscal_id
						from usuarios u
							inner join RecursosHumanos.v_Personal p
						ON u.id_empleado=p.id
							left join RecursosHumanos.Personal_DatosFiscales df
						ON df.personal_id=p.id" . $formatDb->getConditionComplete());

			$datosArray  = $formatDb->queryResult2Object($datosArray);
			$this->datos = $datosArray;

			foreach ($datosArray as $llave => $usuarios) {
				if (isset($_GET["infoBasica"]) && $_GET["infoBasica"] === false) {
					$personalId = $usuarios->idPersonal;
					$idUsuario  = $usuarios->idUsuario;

					$datosArray[$llave]->telefonos = array();

					$telefonos = $dbGlobal->getArray("SELECT
								[id]
								,[numero]
								,[Personal.id]
								,[TelefonoTipo.id]
								,[TelefonoTipo.nombre]
						 FROM RecursosHumanos.v_Personal_Telefonos WHERE [Personal.id] = $personalId");
					$telefonos                      = $formatDb->queryResult2Object($telefonos);
					$this->datos[$llave]->Telefonos = $telefonos;

					$direccion = $dbGlobal->getArray("select * from AtencionPublico.v_Direcciones where id=(select direccion_id  from RecursosHumanos.Personal where id_personal=$personalId)");

					$direccion                      = $formatDb->queryResult2Object($direccion);
					$this->datos[$llave]->Direccion = $direccion[0] ?? array();

					$grupos = $dbGlobal->getArray("SELECT
								ug.idUsuario
								,sg.Descripcion
								,sg.Estatus
								,sg.Grupo
								,sg.alias
								,sg.IDGrupo
								FROM Sistema_Usuario_Grupos ug
								INNER JOIN Sistema_Grupos sg ON ug.IDGrupo=sg.IDGrupo
								WHERE ug.idUsuario= $idUsuario");
					$this->datos[$llave]->Grupos = $grupos ?? array();
				} else {
					$this->datos[$llave]->Grupos    = array();
					$this->datos[$llave]->Direccion = array();
					$this->datos[$llave]->Telefonos = array();
				}
			}

		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function detalles() {
		global $dbGlobal;

		try
		{
			$id     = validacion::intVal()->setName('id')->check(SG::GET('id')) ? SG::GET('id') : 0;
			$valido = true;
		} catch (NestedValidationException $exception) {
			$this->errores[] = $exception->getMessages();
			$valido          = false;
		}

		if ($valido) {
			unset($_GET);
			$_GET["id"] = $id;
			$usuario    = $this->buscar();

			if (count($usuario->datos["respuesta"]->Usuarios)) {
				$usuario              = $usuario->datos["respuesta"]->Usuarios[0];
				$this->datos->Usuario = $usuario;

				$user_session = new DataUser();

				//SIstemas usuario
				$contorlModulos        = new ControlModulos();
				$sistemas              = $contorlModulos->getSistemas();
				$this->datos->Sistemas = $sistemas->datos["respuesta"]["Sistemas"];
				foreach ($this->datos->Sistemas as $key => $sistema) {
					$idUsuario                                    = $id;
					$idSistema                                    = $sistema['idSistema'];
					$this->datos->Sistemas[$key]['tienePermisos'] = $dbGlobal->getValue("select dbo.usuarioTienePermisos($idSistema, $idUsuario)");
				}

				//Modulos usuario
				$modulos              = new Modulos();
				$modulos              = $modulos->listar();
				$this->datos->Modulos = $modulos->datos["respuesta"]["Modulos"];

				//Grupos de usuarios
				$this->datos->Grupos = $dbGlobal->getArray("SELECT
							id,
							Grupo,
							Descripcion,
							Estatus
						FROM dbo.getGruposDelUsuario( $id )");

			} else {
				$errores = new \Modulos\Home\Principal\Errores();
				return $errores->error500("Revise los parametros enviados.");
			}
		} else {
			$errores = new \Modulos\Home\Principal\Errores();
			return $errores->error500("Revise los parametros enviados.");
		}

		$plantilla = "Configuracion/Usuarios/detalle.html";
		return new Respuesta($plantilla, $this->datos, $this->seccion);
	}

	#Limpiar esta funcion, tiene validaciones hecas a madrazo.
	public function obtenerTodos() {
		global $dbGlobal;

		try
		{
			$registrado     = validacion::optional(validacion::regex('/^(1|0)$/'))->setName('registrado')->check(SG::GET('registrado')) ? SG::GET('registrado') : '';
			$usuario        = validacion::optional(validacion::alnum())->setName('Usuario')->check(SG::GET('Usuario')) ? SG::GET('Usuario') : '';
			$idEmpleado     = validacion::optional(validacion::intVal())->setName('IDEmpleado')->check(SG::GET('IDEmpleado')) ? SG::GET('IDEmpleado') : '';
			$nombreCompleto = validacion::optional(validacion::alnum())->setName('NombreCompleto')->check(SG::GET('NombreCompleto')) ? SG::GET('NombreCompleto') : '';

			$valido = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$query = "";
			if ($registrado != '') {
				if ($registrado == 1) {
					$query = "e.IDEmpleado IN (SELECT IDEmpleado FROM empleados_acceso)";
				} else {
					$query = "e.IDEmpleado NOT IN (SELECT IDEmpleado FROM empleados_acceso)";
				}

			}

			$filtroLocal = "";
			$formatDb    = new extraDb($filtroLocal);
			if (!empty($usuario)) {
				$formatDb->where("ea.Usuario", $usuario, "OR");
			}

			if (!empty($idEmpleado)) {
				$formatDb->where("e.IDEmpleado", $idEmpleado, "OR");
			}

			if (!empty($nombreCompleto)) {
				$formatDb->like("e.Nombre+' '+e.Apellidos", $nombreCompleto, "OR");
			}

			if (!empty($query)) {
				if (!empty($filtroLocal)) {
					$query = "$query AND ($filtroLocal)";
				}

				$query = "WHERE " . $query;
			}

			$this->datos->Usuarios = new \stdClass;
			$this->datos->Usuarios = $dbGlobal->getArray("SELECT
						ISNULL(e.clave_empleado, 0)  AS IDEmpleado,
						users.id AS idUsuario,
						users.Usuario,
						dbo.empleadoNombreCompleto(e.id_personal) as NombreCompleto,
						IIF(users.Estatus = 1, 'ACTIVO', 'INACTIVO') as Estatus
					FROM RecursosHumanos.Personal e
					RIGHT JOIN usuarios users ON users.id_empleado = e.id_personal");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	/**
	 * Buscador de usuarios registrados al sistema
	 * @param int $_GET['idProveedor'] [id del proveedor]
	 * @return String formato json
	 */
	public function buscar() {
		global $dbGlobal;

		try
		{
			$id             = validacion::optional(validacion::intVal())->setName('id')->check(SG::GET('id')) ? SG::GET('id') : 0;
			$clave          = validacion::optional(validacion::alnum())->setName('clave')->check(SG::GET('clave')) ? SG::GET('clave') : 0;
			$usuario        = validacion::optional(validacion::alnum())->setName('Usuario')->check(SG::GET('Usuario')) ? SG::GET('Usuario') : '';
			$idEmpleado     = validacion::optional(validacion::intVal())->setName('IDEmpleado')->check(SG::GET('IDEmpleado')) ? SG::GET('IDEmpleado') : 0;
			$nombreCompleto = validacion::optional(validacion::alnum())->setName('NombreCompleto')->check(SG::GET('NombreCompleto')) ? SG::GET('NombreCompleto') : '';
			$estatus        = validacion::optional(validacion::boolVal())->setName('Estatus')->check(SG::GET('Estatus')) ? SG::GET('Estatus') : '';

			$valido = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$filtros  = "";
			$formatDb = new extraDb($filtros);

			if (!empty($id)) {
				$formatDb->where("u.id", $id, "OR");
			}

			if (!empty($clave)) {
				$formatDb->like("e.clave_empleado", $clave, "OR");
			}

			if (!empty($usuario)) {
				$formatDb->where("u.Usuario", $usuario, "OR");
			}

			if (!empty($idEmpleado)) {
				$formatDb->where("e.id_personal", $idEmpleado, "OR");
			}

			if (!empty($nombreCompleto)) {
				$formatDb->like("dbo.empleadoNombreCompleto(e.id_personal)", $nombreCompleto, "OR");
			}

			if ($estatus != '') {
				$formatDb->where("u.Estatus", $estatus, "OR");
			}

			$strWhere = !empty($filtros) ? " WHERE $filtros" : "";

			$this->datos->Usuarios = $dbGlobal->getArray("SELECT
						ISNULL(e.clave_empleado, 0)  AS clave,
						dbo.empleadoNombreCompleto(e.id_personal) as NombreCompleto,
						u.Usuario,
						u.Estatus,
						u.id as id,
						e.id_personal as id_personal
					FROM RecursosHumanos.Personal e
					RIGHT JOIN usuarios u ON u.id_empleado = e.id_personal $strWhere");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	function editar() {
		global $dbGlobal;

		$datos   = [];
		$errores = [];

		$validar = $this->setValidacion("post");

		$id                = $validar->v_clavePrincipal(true, "id");
		$estatus           = $validar->v_checkBox(false, "Estatus");
		$usuario           = $validar->v_userName(true, "usuario");
		$password          = $validar->v_password(false, "password");
		$confirmarPassword = $validar->v_password(false, "confirmarPassword");

		if ($password != $confirmarPassword) {
			$validar->anadirError("password", "", "La confirmacion no coincide");
			$validar->anadirError("confirmarPassword", "", "La confirmacion no coincide");
		}

		if ($validar->hayError()) {
			$errores = $validar->infoErrores();
		} else {
			#Validar si el nombre nuevo de usuario no se ha usado, si es usado por el mismo, no importa
			$nombreUsado = $dbGlobal->getValue("SELECT id FROM usuarios WHERE id <> $id and usuario = '$usuario'");

			if ($nombreUsado != 0) {
				$validar->anadirError("usuario", $usuario, "Usuario en uso, no disponible!");
			}

			if (count($validar->infoErrores()) == 0) {
				$actualizacion = ['id' => $id];

				if (!empty($usuario)) {
					$actualizacion["usuario"] = $usuario;
				}

				if (!empty($password)) {
					$actualizacion["nip"] = password_hash($password, PASSWORD_DEFAULT);
				}

				$actualizacion["estatus"] = !empty($estatus);

				$dbGlobal->qqUpdate("usuarios", $actualizacion);

				$datos["mensaje"] = "Cambio realizado con exito.";

				$this->_cache->hdel("user:$id", "usuario");
			}

			$errores = $validar->infoErrores();
		}

		return $this->json(
			array("respuesta" => $datos, "errores" => $errores),
			$this->seccion
		);
	}

	function getIdByEmpleado($id_personal) {
		global $dbGlobal;
		$id_personal = intVal($id_personal);
		return $dbGlobal->getValue("SELECT ISNULL(id, 0) FROM dbo.usuarios WHERE id_empleado = $id_personal");
	}

	// OBSOLETA - ELIMINAR
	function cambioPassword() {
		global $dbGlobal;

		try {
			$this->validarPassword->check(SG::POST('password'));
			$this->validarPassword->check(SG::POST('nuevoPassword'));
			$this->validarPassword->check(SG::POST('confirmarPassword'));

			if (SG::POST('nuevoPassword') != SG::POST('confirmarPassword')) {
				$this->errores[] = "La nueva contraseña no coincide.";
				$valido          = false;
			} else {
				$valido = true;
			}

		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$oldPassword = SG::POST('password');
			$newPassword = SG::POST('nuevoPassword');
			$usuario     = $dbGlobal->getRow("SELECT TOP 1 Nip, id FROM empleados_acceso WHERE id = $_SESSION[idEmpleado]");

			if (password_verify($oldPassword, $usuario['Nip'])) {
				$usuario["Nip"] = password_hash($newPassword, PASSWORD_DEFAULT);
				$dbGlobal->qqUpdate("empleados_acceso", $usuario);

				$this->datos->Usuario          = new \stdClass;
				$this->datos->Usuario->Mensaje = "Cambio realizado con exito.";
			} else {
				$this->errores[] = "Verifique la contraseña";
			}

		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	function crearCuenta() {
		global $dbGlobal;

		$datos = [];

		$validar = $this->setValidacion("post");

		$idEmpleado        = $validar->v_clavePrincipal(true, "idEmpleado");
		$usuario           = $validar->v_userName(true, "usuario");
		$password          = $validar->v_password(true, "password");
		$confirmarPassword = $validar->v_password(true, "confirmarPassword");

		if ($password != $confirmarPassword) {
			$validar->anadirError("password", "", "La confirmacion no coincide");
			$validar->anadirError("confirmarPassword", "", "La confirmacion no coincide");
		}

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			if ($this->existeUsuario($usuario, $idEmpleado)) {
				$validar->anadirError("usuario", "", "El usuario ya existe");
			}

			if (!$this->existeEmpleado($idEmpleado)) {
				$validar->anadirError("idEmpleado", "", "El empleado no existe");
			}

			if (!$validar->hayError()) {
				$id = $this->agregarCuentaUsuario($usuario, $password, $idEmpleado);

				if ($id) {
					#Insert usuario en grupo
					$grupo = new Grupos();
					unset($_GET);
					$_GET['nombre'] = 'basico';
					$grupo          = $grupo->listar();

					if (isset($grupo->datos["respuesta"]["Grupos"][0])) {
						$grupo = $grupo->datos["respuesta"]["Grupos"][0];
						$_POST = [
							'grupo' => $grupo['id'],
							'id'    => $id,
						];
						$usuario = new UsuarioGrupo();
						$usuario = $usuario->guardar();
					}

					$datos["Usuario"]["id"]      = $id;
					$datos["Usuario"]["Mensaje"] = "Usuario agregado correctamente.";
				} else {
					$validar->anadirError("cuentaNueva", "", "No se pudo agregar el usuario, contacte al administrador.");
				}

			}

			$this->errores = $validar->infoErrores();
		}

		return $this->json(
			array("respuesta" => $datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	/*
		function crearCuenta()
		{
			global $dbGlobal;

			try{
				$idEmpleado = validacion::texto()->setName('idEmpleado')->check( SG::POST('idEmpleado') ) ? SG::POST('idEmpleado') : 0;
				$usuario = $this->validarUsername->setName('usuario')->check( SG::POST('usuario') ) ? SG::POST('usuario') : '' ;
				$password = $this->validarPassword->setName('password')->check( SG::POST('password') ) ? SG::POST("password") : '';
				$confirmarPassword = $this->validarPassword->setName('confirmarPassword')->check(SG::POST('confirmarPassword')) ? SG::POST('confirmarPassword') : '';

				if ( !empty( $password ) and SG::POST('password') != SG::POST('confirmarPassword'))
				{
					$this->errores[] = "La contraseña no coincide.";
					$valido = false;
				}
				else $valido = true;
			}
			catch(ValidationException $exception)
			{
				$this->errores[] = $exception->getMainMessage();
				$valido = false;
			}

			// Validacion de nombre de usuario
			if($valido)
			{
				#$idEmpleado = empty($idEmpleado) ? '0' : $idEmpleado;
				# Validacion en datos a insertar
				if ( $this->existeUsuario( $usuario, $idEmpleado ) )
					$this->errores[] = "El usuario ya existe";

				// Esta validacion se revisa si se envia la clave del empleado, es opcional.

				if ( !$this->existeEmpleado( $idEmpleado ) )
					$this->errores[] = "El empleado no existe";

				# Si no existen errores, agrega el nuevo usuario
				if ( !count( $this->errores ) )
				{
					$this->datos->Usuario = new \stdClass;
					$id = $this->agregarCuentaUsuario($usuario, $password, $idEmpleado);
					if( $id )
					{
						$this->datos->Usuario->id = $id;
						$this->datos->Usuario->Mensaje = "Usuario agregado correctamente.";
					}
					else
						$this->errores[] = "No se pudo agregar el usuario, contacte al administrador.";
				}
			}

			return new RespuestaJson(
					array( "respuesta" => $this->datos, "errores" => $this->errores ),
					$this->seccion
				);
		}
		*/

	private function existeEmpleado($clave) {
		global $dbGlobal;
		return $dbGlobal->getValue("SELECT TOP 1 id_personal as id FROM RecursosHumanos.Personal WHERE id_personal = $clave");
	}

	private function existeUsuario($usuario = "", $clave = 0) {
		global $dbGlobal;
		return $dbGlobal->getValue("SELECT TOP 1
					u.id
				FROM usuarios u
				INNER JOIN RecursosHumanos.Personal e ON e.id_personal = u.id_empleado
				WHERE u.usuario = '$usuario' OR e.clave_empleado = '$clave'");
	}

	private function agregarCuentaUsuario($usuario, $password, $clave, $estatus = true) {
		global $dbGlobal;

		//Validacion pendiente
		$nip = password_hash($password, PASSWORD_DEFAULT);

		$idEmpleado = $dbGlobal->getValue("SELECT TOP 1 id_personal as id FROM RecursosHumanos.Personal WHERE id_personal = $clave");

		$datos = array(
			'id_empleado' => $idEmpleado,
			'usuario'     => $usuario,
			'estatus'     => $estatus,
			'nip'         => $nip,
		);
		return $dbGlobal->qqInsert("usuarios", $datos);
	}

}

?>
