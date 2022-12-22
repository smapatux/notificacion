<?php
namespace Controlador\Respuestas;

class RespuestaJson {
	public $datos;
	public $seccion;

	function __construct($datos = null, $seccion = null) {
		# Generar IdToken por cada una de las llamadas
		# Guardar en sesion para comprobar si es la misma sesion
		$_SESSION['idToken'] = \Herramientas\Herramientas::randomString(32);

		if (isset($datos) && sizeof($datos['errores']) > 0) {
			$datos['respuesta'] = new \stdClass();
		}

		if (is_array($datos)) {
			$datos['idToken'] = $_SESSION['idToken'];
		} else {
			$datos->idToken = $_SESSION['idToken'];
		}

		$this->datos   = $datos;
		$this->seccion = $seccion;
	}

	public function hayError() {
		return (isset($this->datos['errores']) && count($this->datos['errores']) > 0);
	}

	public function getDatos() {
		return $this->datos['respuesta']['datos'] ?? [];
	}

	public function getErrores() {
		return $this->datos['errores'] ?? [];
	}

	public function render() {

		if (ob_get_contents()) {
			ob_end_clean();
		}

		if (!$_SESSION['logueado']) {
			http_response_code(401);
		}

		if ($this->hayError()) {
			http_response_code(400);
		}

		$html = json_encode($this->datos);

		echo $html;

	}

}
?>
