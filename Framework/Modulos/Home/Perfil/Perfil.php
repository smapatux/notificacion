<?php
/**
 * Metodos relacionados con la vista inicial del sitio.
 *
 */
namespace Modulos\Home\Perfil;

# Herramientas

use \Controlador\Respuestas\Respuesta;
use \Controlador\Respuestas\RespuestaJson;
use \Controlador\Respuestas\RespuestaPdf;

# Respuestas disponibles
use \Herramientas\DbFormat as extraDb;
# Validacion
use \Herramientas\Generales\DataUser;
use \Herramientas\ValidacionFormat;
use \Modulos\RecursosHumanos\Nomina\Impresion\Impresion;
use \Modulos\RecursosHumanos\Nomina\Papeletas;
use \PhpThumbFactory;

class Perfil {
	private $seccion = 'Servicios';
	public $datos    = array();
	public $errores  = array();

	function __construct() {}

	public function perfil() {
		global $dbGlobal;
		$perfil             = new \Modulos\Configuracion\Usuario\Perfil();
		$_GET["infoBasica"] = false;
		$perfil             = $perfil->obtener();

		$this->datos                   = (array) $perfil->datos;
		$this->datos["perfilCompleto"] = $perfil->datos['respuesta']['Usuario'];

		$idEmpleado                          = $_SESSION['idEmpleado'];
		$this->datos["papeletasDisponibles"] = $dbGlobal->getArray
			(
			"SELECT
					rh_pP.id_periodoPapeletas as id,
					concat(rh_per.anio,' - ',dbo.obtenerNombreMes(rh_per.mes),' - ',rh_perSeg.nombre) as nombre
				FROM
					RecursosHumanos.ReciboEmpleado rh_re
					INNER JOIN RecursosHumanos.Personal rh_p ON rh_p.id_personal = rh_re.empleado_id
					INNER JOIN RecursosHumanos.Recibo rh_r ON rh_r.idRecibo = rh_re.recibo_id
					INNER JOIN RecursosHumanos.periodoPapeletas rh_pP ON rh_pP.id_periodoPapeletas = rh_r.periodoPapeletas_id
					INNER JOIN RecursosHumanos.periodos rh_per ON rh_pP.periodo_id = rh_per.id_periodo
					INNER JOIN RecursosHumanos.periodosSegmentos rh_perSeg ON rh_pP.periodoSegmento_id = rh_perSeg.id_periodoSegmento
				WHERE
					rh_r.estatus = 1
					and empleado_id = $idEmpleado
				order by rh_per.anio desc, rh_per.mes desc"
		);

		$this->datos['perfilCompleto']->imagenPerfil = $this->getImagenPerfil($_SESSION['id']);

		$plantilla = "Perfil/index.html";
		return new Respuesta($plantilla, $this->datos, $this->seccion);
	}

