<?php

if (!$router->existenRutas()) {
	# ----------------------------------------------------------------------------------
	# URI's disponibles
	include "Modulos/Home/__Rutas.php";	
	include "Modulos/Configuracion/__Rutas.php";
	# ----------------------------------------------------------------------------------
}

?>