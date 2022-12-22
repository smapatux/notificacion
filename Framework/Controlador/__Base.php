<?php
	namespace Controlador;

	# Respuestas disponibles
	use \Controlador\Respuestas\Respuesta;
	use \Controlador\Respuestas\RespuestaCsv;
	use \Controlador\Respuestas\RespuestaJson;
	use \Controlador\Respuestas\RespuestaPdf;
	use \Controlador\Respuestas\RespuestaXlsx;
	use \Controlador\Respuestas\RespuestaZip;
	use \Herramientas\DbFormat;
	use \Herramientas\ValidacionFormat;

	class __Base {

		function __construct() {}

		function view($plantilla = null, $datos = [], $seccion = null, $tipo = "html") {
			return new Respuesta($plantilla, $datos, $seccion, $tipo);
		}

		function json($datos = null, $seccion = null) {
			return new RespuestaJson($datos, $seccion);
		}

		function pdf($url, $datos = null, $filename = null, $config = null, $tieneHtml = true) {
			return new RespuestaPdf($url, $datos, $filename, $config, $tieneHtml);
		}

		function xlsx($plantilla, $datos) {
			return new RespuestaXlsx($plantilla, $datos);
		}

		function zip($archivos = [], $filename = 'file.zip') {
			return new RespuestaZip($archivos, $filename);
		}

		function csv($datos = []) {
			return new RespuestaCsv($datos);
		}

		function setValidacion($tipoValidacion = null) {
			return new ValidacionFormat($tipoValidacion ?? 'post');
		}

		function dbFormat($var) {
			return new DbFormat($var);
		}

		function redireccion($ruta = '', $variables = array(), $foraneo = FALSE) {
			Router::redireccion($ruta, $variables, $foraneo);
		}

		function noRedireccionar($variables = array()) {
			Router::noRedireccionar($variables);
		}
	}
?>