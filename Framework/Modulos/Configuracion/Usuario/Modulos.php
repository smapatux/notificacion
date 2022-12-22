<?php 

	namespace Modulos\Configuracion\Usuario;

	# Herramientas	
	use \Herramientas\VariablesGlobales as SG;
	
	use \Respect\Validation\Validator as validacion;
	use \Respect\Validation\Exceptions\ValidationException;
	
	# Respuestas disponibles
	use \Controlador\Respuestas\RespuestaJson as RespuestaJson;

	class Modulos
	{
		
		private $datos = array();
		private $errores = array();
		private $seccion = 'Configuracion';
		function __construct() {
			
		}

		public function listar()
		{
			global $dbGlobal;
			
			try
			{
				$id = validacion::intVal()->setName('id')->check(SG::GET('id')) ? SG::GET('id') : 0;
				$valido = true;
			}
			catch(ValidationException $exception)
			{
				$this->errores[] = $exception->getMainMessage();
				$valido = false;
			}

			if( $valido )
			{
				
				$this->datos["Modulos"] = $dbGlobal->getArray("SELECT 
						id_modulo as idModSistema,
						ISNULL(T0.idModulo,0) as idModUsuario,
						id_padre,
						Ruta,
						Alias,
						show_menu
					FROM dbo.getModulosHijos(0) gMH
					LEFT JOIN
						(
							SELECT
								idUsuario,
								idModulo
							FROM Sistema_Usuario_Modulos WHERE idUsuario = $id
						) T0 ON T0.idModulo = gMH.id_modulo");

				foreach ($this->datos["Modulos"] as $key => $modulo)
				{
					$a = explode("\\", $modulo["Ruta"]);
					unset($a[count($a)-1]);
					$this->datos["Modulos"][$key]["Ruta"] = implode("\\", $a);
				}
			}

			return new RespuestaJson(
					array( "respuesta" => $this->datos, "errores" => $this->errores ),
					$this->seccion
				);

		}

		public function actualizar()
		{
			global $dbGlobal;
			
			try
			{
				$id = validacion::intVal()->setName('usuario')->check( SG::POST('usuario') ) ? $_POST['usuario'] : 0;
				if(isset($_POST["modulos"]))
					foreach ($_POST["modulos"] as $key => $attr)
					{
						validacion::intVal()->setName('modulo')->check( isset( $attr ) ? $attr : 0 ) ? $attr : 0;
						// Validacion atributo existe en sistema

						$attr = $dbGlobal->getValue("SELECT TOP 1 id FROM Sistema_Modulos WHERE id = $attr");

						validacion::intVal()->positive()->setName('modulo')->check( isset( $attr ) ? $attr : 0 );
					}

				$valido = true;
			}
			catch(ValidationException $exception)
			{
				$this->errores[] = $exception->getMainMessage();
				$valido = false;
			}

			if ( $valido )
			{
				$dbGlobal->iniciarTransaccion();
				
				#Borrando attr viejos.
				$dbGlobal->run("DELETE FROM Sistema_Usuario_Modulos WHERE idUsuario = $id");
				#Agregando nuevos
				if (isset($_POST["modulos"]) && count($_POST["modulos"]))
				{
					foreach ($_POST["modulos"] as $key => $attr)
					{
						$dbGlobal->run("INSERT INTO Sistema_Usuario_Modulos VALUES ($id, $attr)");
					}					
				}

				$this->datos["Mensaje"] = "Actualizacion exitosa.";
				$dbGlobal->guardarTransaccion();

				#$this->datos["tienePermisos"] = $dbGlobal->getValue("select dbo.usuarioTienePermisos($sistema,$id)");
				
			}

			return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);
		}


		public function actualizarTodos()
		{
			global $dbGlobal;
			
			try
			{
				$IdEmpleado = validacion::intVal()->setName('IdEmpleado')->check( SG::POST('IdEmpleado') ) ? $_POST['IdEmpleado'] : 0;
				if ( isset($_POST["modulos"]) )
					foreach ($_POST["modulos"] as $key => $detalle) {
						validacion::intVal()->setName('idModulo')->check( isset( $detalle['idModulo'] ) ? $detalle['idModulo'] : 0 );
					}
				$valido = true;
			}
			catch(ValidationException $exception)
			{
				$this->errores[] = $exception->getMainMessage();
				$valido = false;
			}

			if( $valido )
			{
				
				$idUsuario = $dbGlobal->getValue("SELECT id FROM Empleados_Acceso WHERE IDEmpleado = $IdEmpleado");
				if ( isset($_POST["modulos"]) )
				foreach ($_POST["modulos"] as $key => $detalle) {
					$_POST["modulos"][$key]["idUsuario"] = $idUsuario;
				}

				#Borrando attr viejos.
				$dbGlobal->run("DELETE FROM
						Sistema_Usuario_Modulos
					WHERE idUsuario = $idUsuario");
				#Agregando nuevos

				if (isset($_POST["modulos"]) && count($_POST["modulos"]))
					$dbGlobal->qqInsert("Sistema_Usuario_Modulos", $_POST["modulos"]);
				$this->datos["Usuario"]["Mensaje"] = "Modulos asignados correctamente";
			}

			return new RespuestaJson(
					array( "respuesta" => $this->datos, "errores" => $this->errores ),
					$this->seccion
				);
		}

		//Consulta los permisos del usuario y los permisos heredados por grupos
		public function get( $idUsuario = 0 )
		{
			global $dbGlobal;
			return $dbGlobal->getArray("SELECT mod_usuario, Ruta, Alias, show_menu FROM dbo.getModulosUsuario( $idUsuario )");
		}


	}


 ?>