<?php
	namespace Herramientas\Generales;
	
	use \Modulos\Notificaciones\Notificaciones;

	class Notify
	{
		function __construct()
		{
			
		}

		public static function listaNotificaciones()
		{
			global $dbGlobal;

			$retorno=$dbGlobal->getArray
			(
				"select
					id,
					status,
					visto,
					descripcionCorta,
					idRequest,
					fecha,
					nombreSeccion,
					icono,
					claseVisita,
					url
				from
					Notificaciones.listadoNotificacion(".$_SESSION["id"].",0)
				order by id desc"
			);	

			return $retorno;
		}

		public static function hayNotificacion()
		{
			global $dbGlobal;

			$retorno=$dbGlobal->getValue("select top 1 status from Notificaciones.alertaUsuario where id_usuario=$_SESSION[id]");

			return ($retorno==0 || $retorno=="")?0:$retorno;
		}


		public static function crearNotificacionGrupo($idRequest,$descripcionCorta,$idGrupo,$idSeccion,$noSeccionUrl=1)
		{
			global $dbGlobal;
			//$idSeccion 1 solicitudes
			
			$idEmisor=2;//grupo
			$usuarioDisparo=$_SESSION["id"];

			$dbGlobal->run
			(
				"exec Notificaciones.crearNotificacion
					$idSeccion,
					$noSeccionUrl,
					$idRequest,
					'$descripcionCorta',
					0,
					$idEmisor,
					$idGrupo,
					$usuarioDisparo"
			);
		}
	}
?>
