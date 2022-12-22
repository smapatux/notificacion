<?php
namespace Herramientas\Generales;

class Pdf {
	function __construct() {
	}

	public static function generarPdf($plantilla, $data, $fileName, $destino = "../public/Descargas/") {

		$plantilla = str_replace("Framework/Vistas/Pdf/Framework/Vistas/Pdf/$plantilla", "Framework/Vistas/Pdf/$plantilla", $plantilla);
		$html      = self::obtenerPlantilla($plantilla, $data);
		$mpdf      = new \Mpdf\Mpdf();
		$mpdf->WriteHTML($html);
		$mpdf->Output("$destino$fileName", "F");
		return "$destino$fileName";
	}

	public static function descargarPdf($plantilla, $data, $fileName, $config = null, $html = true) {
		$plantilla = str_replace(
			"Framework/Vistas/Pdf/Framework/Vistas/Pdf/$plantilla",
			"Framework/Vistas/Pdf/$plantilla",
			$plantilla
		);

		# config [alto,ancho]
		if (is_array($config)) {
			$param = array();

			if (count($config) == 2) {
				$mpdf = new \Mpdf\Mpdf(["mode" => 'utf-8'], $config);
			}

		} else {
			$mpdf = new \Mpdf\Mpdf();
		}

		$mpdf = new \Mpdf\Mpdf($config);

		if ($html) {
			$html = self::obtenerPlantilla($plantilla, $data);

			$mpdf->WriteHTML($html);
		} else {
			ob_start();
			include $plantilla;
		}

		$mpdf->Output($fileName, "D");
	}

	public static function obtenerPlantilla($url, $data) {
		ob_start();
		require $url;
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

}
?>
