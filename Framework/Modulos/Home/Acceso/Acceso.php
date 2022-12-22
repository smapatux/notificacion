<?php
	namespace Modulos\Home\Acceso;

	# Herramientas
	use Herramientas\Generales\DataUser;
	use \Controlador\Respuestas\Respuesta;

	# Validacion
	use \Controlador\Respuestas\RespuestaJson;

	# Respuestas disponibles
	use \Controlador\Router;
	use \Herramientas\Generales\LoginRetry;
	use \Herramientas\VariablesGlobales as SG;

	#use \Modulos\Home\Acceso\Permisos;
	use \Modulos\Configuracion\Usuario\Modulos;	
	#use \Modulos\Configuracion\Web\Menu;
	use \Modulos\RecursosHumanos\Empleados\Empleado;
	
	use \Respect\Validation\Exceptions\ValidationException;
	use \Respect\Validation\Validator as validacion;

	class Acceso
	{
		private $datos   = array();
		private $errores = array();
		private $seccion = 'Acceso';
		private $__path_api = "#^/*api/#i";

		function __construct() {}

		public function inicio()
		{		
			$uri = isset($_GET['uri']) ? $_GET['uri'] : '/';			
			# Revisar si la peticion es API o HTTP
			if (preg_match($this->__path_api, $uri, $matches))
			{
				$respuesta = $this->acceso();
			}		
			else
			{   # Redireccion a web login http			
				$respuesta = $this->loginHttp();
			}			
			return $respuesta;
		}

		public function loginHttp()
		{
			if (isset($_SESSION['logueado']) && !$_SESSION['logueado'])
			{
				$plantilla = "Acceso/Login/login.html";
				$datos     = array(
					"usuario" => "juan",
					"fecha"   => date('d/m/y'),
				);
			}
			else
			{
				$dataUser = new DataUser();			
				$sessionAnterior = $dataUser->existe($_SESSION['id'], "REQUEST_URI");

				// REQUEST_URI
				Router::redireccion(
					$sessionAnterior ?
					$dataUser->get($_SESSION['id'], "REQUEST_URI") :
					''
				);
			}
			return new Respuesta($plantilla, $datos ?? [], $this->seccion);
		}

		public function acceso()
		{
			global $dbGlobal;
			$usuario  = SG::POST('username');
			$password = SG::POST('password');

			try
			{
				validacion::alnum('áéíóúÁÉÍÓÚñÑ_+-*#/:.,@')->noWhitespace()->length(1, 15)->validate($usuario);								
				validacion::stringType()->notEmpty()->validate($password);				
				$valido = true;
			}
			catch (ValidationException $exception){
				$this->errores[] = "exception->getMainMessage()";
				$this->errores[] = "Usuario no logueado";
				$valido          = false;
			}			
			
			if ($valido)
			{				
				$loginRetry = new LoginRetry($usuario);
				if ($loginRetry->cuentaHabilitada($usuario) && self::validarUsuario($usuario, $password))
				{
					$loginRetry->del($usuario);

					$_SESSION['logueado'] = true;
					$this->datos["Login"] = "Ok";

					$_GET                         = ["id" => $_SESSION["idEmpleado"], "picture" => 1];
					$this->datos["datos"]["user"] = (new Empleado)->apiDetalles()->getDatos();

					$dataUser = new DataUser();
					$this->datos['datos']['redirect'] = $dataUser->existe($_SESSION['id'], "REQUEST_URI") ?
					$dataUser->get($_SESSION['id'], "REQUEST_URI") : '/';					

					#Desloguea usuarios en la misma cuenta.
					$dataUser      = new DataUser();
					$estadoSession = $dataUser->existe($_SESSION['id'], "estadoSession");
					if ($estadoSession) {
						$estadoSession = $dataUser->get($_SESSION['id'], "estadoSession");

						if ($estadoSession->session_id != session_id()) {
							$this->destruirSession($estadoSession->session_id);
						}
					}
					$dataUser->set($_SESSION['id'], "estadoSession", ['session_id' => session_id()]);
				}
				else
				{
					if ($loginRetry->counter === -1){
						$this->errores[] = 'Cuenta deshabilitada temporalmente por varios intentos fallidos';
					}

					$this->datos["try"]   = $loginRetry->get($usuario);
					$_SESSION['logueado'] = false;
					$this->datos["Login"] = 'Error';
					$this->errores[]      = 'Login incorrecto';
				}
			}
			else {
				$_SESSION['logueado'] = false;
			}

			return new RespuestaJson(
				["respuesta" => $this->datos, "errores" => $this->errores],
				$this->seccion
			);
		}

		private function validarUsuario($username, $password) {
			global $dbGlobal;
			$acceso = false;
			# Validacion en datos a insertar

			$usuario = $dbGlobal->getRow("SELECT TOP 1
						users.Nip,
						users.id as idUsuarios,
						users.id_empleado as IDEmpleado
					FROM usuarios users
					LEFT JOIN RecursosHumanos.Personal e ON e.id_personal = users.id
					WHERE Usuario = '$username' AND users.estatus = 1");

			if (count($usuario) == 0) {
				$this->errores[] = 'Login incorrecto';
			}

			#$this->errores[] = "El usuario no existe.";

			if (count($this->errores) == 0 && password_verify($password, $usuario['Nip'])) {
				# Obtener datos usuario
				$_SESSION['id']         = $usuario['idUsuarios'];
				$_SESSION['idEmpleado'] = $usuario['IDEmpleado'];

				#Permisos del usuario
				# No se utiliza, los permisos se consultan directamente en la DB
				// $permisos = new Permisos();
				// $_SESSION['permisos'] = $permisos->get( $_SESSION['id'] );

				#Modulos del usuario
				#Se quita, ningun usuario esta utilizando los modulos.
				// $modulos = new Modulos();
				// $_SESSION['modulos'] = $modulos->get( $_SESSION['id'] );

				$acceso = true;
			}

			return $acceso;
		}

		public function salir() {
			session_destroy();

			$this->datos["Logout"] = 'Ok';

			return new RespuestaJson(
				array("respuesta" => $this->datos, "errores" => $this->errores),
				$this->seccion
			);
		}

		public function logoutHttp() {
			session_destroy();
			Router::redireccion('login/');
		}

		#http://php.net/manual/es/function.session-destroy.php
		public function destruirSession($session_id_to_destroy) {
			// 1. commit session if it's started.
			if (session_id()) {
				session_commit();
			}

			// 2. store current session id
			session_start();
			$current_session_id = session_id();
			session_commit();

			// 3. hijack then destroy session specified.
			session_id($session_id_to_destroy);
			session_start();
			session_destroy();
			session_commit();

			// 4. restore current session id. If don't restore it, your current session will refer to the session you just destroyed!
			session_id($current_session_id);
			session_start();
			session_commit();
		}

	}
?>