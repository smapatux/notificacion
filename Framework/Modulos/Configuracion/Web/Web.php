<?php 

	/**
	* Proveedores.
	* 
	*/
	namespace Modulos\Configuracion\Web;

	# Herramientas
	use \Herramientas\Log as Log;
	
	# Respuestas disponibles
	use \Controlador\Respuestas\Respuesta as Respuesta;

	class Web
	{	
		private $seccion = 'Configuracion';
		function __construct() { }

		public function modulos() 
		{
			$plantilla = "Configuracion/Modulos/Modulos.html";
			$datos = array( );
			return new Respuesta($plantilla, $datos, $this->seccion);
		}



	}


 ?>