<?php 

	namespace Modulos\Configuracion\Usuario;

	use \Controlador\__Base;

	class Grupo
	{
		
		private $seccion = 'Configuracion';

		function __construct() {
			
		}

		//Consulta los permisos del usuario y los permisos heredados por grupos
		static public function get( $idUsuario = 0 )
		{
			global $dbGlobal;

			$idUsuario = intval( $idUsuario );

			return $dbGlobal->getArray("SELECT
					id,
					Grupo,
					Descripcion,
					Estatus
				FROM dbo.getGruposDelUsuario( $idUsuario )");
		}


	}


 ?>