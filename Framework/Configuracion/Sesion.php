<?php
#CONTROL DE SESION DEL USUARIO

#session_cache_limiter("private");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: post-check=0, pre-check=0", false);

session_cache_limiter("must-revalidate");

if (ini_set('session.name', SESSION_NAME) === false || !session_name(SESSION_NAME)) {
	die('Unable to set sesssion scope');
}

session_start();

# __Init__: Default
if (!isset($_SESSION['logueado'])) {
	$_SESSION['logueado'] = false;
}

if (!isset($_SESSION['idToken'])) {
	$_SESSION['idToken'] = '';
}

if (!isset($_SESSION['idStorage'])) {
	$_SESSION['idStorage'] = [];
}

?>