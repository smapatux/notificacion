<?php
	include("Web/__Rutas.php");

	$url = "configuracion/";
	$api = "api/configuracion/"; 

		$router->GET($url,	"\Modulos\Configuracion\Configuracion", "principal", "fa fa-gears");

		$router->GET($api."modulos/",				"\Modulos\Configuracion\ControlModulos", "listarModulos");
		$router->GET($api."modulos/atributos/",	"\Modulos\Configuracion\ControlModulos", "listarAtributos");
		$router->GET($api."modulos/usuario/",		"\Modulos\Configuracion\ControlModulos", "usuarioModulos");
		$router->GET($api."modulos/permisos/",		"\Modulos\Configuracion\ControlModulos", "listarPermisos");
		$router->GET($api."sistemas/",	"\Modulos\Configuracion\ControlModulos", "getSistemas");

		#Configuracion
		$router->POST($api."nuevo-usuario/",	"\Modulos\Configuracion\Usuario\Usuario", "crearCuenta");
		$router->GET($api."update-rutas/",	"\Modulos\Configuracion\ControlModulos", "actualizarDB");
		$router->GET($api."update-iconos/",	"\Modulos\Configuracion\ControlModulos", "actualizarIconos", "fa fa-refresh");
		$router->POST($api."permisos-desarrollo/",	"\Modulos\Configuracion\ControlModulos", "permisosDesarrollo");


	include("Usuario/__Rutas.php");
	include("Grupos/__Rutas.php");
	include("Generales/__Rutas.php");
	include("Log/__Rutas.php");
 ?>