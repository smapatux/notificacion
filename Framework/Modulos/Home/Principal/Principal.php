<?php
	/**
	* Metodos relacionados con la vista inicial del sitio.
	* 
	*/
	namespace Modulos\Home\Principal;

	use \Herramientas\DbFormat;
	#use \Herramientas\Predis\src\Client;	
	use \Herramientas\Herramientas as Herramientas;
	# Respuestas disponibles
	use \Controlador\Respuestas\RespuestaJson 	as RespuestaJson;
	use \Controlador\Respuestas\Respuesta 		as Respuesta;

	class Principal
	{
		private $seccion = 'Servicios';
		public $datos = array();
		
		function __construct() { }

		public function home()
		{
			$plantilla = "Home/index.html";
			return new Respuesta($plantilla, $this->datos, $this->seccion);
		}

		public function indexModulos()
		{
			$plantilla = "__base/indexModulo.html";
			return new Respuesta($plantilla, $this->datos, $this->seccion);
		}

		public function inicio()
		{

			$errores = array();
			$datos = array( 'Empresa' => 'SMAPA', 'Fecha' => date('l jS \of F Y h:i:s A') );

			return new RespuestaJson(
					array( "respuesta" => $datos, "errores" => $errores ), $this->seccion
				);
			
		}


	}
 ?>