<?php

	#Perfil
	$router->GET("api/configuracion/usuario/",	"\Modulos\Configuracion\Usuario\Perfil", "obtener");
	$router->PUT("api/configuracion/usuario/",	"\Modulos\Configuracion\Usuario\Usuario", "editar");

	#Permisos
	#Sin S, uno en uno. Con S Multiples
	$router->GET("api/configuracion/usuario/modulos/",	"\Modulos\Configuracion\Usuario\Modulos", "listar");
	$router->PUT("api/configuracion/usuario/modulos/",	"\Modulos\Configuracion\Usuario\Modulos", "actualizar");
	#$router->POST("api/configuracion/usuario/modulos/",	"\Modulos\Configuracion\Usuario\Modulos", "actualizarTodos");
	#$router->POST("api/configuracion/usuario/permisos/", "\Modulos\Configuracion\Usuario\Permisos", "actualizarTodos");

	$router->GET("api/configuracion/usuario/permisos/", "\Modulos\Configuracion\Usuario\Permisos", "listar");
	$router->PUT("api/configuracion/usuario/permisos/", "\Modulos\Configuracion\Usuario\Permisos", "actualizar");
	#$router->GET("api/configuracion/usuario/permiso/",	"\Modulos\Configuracion\Usuario\Perfil", "obtener");

	# Detalle de usuario
	$router->GET("configuracion/usuario/",	"\Modulos\Configuracion\Usuario\Usuario", "detalles");

	#Usuarios
	$router->GET("configuracion/usuarios/",	"\Modulos\Configuracion\Usuario\Usuario", "listar", "fa fa-user");
	$router->GET("api/configuracion/usuarios/",	"\Modulos\Configuracion\Usuario\Usuario", "obtenerTodos");
	$router->GET("api/configuracion/buscar/usuarios/",	"\Modulos\Configuracion\Usuario\Usuario", "buscar");

	#Cambio de password
	$router->PUT("api/configuracion/usuario/password/",	"\Modulos\Configuracion\Usuario\Perfil", "cambioPassword");

 ?>