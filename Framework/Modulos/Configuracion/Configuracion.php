<?php
	/**
	* Configuracion
	* 
	*/
	namespace Modulos\Configuracion;
	
	use \Controlador\Respuestas\Respuesta;
	use \Controlador\Respuestas\RespuestaJson;

	class Configuracion
	{
		private $datos = array();
		public $errores = array();
		private $seccion = 'Administracion';
		public function __construct() {

		}

		public function principal()
		{
			$plantilla = "__base/indexModulo.html";
			return new Respuesta($plantilla, $this->datos, $this->seccion);
		}

	}
 ?>