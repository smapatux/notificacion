<?php 
	namespace Modulos\Configuracion\Generales;

	#Controlador
	use \Controlador\ModeloGeneral;

	# Herramientas
	use \Herramientas\Log as Log;
	use \Herramientas\VariablesGlobales as SG;
	use \Herramientas\DbFormat as extraDb;
	
	use \Respect\Validation\Validator as validacion;
	use \Respect\Validation\Exceptions\ValidationException;
	# Respuestas disponibles
	use \Controlador\Respuestas\RespuestaJson as RespuestaJson;

	class Sucursales extends ModeloGeneral
	{
		public $datos = array();
		public $errores = array();
		private $seccion = 'Configuracion';
		function __construct()
		{
			$modelo = array(
				'id' => array('type' => 'integer', 'pk' => true, 'regla' => validacion::intVal()->setName('id')),
				'id_atl' => array('type' => 'integer', 'pk' => true, 'regla' => validacion::intVal()->setName('id_atl')),
				'nombre' => array('type' => 'string', 'regla' => validacion::notEmpty()->setName('nombre') ),
				'siglas' => array('type' => 'string', 'regla' => validacion::notEmpty()->setName('siglas') ),
				'registro_atl' => array('type' => 'boolean', 'regla' => validacion::boolVal()->setName('registro_atl') ),
			);
			parent::__construct("sucursales", $modelo);
		}

		function buscar()
		{
			$filtrosBusqueda = array(
					'id' => array( 'tipo' => 'where' ),
					'id_atl' => array( 'tipo' => 'where' ),
					'nombre' => array( 'tipo' => 'like' ),
					'siglas' => array( 'tipo' => 'like' ),
					'registro_atl' => array( 'tipo' => 'where' ),
				);
			$this->datos["sucursales"] = $this->select($_GET, $filtrosBusqueda);

			return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);
		}
		
		public function agregar()
		{
			global $dbGlobal;
			
			$this->datos['nombre'] =  $_POST['nombre'];
			$this->datos['siglas'] =  $_POST['siglas'];

			$nombre = $this->datos['nombre'];
			$siglas = $this->datos['siglas'];

			
			$dbGlobal->run("INSERT INTO sucursales (nombre, siglas) VALUES ( '$nombre', '$siglas')");
			$this->datos['id'] = $dbGlobal->getValue("SELECT LAST_INSERT_ID()");

			return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);

		}
		
		public function editar()
		{
			$this->setModel( $_POST );
			$this->update();

			if ( !count( $this->errores ) )
			{
				$this->datos['UnidadesMedidas'][] = $this->getModel();	
			}

			return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);
		}
	}

 ?>