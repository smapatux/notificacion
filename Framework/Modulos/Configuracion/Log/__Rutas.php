<?php


		$router->GET("configuracion/log/sistema/",	"\Modulos\Configuracion\Log\Sistema", "ver");
		$router->POST("api/configuracion/log/",	"\Modulos\Configuracion\Log\Log", "agregar");

?>