	public function getPapeleta() {
		global $dbGlobal;
		$validar = new ValidacionFormat("get");
		$id      = $validar->v_numeroPositivo(true, "id");

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$formatDb   = new extraDb($filtros);
			$idEmpleado = $_SESSION['idEmpleado'];
			$datosArray = $dbGlobal->getRow
				(
				"SELECT
						recibo_id as id,
						concat(rh_per.anio,' - ',dbo.obtenerNombreMes(rh_per.mes),' - ',rh_perSeg.nombre) as nombre,
						informacion,
						RecursosHumanos.papeletaEstadoEmpleado($idEmpleado, rh_pP.id_periodoPapeletas) as estado,
						rh_re.empleado_id as empleado,
						rh_pP.id_periodoPapeletas as periodo
					FROM
						RecursosHumanos.ReciboEmpleado rh_re
						INNER JOIN RecursosHumanos.Personal rh_p ON rh_p.id_personal = rh_re.empleado_id AND rh_p.impresionPapeleta = 1
						INNER JOIN RecursosHumanos.Recibo rh_r ON rh_r.idRecibo = rh_re.recibo_id
						INNER JOIN RecursosHumanos.periodoPapeletas rh_pP ON rh_pP.id_periodoPapeletas = rh_r.periodoPapeletas_id
						INNER JOIN RecursosHumanos.periodos rh_per ON rh_pP.periodo_id = rh_per.id_periodo
						INNER JOIN RecursosHumanos.periodosSegmentos rh_perSeg ON rh_pP.periodoSegmento_id = rh_perSeg.id_periodoSegmento
					WHERE
						rh_r.estatus = 1
						and empleado_id = $idEmpleado
						and rh_r.idRecibo = $id"
			);

			//$papeletas = new Papeletas();
			$datosArray['informacion'] = Papeletas::acercarFechaGlobal($datosArray['informacion']);
			$datosArray['informacion'] = str_replace('Ä', '═', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('Â', '═', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('Á', '═', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('³', '║', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('Ú', '╔', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('¿', '╗', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('À', '╚', $datosArray['informacion']);
			$datosArray['informacion'] = str_replace('Ù', '╝', $datosArray['informacion']);
			$datosArray['informacion'] = Papeletas::quitarLineaFirma($datosArray['informacion']);
			$this->datos["datos"]      = $datosArray;
			$this->datos["mensaje"]    = "Datos exitosamente cargados";
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	# Limpiar funcion
	public function obtenerPapeletas() {
		global $dbGlobal;

		$periodos = $_GET["periodos"] ?? null;
		$estado   = intval($_GET["estado"]) ?? null;

		unset($_GET);
		$_GET["id"]           = $_SESSION['idEmpleado'];
		$_GET["tipoBusqueda"] = 2;
		if (!is_null($estado)) {
			$_GET["estado"] = $estado;
		}

		$_GET["periodos"] = $periodos;

		$impresion = new Impresion();
		$impresion = $impresion->getPapeletas();

		if (sizeof($impresion->datos["errores"])) {
			return new RespuestaJson(
				array("respuesta" => array(), "errores" => $impresion->datos["errores"]),
				$this->seccion
			);
		} else {
			$this->datos = $impresion->datos["respuesta"];
			return new RespuestaJson(
				array("respuesta" => $this->datos, "errores" => array()),
				$this->seccion
			);
		}
	}

	public function getImagenPerfil($idUsuario, $ancho = 100, $alto = 100) {
		global $dbGlobal;
		$validar      = new ValidacionFormat("get");
		$id           = $validar->v_clavePrincipal(true, $idUsuario, "", array('nombre' => 'idUsuario'));
		$imagenPerfil = '';
		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$cacheData = new DataUser();

			if (!$cacheData->tieneImagenPerfil($idUsuario, $ancho, $alto)) {
				$imagenPerfil = $dbGlobal->getValue("SELECT
							isnull(p.imagenPerfil, '') as img
						FROM RecursosHumanos.Personal p
						INNER JOIN dbo.usuarios u ON u.id_empleado = p.id_personal WHERE u.id = $id");

				if (!empty($imagenPerfil) && file_exists("servicios/UploadPerfil/$imagenPerfil")) {

					try
					{
						$thumb       = \PhpThumbFactory::create("servicios/UploadPerfil/$imagenPerfil");
						$tmpPathFile = sys_get_temp_dir() . "/$imagenPerfil";
						$thumb->adaptiveResize($ancho, $alto)->save($tmpPathFile);
						$check        = getimagesize($tmpPathFile);
						$imagenPerfil = "data:" . $check["mime"] . ";base64," . base64_encode(file_get_contents($tmpPathFile));

						$cacheData->setImagenPerfil($idUsuario, $imagenPerfil, $ancho, $alto);

					} catch (Exception $e) {
						$imagenPerfil = '';
					}

				} else {
					$imagenPerfil = '';
				}

			} else {
				$perfil       = $cacheData->getImagenPerfil($idUsuario, $ancho, $alto);
				$imagenPerfil = $perfil->imagen ?? '';
			}

		}
		return $imagenPerfil;

	}

	public function getUserId($idEmpleado) {
		global $dbGlobal;
		$idEmpleado = intval($idEmpleado);
		return $dbGlobal->getValue("SELECT id FROM dbo.usuarios WHERE id_empleado = $idEmpleado");
	}

	public function setImagenPerfil($idUsuario, $pathOrigen = "") {
		global $dbGlobal;
		$validar = new ValidacionFormat("post");

		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {

			# El empleado tiene foto de perfil?
			$empleado = $dbGlobal->getRow("SELECT
						isnull(p.imagenPerfil, '') as img,
						p.id_personal as idEmpleado
					FROM RecursosHumanos.Personal p
					INNER JOIN dbo.usuarios u ON u.id_empleado = p.id_personal
					WHERE u.id = $idUsuario");

			# Si tiene foto, se elimina el archivo anterior
			if (!empty($empleado['img']) && file_exists("servicios/UploadPerfil/$empleado[img]")) {
				unlink("servicios/UploadPerfil/$empleado[img]");
			}

			$cacheData = new DataUser();
			$cacheData->delImagenPerfil($idUsuario);

			$extension = "jpeg";

			$imagenPerfil = str_replace('data:image/jpeg;base64,', '', $_POST['imagenPerfil']);
			$imagenPerfil = str_replace(' ', '+', $imagenPerfil);
			$imagenPerfil = base64_decode($imagenPerfil);
			$now          = new \DateTime();
			$fileName     = "perfil_" . $now->getTimestamp() . ".$extension";
			$success      = file_put_contents("servicios/UploadPerfil/$fileName", $imagenPerfil);

			$dbGlobal->qqUpdate("RecursosHumanos.Personal", array(
				'id_personal'  => $empleado['idEmpleado'],
				'imagenPerfil' => $fileName,
			));

		}

	}

	public function generarPDFPapeleta() {
		$_GET = ["periodos" => $_GET["periodos"] ?? null, "estado" => 2];

		$resultado = $this->obtenerPapeletas();
		$resultado = $resultado->datos["respuesta"]["datos"]["datos"] ?? [];

		$timestamp = time();
		$filename  = "papeletas-$timestamp.pdf";
		$plantilla = "Framework/Modulos/Home/Perfil/PDF/papeletas.php";

		return new RespuestaPdf($plantilla, $resultado, $filename, ['orientation' => 'P'], false);
	}
}
?>