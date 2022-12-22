<?php 

/**
* 
*/
	namespace Herramientas;
	use \Exception;
	use \Herramientas\Log as Log;

	class DbFormat
	{		

		public $output = "";

		function __construct( &$output )
		{
			$this->output = &$output;
		}

		public function where($campo, $valor, $operador = "AND")
		{
			$query = "$campo = '$valor'";
			$this->setOutput($query, $operador);
		}

		public function extraWhere($string,$operador="AND")
		{
			$this->setOutput($string, $operador);
		}

		public function extra($query, $operador = "AND")
		{
			$this->setOutput($query, $operador);
		}

		public function between($campo, $inferior, $superior, $operador = "AND")
		{
			$query = "$campo BETWEEN '$inferior' AND '$superior'";
			$this->setOutput($query, $operador);
		}

		public function in($campo, $datos = [], $operador = "AND", $setComillas = false)
		{
			$query = " $campo IN (".implode(", ", array_map(function( $item ) use ($setComillas) {
				
				if ( $setComillas )
					$valor = "'" . $item . "'";
				else
					$valor = is_numeric($item) ? $item : "'" . $item . "'";
				
				return $valor;

			}, $datos) ).")";

			$this->setOutput($query, $operador);
		}


		public function like($campo, $valor, $operador = "AND")
		{
			$query = " $campo LIKE '%$valor%' ";
			$this->setOutput($query, $operador);
		}

		private function setOutput( $query, $operador = "" )
		{
			$this->output .= ( !empty($this->output ) ? " $operador " : "") . $query;	
		}


		public static function setFormat($valor)
		{
			return is_numeric($valor) ? $valor : "'" . $valor . "'";
		}

		public function getConditionComplete()
		{
			return !empty($this->output) ? " WHERE $this->output" : "";
		}

		public function hasCondition()
		{
			return !empty( $this->output );
		}

		public function queryResult2Object( $datos = array(), $delimitador = "." )
		{
			# Si tiene elementos obtiene las llaves de cada columna
			$elementos = count($datos);
			if( $elementos )
				$columnas = array_keys($datos[0]);

			$principal = array();

			# Recorre cada dato generado por la consulta
			foreach ($datos as $key => $usuario)
			{
				$initNodo = new \stdClass;
				# Recorre cada llave del arreglo
				foreach ( $columnas as $valorKey )
				{
					#Separa elementos de la llave para formar objeto
					$elementos = explode($delimitador, $valorKey);
					$countElementos = count( $elementos ) - 1;
					for ( $i = 0; $i < $countElementos; $i++ )
					{
						if ( isset($refNodo) )
						{
							$nombreElemento = $elementos[$i];
							if ( !isset( $refNodo->$nombreElemento ) )
								$refNodo->$nombreElemento = new \stdClass;
							$refNodo = $refNodo->$nombreElemento;

						}
						else
						{
							$nombreElemento = $elementos[0];
							if ( !isset( $initNodo->$nombreElemento ) )
								$initNodo->$nombreElemento = new \stdClass;
							$refNodo = $initNodo->$nombreElemento;
						}

					}
					
					$nombreElemento = $elementos[$i];
					if ( !isset( $refNodo ) )
					{
						$initNodo->$nombreElemento = new \stdClass;
						$initNodo->$nombreElemento = $usuario[$valorKey];
					}
					else
					{
						$refNodo->$nombreElemento = $usuario[$valorKey];

					}

					unset( $refNodo );

				}
				$principal[] = $initNodo;
			}
			return $principal;
		}


	}

	


 ?>