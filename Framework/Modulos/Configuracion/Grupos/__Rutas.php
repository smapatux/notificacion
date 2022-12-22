<?php

		#lista de grupos
		$router->GET("configuracion/grupos/",	"\Modulos\Configuracion\Grupos\Grupos", "principal", "fa fa-users");
		$router->GET("configuracion/grupos/detalle/",	"\Modulos\Configuracion\Grupos\Grupos", "detalle");
		#Grupos
		
		$router->GET("api/configuracion/grupos/",	"\Modulos\Configuracion\Grupos\Grupos", "listar");
		$router->PUT("api/configuracion/grupos/",	"\Modulos\Configuracion\Grupos\Grupos", "editar");
		$router->POST("api/configuracion/grupos/",	"\Modulos\Configuracion\Grupos\Grupos", "agregar");
		
		$router->GET("api/configuracion/grupos/permisos/",	"\Modulos\Configuracion\Grupos\Permisos", "listar");
		$router->PUT("api/configuracion/grupos/permisos/",	"\Modulos\Configuracion\Grupos\Permisos", "actualizar");
		#$router->POST("api/configuracion/grupos/permisos/",	"\Modulos\Sistema\Configuracion\Grupos\Permisos", "guardar");

		$router->GET("api/configuracion/grupos/modulos/",	"\Modulos\Configuracion\Grupos\Modulos", "listar");
		$router->PUT("api/configuracion/grupos/modulos/",	"\Modulos\Configuracion\Grupos\Modulos", "actualizar");
		#$router->GET("api/configuracion/usuario/permiso/",	"\Modulos\Sistema\Configuracion\Usuario\Perfil", "obtener");

		$router->GET("api/configuracion/grupos/usuario/",	"\Modulos\Configuracion\Grupos\Usuarios", "listar");
		$router->POST("api/configuracion/grupos/usuario/",	"\Modulos\Configuracion\Grupos\Usuarios", "guardar");
		$router->DELETE("api/configuracion/grupos/usuario/",	"\Modulos\Configuracion\Grupos\Usuarios", "eliminar");
		
		$router->GET("api/configuracion/grupos/usuarios/",	"\Modulos\Configuracion\Grupos\Usuarios", "listarUsuarios");

 ?>