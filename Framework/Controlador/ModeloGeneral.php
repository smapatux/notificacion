<?php 
	namespace Controlador;

	# Herramientas	
	use \Herramientas\DbFormat as extraDb;
	use \Herramientas\Respect\Validation\Validator as validacion;
	use \Herramientas\Respect\Validation\Exceptions\ValidationException;

	class ModeloGeneral
	{	
		private $__Modelo = array();
		private $__Tabla = "";

		function __construct($tabla = "", $estructuraModelo = array())
		{
			$this->__Tabla = $tabla;
			$this->__Modelo = $estructuraModelo;
		}

		function get($variable = '')
		{
			$valor = null;
			if ( isset( $this->__Modelo[$variable] ) )
			{
				if ( isset( $this->__Modelo[$variable]['type'] ) && $this->__Modelo[$variable]['type'] == 'boolean')
					$valor = (bool) $this->__Modelo[$variable]['value'];
				else
					$valor = $this->__Modelo[$variable]['value'];
			}

			return $valor;
		}

		function getModel()
		{
			$data = array();

			foreach ($this->__Modelo as $key => $element)
			{
				if ( isset($element['value']) )
				{
					if ( isset($element['type']) && $element['type'] == 'boolean')
						$data[$key] = (bool) $element['value'];
					else
						$data[$key] = $element['value'];
				}
			}	

			return $data;
		}

		function set($variable = "", $valor = "")
		{
			if ( isset($this->__Modelo[$variable]) )
				$this->__Modelo[$variable]['value'] = $valor;
		}

		/**
		 * Asigna el arreglo $datos a los elementos del modelo correspondiente.
		 * Si en el arreglo vienen mas datos son ignorados.
		 */
		function setModel( $datos = array() )
		{
			foreach ($this->__Modelo as $key => $elemento)
			{
				if ( isset($datos[$key]) )
				{
					try
					{
						$this->__Modelo[$key]['value'] = $datos[$key];
						$elemento['regla']->check( $datos[$key] );
					}
					catch( ValidationException $exception )
					{
						$this->errores[] = $exception->getMainMessage();
					}			

				}
			}

		}

		function select($datos = array(), $filtros = array())
		{
			global $dbGlobal;
			// IMPORTANTE:
			# Validar que key exista en filtro y en los modelos de toda la clase
			$resultado = array();

			# Validacion en base a las reglas creadas en el modelo
			foreach($filtros as $key => $filtro)
			{
				try
				{
					#Revisar si existe, los valores de filtro son opcionales
					if ( isset( $this->__Modelo[$key]['regla'] ) && isset( $datos[$key] ) )
					{
						$regla = &$this->__Modelo[$key]['regla'];
						$regla->check($datos[$key]);
					}
				}
				catch( ValidationException $exception )
				{
					$this->errores[] = $exception->getMainMessage();
				}
			}

			if ( !count( $this->errores ) )
			{
				$strFiltros = "";
				$formatDb = new extraDb( $strFiltros );
				

				foreach ($filtros as $key => $filtro)
				{
					if ( !empty( $_GET[$key] ) && $key != "__EXTRA__" )
						call_user_func_array((array(&$formatDb, $filtro['tipo'])), array($key, $_GET[$key]));
				}

				if ( isset( $filtros["__EXTRA__"] ) )
				{
					$formatDb->extra( $filtros["__EXTRA__"]['query'] );
				}				

				$strWhere = !empty($strFiltros) ? " WHERE $strFiltros" : "";

				$strQuery = "SELECT ";
				foreach ($this->__Modelo as $key => $value)
				{
					$strQuery .= $key . ",";
				}
				$strQuery = rtrim($strQuery, ',') . " FROM $this->__Tabla $strWhere";

				$resultado = $dbGlobal->getArray($strQuery);
			}

			return $resultado;
		}

		function insert()
		{
			global $dbGlobal;

			$idInsert = 0;
			$dataDb = array();

			# Creando un insert generico
			# Validacion en base a las reglas creadas en el constructor
			foreach($this->__Modelo as $key => &$elemento)
			{
				try
				{
					if ( !isset( $elemento['pk'] ) || $elemento['pk'] == false )
						$elemento['regla']->check( $elemento['value'] );
				}
				catch( ValidationException $exception )
				{
					$this->errores[] = $exception->getMainMessage();
				}
			}

			if ( !count( $this->errores ) )
			{
				

				foreach ($this->__Modelo as $key => $element)
				{
					if (isset($element['value']))
						$dataDb[$key] = $element['value'];
				}

				$idInsert = $dbGlobal->qqInsert($this->__Tabla, $dataDb);
			}
			else
			{
				$this->errores[] = "Datos incompletos";
			}

			return $idInsert;
		}

		function update()
		{
			global $dbGlobal;
			
			$valido = false;
			$dataDb = array();

			# Creando un update generico
			# Validacion en base a las reglas creadas en el constructor
			foreach($this->__Modelo as $key => &$elemento)
			{
				try
				{
					if ( isset( $elemento['pk'] ) && $elemento['pk'] == true )
						$elemento['regla']->check($elemento['value']);
					else
						validacion::optional( $elemento['regla'] )->check($elemento['value']);
				}
				catch( ValidationException $exception )
				{
					$this->errores[] = $exception->getMainMessage();
				}
			}

			if ( !count( $this->errores ) )
			{
				
				foreach ($this->__Modelo as $key => $element)
				{
					if (isset($element['value']))
						$dataDb[$key] = $element['value'];
				}
				$dbGlobal->qqUpdate($this->__Tabla, $dataDb);
				$valido = true;
			}
			else
			{
				$this->errores[] = "Datos incompletos";
			}

			return $valido;
		}
	}
?>