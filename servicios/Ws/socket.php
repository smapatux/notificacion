<?php
	require 'vendor/autoload.php';

	# Ubicando en la raiz del sistema
	chdir(realpath(dirname(__FILE__)) . "/../../");

	# Datos e informacion importante (constantes,accesos,etc...)
	require "Framework/Configuracion/Constantes.php";
	require "Framework/Configuracion/Sesion.php";

	error_reporting(E_ALL | E_STRICT);
	printf(
		"SERVER: %s:%u \n",
		WS_HOST,
		WS_PORT
	);

	$app = new Ratchet\App(WS_HOST, WS_PORT, '0.0.0.0');
	$app->route('/notificaciones', new App\Canales\Notificaciones, ['*']);
	$app->run();
?>