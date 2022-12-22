<?php 

	namespace Modulos\Configuracion\Usuario;

	# Herramientas
	use \Herramientas\Log as Log;
	use \Herramientas\VariablesGlobales as SG;
	use \Herramientas\DbFormat as extraDb;
	
	use \Respect\Validation\Validator as validacion;
	use \Respect\Validation\Exceptions\ValidationException;
	# Respuestas disponibles
	use \Controlador\Respuestas\RespuestaJson as RespuestaJson;

	use Modulos\Configuracion\ControlModulos;

	/**
	* 
	*/
	class Permisos
	{
		
		private $datos = array();
		private $errores = array();
		private $seccion = 'Configuracion';
		
		function __construct() {
			
		}

		public function actualizar()
		{
			global $dbGlobal;
			
			try
			{
				$id = validacion::intVal()
					->setName('usuario')
					->check( SG::POST('usuario') ) ? $_POST['usuario'] : 0;

				$sistema = validacion::intVal()
					->setName('sistema')
					->check( SG::POST('sistema') ) ? $_POST['sistema'] : 0;

				if(isset($_POST["atributos"]))
					foreach ($_POST["atributos"] as $attr)
					{

						validacion::intVal()
							->setName('atributo')
							->check( isset( $attr ) ? $attr : 0 ) ? $attr : 0;

						// Validacion atributo existe en sistema
						$attr = $dbGlobal->getValue("
							SELECT TOP 1
								idAtributo 
							FROM Sistema_Atributos sa
							INNER JOIN Sistema_Modulos sm ON sm.id = sa.IDModulo
							WHERE id_sistema = $sistema AND IDAtributo = $attr");

						validacion::intVal()
							->positive()
							->setName('atributo')
							->check( isset( $attr ) ? $attr : 0 );
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
				$dbGlobal->run("
					DELETE FROM
						Sistema_Usuario_Permisos
					WHERE idUsuario = $id 
					AND idAtributo in (
						SELECT
							idAtributo 
						FROM Sistema_Atributos sa
						INNER JOIN Sistema_Modulos sm ON sm.id = sa.IDModulo
						WHERE id_sistema = $sistema
					)");

				#Agregando nuevos
				if (isset($_POST["atributos"]) && count($_POST["atributos"]))
					foreach ($_POST["atributos"] as $attr)
					{
						$dbGlobal->run("INSERT INTO Sistema_Usuario_Permisos VALUES ($id, $attr)");
					}
					

				$this->datos["Mensaje"] = "Actualizacion exitosa.";
				$dbGlobal->guardarTransaccion();

				$this->datos["tienePermisos"] = $dbGlobal->getValue("select dbo.usuarioTienePermisos($sistema,$id)");
				
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
				$IdEmpleado = validacion::intVal()
					->setName('IdEmpleado')
					->check( SG::POST('IdEmpleado') ) ? $_POST['IdEmpleado'] : 0;

				if(isset($_POST["permisos"]))
					foreach ($_POST["permisos"] as $detalle)
						validacion::intVal()
							->setName('idAtributo')
							->check( isset( $detalle['idAtributo'] ) ? $detalle['idAtributo'] : 0 );

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

				if ( isset($_POST["permisos"]) )
					foreach ($_POST["permisos"] as $key => $detalle)
						$_POST["permisos"][$key]["idUsuario"] = $idUsuario;

				#Borrando attr viejos.
				$dbGlobal->run("DELETE FROM Sistema_Usuario_Permisos WHERE idUsuario = $idUsuario");

				#Agregando nuevos
				if (isset($_POST["permisos"]) && count($_POST["permisos"]))
					$dbGlobal->qqInsert("Sistema_Usuario_Permisos", $_POST["permisos"]);

				$this->datos["Usuario"]["Mensaje"] = "Permisos asignados correctamente";

			}


			return new RespuestaJson(
					array( "respuesta" => $this->datos, "errores" => $this->errores ),
					$this->seccion
				);

		}

		public function listar()
		{
			global $dbGlobal;
			
			try
			{
				$id = validacion::intVal()->setName('id')->check( SG::GET('id') ) ? $_GET['id'] : 0;
				$sistema = validacion::intVal()->setName('sistema')->check( SG::GET('sistema') ) ? $_GET['sistema'] : 0;
				$valido = true;
			}
			catch(ValidationException $exception)
			{
				$this->errores[] = $exception->getMainMessage();
				$valido = false;
			}

			if( $valido )
			{
				
				$this->datos["Atributos"] = $dbGlobal->getArray("
					SELECT
							attr_sistema as idAttrSistema,
							ISNULL(attr_usuario,0) as idAttrUsuario,
							IDModulo,
							Atributo,
							Ruta
					FROM dbo.getPermisosSistemaUsuario($sistema,$id)
					LEFT JOIN(
						SELECT 
							IDAtributo,
							IDModulo,
							Atributo,
							dbo.getRutaModuloPadre(IDModulo, '') AS Ruta
						FROM Sistema_Atributos
						INNER JOIN Sistema_Modulos sm on sm.id = IDModulo
						WHERE sm.id_sistema = $sistema
						) T0 ON T0.IDAtributo = attr_sistema");
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
			
			return $dbGlobal->getArray("SELECT
					attr_usuario,
					Ruta,
					Atributo
				FROM dbo.getPermisosUsuario( $idUsuario )");			
		}


		public function validarPermisoUsuario()
		{
			global $dbGlobal;
			
			global $_Ejecutar;
			
			$datos = $dbGlobal->getRow("
				SELECT
					sa.IDAtributo
				FROM Sistema_Atributos sa
				LEFT JOIN Sistema_Usuario_Permisos sup ON sa.IDAtributo = sup.idAtributo
				LEFT JOIN Sistema_Grupos_Permisos sgp ON sa.IDAtributo = sgp.IDAtributo
				LEFT JOIN Sistema_Usuario_Grupos sug ON sug.IDGrupo = sgp.IDGrupo
				LEFT JOIN Empleados_Acceso ea ON (ea.id = sug.idUsuario or ea.id = sup.idusuario )
				WHERE
					ea.idEmpleado = $_SESSION[idEmpleado] AND
					sa.Ruta = '$_Ejecutar->clase' AND
					sa.Atributo = '$_Ejecutar->funcion'
				GROUP BY sa.IDAtributo ORDER BY sa.IDAtributo ");

			return isset($datos['IDAtributo']) && $datos['IDAtributo'] != 0;

		}


	}


 ?>