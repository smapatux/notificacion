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

	class UnidadesMedidas extends ModeloGeneral
	{
		public $datos = array();
		public $errores = array();
		private $seccion = 'Configuracion';
		function __construct()
		{
			$modelo = array(
				'id' => array('type' => 'integer', 'pk' => true, 'regla' => validacion::intVal()->setName('id')),
				'sigla' => array('type' => 'string', 'regla' => validacion::notEmpty()->setName('sigla') ),
				'descripcion' => array('type' => 'string', 'regla' => validacion::notEmpty()->setName('descripcion') ),
			);
			parent::__construct("catalogo_unidades", $modelo);
		}

		function buscar()
		{
			$filtrosBusqueda = array(
					'id' => array( 'tipo' => 'where' ),
					'Unidad' => array( 'tipo' => 'like' ),
				);
			$this->datos["UnidadesMedidas"] = $this->select($_GET, $filtrosBusqueda);

			return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);

		}
		
		public function agregar()
		{
			$this->setModel( $_POST );

			$this->set( 'IdUnidad', $this->insert() );


			if ( !count( $this->errores ) )
			{
				$this->datos['UnidadesMedidas'][] = $this->getModel();	
			}			

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