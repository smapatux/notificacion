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
	class Permisos
	{
		private $datos = array();
		private $errores = array();
		private $seccion = 'Configuracion';
		function __construct() { }

		function listar()
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
				
				$this->datos["Atributos"] = $dbGlobal->getArray("SELECT
							attr_sistema as idAttrSistema,
							ISNULL(attr_grupo,0) as idAttrGrupo,
							IDModulo,
							Atributo,
							Ruta
						FROM dbo.getPermisosSistemaGrupo($sistema,$id)
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

		public function actualizar()
		{
			global $dbGlobal;
			
			try
			{
				$id = validacion::intVal()->setName('grupo')->check( SG::POST('grupo') ) ? $_POST['grupo'] : 0;
				$sistema = validacion::intVal()->setName('sistema')->check( SG::POST('sistema') ) ? $_POST['sistema'] : 0;
				if(isset($_POST["atributos"]))
					foreach ($_POST["atributos"] as $key => $attr)
					{
						validacion::intVal()->setName('atributo')->check( isset( $attr ) ? $attr : 0 ) ? $attr : 0;
						// Validacion atributo existe en sistema
						$attr = $dbGlobal->getValue("SELECT TOP 1 idAtributo 
							FROM Sistema_Atributos sa
							INNER JOIN Sistema_Modulos sm ON sm.id = sa.IDModulo
							WHERE id_sistema = $sistema AND IDAtributo = $attr");

						validacion::intVal()->positive()->setName('atributo')->check( isset( $attr ) ? $attr : 0 );
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
				$dbGlobal->run("DELETE FROM Sistema_Grupos_Permisos
					WHERE IDGrupo = $id 
					AND idAtributo in (SELECT
							idAtributo 
						FROM Sistema_Atributos sa
						INNER JOIN Sistema_Modulos sm ON sm.id = sa.IDModulo
						WHERE id_sistema = $sistema)");
				#Agregando nuevos
				if (isset($_POST["atributos"]) && count($_POST["atributos"]))
				{
					foreach ($_POST["atributos"] as $key => $attr)
					{
						$dbGlobal->run("INSERT INTO Sistema_Grupos_Permisos VALUES ($id, $attr)");
					}
					
				}

				$this->datos["Mensaje"] = "Actualizacion exitosa.";
				$dbGlobal->guardarTransaccion();

				$this->datos["tienePermisos"] = $dbGlobal->getValue("select dbo.grupoTienePermisos( $sistema, $id )");
				
			}

			return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);

		}

		public function guardar()
		{
			global $dbGlobal;
			
			try
			{
				$IDGrupo = validacion::intVal()->setName('IDGrupo')->check( SG::POST('IDGrupo') ) ? $_POST['IDGrupo'] : 0;
				if (isset($_POST["permisos"]))
				foreach ($_POST["permisos"] as $key => $detalle) {
					validacion::intVal()->setName('IDAtributo')->check( isset( $detalle['IDAtributo'] ) ? $detalle['IDAtributo'] : 0 );
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
				
				if (isset($_POST["permisos"]))
				foreach ($_POST["permisos"] as $key => $detalle)
					$_POST["permisos"][$key]["IDGrupo"] = $IDGrupo;
				
				#Borrando attr viejos.
				$dbGlobal->run("DELETE FROM Sistema_Grupos_Permisos WHERE IDGrupo = $IDGrupo");

				#Agregando nuevos
				if ( isset($_POST["permisos"]) && count( $_POST["permisos"] ) )
					$dbGlobal->qqInsert("Sistema_Grupos_Permisos", $_POST["permisos"]);
				
				$this->datos["Grupos"]["Mensaje"] = "Permisos asignados correctamente";
			}

			return new RespuestaJson(
					array( "respuesta" => $this->datos, "errores" => $this->errores ),
					$this->seccion
				);
		}


	}


 ?>