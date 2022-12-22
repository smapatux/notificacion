<?php
	namespace Modulos\Home\Acceso;
	
	# Herramientas
	use \Herramientas\Log as Log;
	use \Herramientas\VariablesGlobales as SG;
	

	# Validacion
	use \Respect\Validation\Validator as V;
	use \Respect\Validation\Exceptions\ValidationException;

	# Respuestas disponibles
	use \Controlador\Respuestas\Respuesta;
	use \Controlador\Respuestas\RespuestaJson;

	#use \Modulos\Configuracion\Usuario\Permisos;

	class Permisos
	{
		
		private $datos = array();
		private $errores = array();
		private $seccion = 'Permisos';		
		function __construct()
		{
			
		}

		public function validarPermisoUsuario()
		{
			global $dbGlobal;	
			global $_Ejecutar;
			
			$valido = $dbGlobal->getValue("
				SELECT TOP 1
					count(attr_usuario) as valido
				FROM dbo.getPermisosUsuario( $_SESSION[id] )
				WHERE
					Ruta = '$_Ejecutar->clase' AND
					Atributo = '$_Ejecutar->funcion'");

			return $valido != 0;
		}

		public function noPermitido()
		{
			$errores = new \Modulos\Home\Principal\Errores();
			return $errores->error403();
		}

	}

?>