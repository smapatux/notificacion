<?php 

	namespace Modulos\Configuracion\Grupos;

	# Herramientas
	use \Herramientas\Log as Log;
	use \Herramientas\VariablesGlobales as SG;
	use \Herramientas\DbFormat as extraDb;
	
	use \Respect\Validation\Validator as validacion;
	use \Respect\Validation\Exceptions\ValidationException;
	# Respuestas disponibles
	use \Controlador\Respuestas\RespuestaJson as RespuestaJson;

	/**
	* 
	*/
	class Modulos
	{

		private $datos = array();
		private $errores = array();
		private $seccion = 'Configuracion';
		function __construct() { }

		public function listar()
		{
			global $dbGlobal;
			
			try
			{
				$valido = true;
			}
			catch(ValidationException $exception)
			{
				$this->errores[] = $exception->getMainMessage();
				$valido = false;
			}

			if( $valido )
			{
				

				#Borrando attr viejos.
				#$this->datos["Modulos"] = $dbGlobal->getArray("SELECT id_modulo, id_padre, Ruta, Alias, show_menu FROM dbo.getModulosHijos(0)");
				
				$this->datos["Modulos"] = $dbGlobal->getArray("SELECT
						id_modulo as idModSistema,
						ISNULL(s_gm.idModulo,0) as idModGrupo,
						id_padre,
						Ruta,
						Alias,
						show_menu
					FROM dbo.getModulosHijos(0) gMH
					LEFT JOIN Sistema_Grupos_Modulos s_gm ON s_gm.idModulo = gMH.id_modulo");

				foreach ($this->datos["Modulos"] as $key => $modulo)
				{
					$a = explode("/", $modulo["Ruta"]);
					unset($a[count($a)-1]);
					$this->datos["Modulos"][$key]["Ruta"] = implode("/", $a);
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
				$id = validacion::intVal()->setName('grupo')->check( SG::POST('grupo') ) ? $_POST['grupo'] : 0;
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
				$dbGlobal->run("DELETE FROM Sistema_Grupos_Modulos WHERE idGrupo = $id");
				#Agregando nuevos
				if (isset($_POST["modulos"]) && count($_POST["modulos"]))
				{
					foreach ($_POST["modulos"] as $key => $attr)
					{
						$dbGlobal->run("INSERT INTO Sistema_Grupos_Modulos VALUES ($id, $attr)");
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



	}


 ?>