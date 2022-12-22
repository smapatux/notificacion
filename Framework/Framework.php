<?php
	require __DIR__ . '/../vendor/autoload.php';
	require "Framework/Configuracion/Constantes.php";

	$dbGlobal   = new \Herramientas\BaseDeDatos();
	$_paginador = new \Herramientas\Generales\Paginador();

	$__sam            = new \stdClass();
	$__sam->Usuario   = new \Herramientas\Generales\Usuario();
	$__sam->IdStorage = new \Herramientas\Generales\IdStorage();
	
	$router = new \Controlador\Router();

	# CONTIENE TODAS LAS LIBRERIAS, CONFIGURACIONES, PROCESOS E INFORMACION DE INICIO.
	require "Framework/Configuracion/Sesion.php";

	#Validar dependiendo de archivo conf.json
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	if ($_SESSION['logueado'])
	{
		//DEBUG -------------------------------------------------
		function consoleLog($data) {
			echo '<script>';
			echo 'console.log(' . json_encode($data) . ')';
			echo '</script>';
		}
		#----------------------------------------------------------
	
		$_Ejecutar = $router->proceso();	
		if (isset($_Ejecutar->clase))
		{
			#LOG SISTEMTA
			(new \Modulos\Configuracion\Log\Sistema())->agregar(
				$_Ejecutar->uri,
				$_Ejecutar->funcion,
				str_replace('\\', '-', $_Ejecutar->clase),
				json_encode($_POST, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
				$_Ejecutar->tipoRequest,
				$_SESSION['id']
			);

			$clase = new $_Ejecutar->clase;

			#___________________________________________________________
			# Validacion de permisos
			$permisos = new \Modulos\Home\Acceso\Permisos();
			if ($permisos->validarPermisoUsuario()) {
				// $respuesta = call_user_func(array(&$clase, $_Ejecutar->funcion));
				if (DEBUG) {
					$respuesta = call_user_func(array(&$clase, $_Ejecutar->funcion));
				} else {
					try
					{
						$respuesta = call_user_func(array(&$clase, $_Ejecutar->funcion));
					} catch (Exception $e) {
						if ($dbGlobal->contadorTransacciones > 0) {
							$dbGlobal->cancelarTransaccion();
						}

						$errores   = new \Modulos\Home\Principal\Errores();
						$respuesta = $errores->error500($e);
					}
				}

			} else {
				$respuesta = $permisos->noPermitido();
			}
		} else {
			$errores   = new \Modulos\Home\Principal\Errores();
			$respuesta = $errores->error404();
		}
	}
	else
	{
		$acceso    = new \Modulos\Home\Acceso\Acceso();
		$respuesta = $acceso->inicio();
	}
	
	switch (get_class($respuesta))
	{
		case 'Controlador\Respuestas\Respuesta':
			$respuesta->render($_Ejecutar ?? null);
			break;		
		case 'Controlador\Respuestas\RespuestaJson':
			$respuesta->render();
			break;		
		case 'Controlador\Respuestas\RespuestaPdf':
			$respuesta->render();
			break;		
		case 'Controlador\Respuestas\RespuestaXlsx':
			$respuesta->render();
			break;		
		case 'Controlador\Respuestas\RespuestaZip':
			$respuesta->render();
			break;		
		case 'Controlador\Respuestas\RespuestaCsv':
			$respuesta->render();
			break;		
		default: break;
	}
?>