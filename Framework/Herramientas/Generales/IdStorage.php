<?php
	namespace Herramientas\Generales;

	class IdStorage
	{
		private $size_token = 32;
		private $size_array = 10;

		public function set( $datos )
		{
			if ( count( $_SESSION['idStorage'] ) == $this->size_array ) 
				array_shift( $_SESSION['idStorage'] );

			$token = bin2hex(random_bytes( $this->size_token ));
			$_SESSION['idStorage'][$token] = $datos;
			return $token;
		}

		//Update en lugar de lanzar una excepcion, se regresa el arreglo datos como nulo.
		public function get( $token )
		{
			if ( isset( $_SESSION['idStorage'][$token] ) )
			{
				$datos = $_SESSION['idStorage'][$token];
			}
			else $datos = null;

			return $datos;
		}

		public function issetData( $datos )
		{
			$hash = md5( json_encode( $datos ) );
			$keysDatos = array_keys( $datos );
			$existeToken = "";
			foreach ($_SESSION['idStorage'] as $kToken => $storageDatos) {
				$existenDatos = true;
				$arrStorage = array();
				foreach ($keysDatos as $key => $value) {
					#var_dump($_SESSION['idStorage'][$kToken]);
					$existenDatos  = $existenDatos && array_key_exists($value, $_SESSION['idStorage'][$kToken]);
					if ( $existenDatos )
						$arrStorage[ $value ] = $storageDatos[ $value ];
				}

				if ( empty( $existeToken ) && $existenDatos ){
					$existeToken = ( $hash == md5( json_encode( $arrStorage ) ) ) ? $kToken : "";
				}
			}
			return $existeToken;
		}

	}
?>
