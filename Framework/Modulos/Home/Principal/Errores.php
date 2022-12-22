<?php
	/**
	 * Mensages y paginas de error para el sistema.	
	 */
	namespace Modulos\Home\Principal;

	use \Controlador\Respuestas\Respuesta;
	# Respuestas disponibles
	use \Controlador\Respuestas\RespuestaJson;
	use \Herramientas\Log as Log;

	class Errores {
		private $datos   = array();
		private $errores = array();
		private $seccion = 'Principal';

		private $__path_api = "#^/*api/#i";

		function __construct() {}

		public function error403() {
			$uri = isset($_GET['uri']) ? $_GET['uri'] : '/';
			if (preg_match($this->__path_api, $uri, $matches)) {
				$respuesta = $this->api(403);
			}
			# Revisar si la peticion es API o HTTP
			else {
				$respuesta = $this->http(403);
			}
			# Redireccion a web login http
			return $respuesta;
		}

		public function error404() {
			$uri = isset($_GET['uri']) ? $_GET['uri'] : '/';
			if (preg_match($this->__path_api, $uri, $matches)) {
				$respuesta = $this->api(404);
			}
			# Revisar si la peticion es API o HTTP
			else {
				$respuesta = $this->http(404);
			}
			# Redireccion a web login http

			return $respuesta;
		}

		public function error500($mensaje = "") {

			$uri = isset($_GET['uri']) ? $_GET['uri'] : '/';
			if (preg_match($this->__path_api, $uri, $matches)) {
				$respuesta = $this->api(500, $mensaje);
			}
			# Revisar si la peticion es API o HTTP
			else {
				$respuesta = $this->http(500, $mensaje);
			}
			# Redireccion a web login http

			return $respuesta;
		}

		private function api($tipoError = 404, $mensaje = "") {
			switch ($tipoError) {
			case 403:$this->errores[] = "Error 403 : No cuenta con los permisos necesarios.";
				break;
			case 500:$this->errores[] = "Error 500 : Error del sistema.";
				break;
			default:$this->errores[] = "Error 404 : Pagina no encontrada.";
				break;
			}

			if (DEBUG) {
				// falta verrrrrr $this->errores = $mensaje->getTraceAsString();
			}
			#Log::guardarReporte("api-$tipoError", "$tipoError:$_GET[uri]$mensaje", 'Errores');

			return new RespuestaJson(
				array("respuesta" => $this->datos, "errores" => $this->errores),
				$this->seccion
			);
		}

		private function http($tipoError = 404, $mensaje = "") {

			$_GET['uri'] = isset($_GET['uri']) ? $_GET['uri'] : '---';

			Log::guardarReporte("http-$tipoError", "$tipoError:$_GET[uri]$mensaje", 'Errores');
			switch ($tipoError) {
			case 403:$plantilla = "Home/Errores/403.html";
				break;
			case 500:$plantilla = "Home/Errores/500.html";
				break;
			default:$plantilla = "Home/Errores/404.html";
				break;
			}

			if (DEBUG && $tipoError == 500) {
				$this->datos['excepcion']['Mensaje'] = $mensaje->getMessage();
				$this->datos['excepcion']['Codigo']  = $mensaje->getCode();
				$this->datos['excepcion']['Archivo'] = $mensaje->getFile();
				$this->datos['excepcion']['Linea']   = $mensaje->getLine();
				$this->datos['excepcion']['Trace']   = str_replace('#', '#', $mensaje->getTraceAsString());
			}

			return new Respuesta($plantilla, $this->datos, $this->seccion);

		}
	}
?>