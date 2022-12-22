<?php
$URL = "perfil/";
$API = "api/perfil/";

$router->GET($API . "obtener/papeleta/", "\Modulos\Home\Perfil\Perfil", "getPapeleta");
$router->GET($API . "obtener/papeleta/impresion/", "\Modulos\Home\Perfil\Perfil", "obtenerPapeletas");

$router->GET($URL . "papeleta/generar-pdf/", "\Modulos\Home\Perfil\Perfil", "generarPDFPapeleta", "");
?>