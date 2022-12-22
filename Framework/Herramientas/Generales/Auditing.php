<?php

	namespace Herramientas\Generales;

	class Auditing {
		private $stack = [];


		function __construct()
		{
		}

		public function query($origen,$sql = '', $extra = [])
		{
			global $dbGlobal;

			$item = new \stdClass();
			$item->sql = $sql;
			$item->extra = $extra;
			$item->origen = $origen;
			$item->datos = $dbGlobal->getRow( $sql );

			$this->stack[] = $item;

		}

		public function check()
		{
			global $dbGlobal;

			foreach($this->stack as $item)
			{
				#Nuevos valores
				$diferencias = $this->diferencias(
						$item->datos,
						$dbGlobal->getRow( $item->sql )
					);

				// $dbGlobal->qqInsert( 'dbo.sistema_cambios', [
				// 		'usuario_id' => $_SESSION['id'],
				// 		'antes' => json_encode( $diferencias->old ),
				// 		'despues' => json_encode( $diferencias->new ),
				// 		'origen' => $item->diferencias
				// 	]);

				// if ( $diferencias->tieneCambios )
				// 	var_dump( [
				// 		'usuario_id' => $_SESSION['id'],
				// 		'log_id' => $_SESSION['sistemaLog_id'],
				// 		'antes' => json_encode( $diferencias->old ),
				// 		'despues' => json_encode( $diferencias->new ),
				// 		'extra' => json_encode( $item->extra ),					
				// 		'origen' => $item->origen,					
				// 		'query' => $item->sql
				// 	] );

			}

		}

		function diferencias( $init, $new )
		{
			$valores = new \stdClass();
			$valores->tieneCambios = false;
			$valores->old = $valores->new = $valores->keys = [];

			foreach ($init as $key => $item)
				if ( $item != $new[$key] )
				{					
					$valores->keys[] = $key;
					$valores->old[ $key ] = $item;
					$valores->new[ $key ] = $new[$key];
					$valores->tieneCambios = true;

				}


			return $valores;

		}


	}



?>