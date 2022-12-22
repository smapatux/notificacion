<?php
namespace Modulos\Configuracion\Log;

#Controlador

# Herramientas
use \Controlador\Respuestas\Respuesta;
use \Herramientas\Herramientas as Herramientas;
use \Herramientas\Log as Log;

# Respuestas disponibles

class Sistema {
	public $datos    = array();
	public $errores  = array();
	private $seccion = 'Configuracion';
	function __construct() {
	}

	public function agregar($ruta, $funcion, $clase, $datos, $metodo, $usuario) {
		global $dbGlobal;

		return $_SESSION['sistemaLog_id'] = 1;

		$_SESSION['sistemaLog_id'] = $dbGlobal->qqInsert('log_sistema', [
			'idUsuario' => $usuario,
			'ruta'      => $ruta ?? "404",
			'funcion'   => $funcion ?? "404",
			'clase'     => $clase ?? "404",
			'datos'     => $datos ?? "404",
			'metodo'    => $metodo ?? "404",
		]);

		return $_SESSION['sistemaLog_id'];

	}

	public function ver() {
		global $dbGlobal;

		$log = $dbGlobal->getArray("SELECT TOP 1000
					ruta,
					metodo,
					funcion,
					CONCAT(e.nombre, ' ', e.apellidoPaterno, ' ', e.apellidoMaterno) as NombreCompleto,
					creacion
				FROM log_sistema ls
				LEFT JOIN usuarios usr ON usr.id = ls.idUsuario
				LEFT JOIN RecursosHumanos.Personal e ON usr.id_empleado = e.id_personal ORDER BY creacion DESC");

		$logSistema = [];

		foreach ($log as $value) {
			$fecha = Herramientas::getFechaDB($value["creacion"]);
			if (!isset($logSistema[$fecha])) {
				$logSistema[$fecha] = array('datos' => [], 'fecha' => $fecha);
			}
			array_push($logSistema[$fecha]['datos'], $value);
		}

		$this->datos["LogSistema"] = $logSistema;

		$plantilla = "Configuracion/Sistema/log.html";
		return new Respuesta($plantilla, $this->datos, $this->seccion);
	}

}

?>