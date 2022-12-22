<?php	
	include("Perfil/__Rutas.php");

	# SMAPA
	$router->GET("/", '\Modulos\Home\Principal\Principal', 'home' , 'fa fa-home');
	$router->GET("perfil/",	'\Modulos\Home\Perfil\Perfil', 'perfil', 'fa fa-user');
	$router->GET("errores/404/", '\Modulos\Home\Principal\Errores',	'error404', 'fa-times-circle');
	$router->GET("errores/500/", '\Modulos\Home\Principal\Errores',	'error500', 'fa-times-circle');

	$router->add('api/', '\Modulos\Home\Principal\Principal', 'inicio', 'GET');	

	# Login
	$router->GET("login/", "\Modulos\Home\Acceso\Acceso", "loginHttp", 'fa fa-user');
	$router->GET("logout/", "\Modulos\Home\Acceso\Acceso", "logoutHttp", 'fa fa-user');

	$router->POST("api/login/", "\Modulos\Home\Acceso\Acceso", "acceso");
	$router->POST("api/logout/", "\Modulos\Home\Acceso\Acceso", "salir");	
 ?>