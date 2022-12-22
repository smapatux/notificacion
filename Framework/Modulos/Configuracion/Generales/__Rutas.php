<?php
	#Grupos
	$router->GET("api/configuracion/generales/unidades-medidas/",	"\Modulos\Configuracion\Generales\UnidadesMedidas", "buscar");
	$router->POST("api/configuracion/generales/unidades-medidas/",	"\Modulos\Configuracion\Generales\UnidadesMedidas", "agregar");
	$router->PUT("api/configuracion/generales/unidades-medidas/",	"\Modulos\Configuracion\Generales\UnidadesMedidas", "editar");

	$router->GET("api/configuracion/generales/sucursales/",	"\Modulos\Configuracion\Generales\Sucursales", "buscar");
	$router->POST("api/configuracion/generales/sucursales/",	"\Modulos\Configuracion\Generales\Sucursales", "agregar");
	$router->PUT("api/configuracion/generales/sucursales/",	"\Modulos\Configuracion\Generales\Sucursales", "editar");

 ?>