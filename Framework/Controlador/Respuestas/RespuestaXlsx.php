<?php
namespace Controlador\Respuestas;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use PHPExcel_Style_NumberFormat;
use PHPExcel_Worksheet_MemoryDrawing;

Class RespuestaXlsx {
	private $PATH_ROOT     = "Framework/Vistas/Xlsx/";
	private $objPHPExcel   = null;
	private $nombreArchivo = "file.xlsx";
	private $datos         = null;
	private $plantilla     = null;
	//public function __construct($titulo = "Reporte", $asunto = "1111", $descripcion = "111", $creador = "SMAPA")
	public function __construct($plantilla = null, $datos = null) {

		error_reporting(E_ALL);
		ini_set('display_errors', TRUE);
		ini_set('display_startup_errors', TRUE);
		define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
		date_default_timezone_set('Europe/London');
		$this->objPHPExcel = new PHPExcel();
		$this->plantilla   = $this->PATH_ROOT . $plantilla;
		$this->datos       = $datos;
	}

	public function render() {
		if (ob_get_contents()) {
			ob_end_clean();
		}

		header_remove();
		require $this->plantilla;
		$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $this->nombreArchivo . '.xlsx"');
		$objWriter->save('php://output');
	}

	//Funciones de ayuda
	function generarImagen($ruta, $celda, $hojaDestino = null, $alto = null, $ancho = null) {
		$gdImage    = imagecreatefrompng($ruta);
		$objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
		$objDrawing->setImageResource($gdImage);
		$objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
		$objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
		$objDrawing->setHeight($alto);
		if ($ancho != null) {
			$objDrawing->setWidth($ancho);
		}
		$objDrawing->setCoordinates($celda);
		$objDrawing->setWorksheet($hojaDestino);
	}

	function combinarCeldas($desde, $hasta) {
		$this->objPHPExcel->getActiveSheet()->mergeCells($desde . ':' . $hasta);
	}

	function combinarCeldasByNumColRow($c1, $r1, $c2, $r2) {
		$this->objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow($c1, $r1, $c2, $r2);
	}

	function centrarCelda($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	}

	function alinearDerecha($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	}

	function alinearIzquierda($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	}

	function cambiarPagina($pagina) {
		$this->objPHPExcel->setActiveSheetIndexByName($pagina);
	}

	function formatoMonetario($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
	}

	function formatoNumerico($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode('#,##0.00');
	}

	function formatoFecha($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14);
	}

	function insertarTexto($celda, $Texto) {
		$this->objPHPExcel->getActiveSheet()->SetCellValue($celda, $Texto);
	}

	function insertarTextoByNumColRow($col_num, $row_num, $Texto) {
		$this->objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col_num, $row_num, $Texto);
	}

	// function insertarFormulaByNumColRow($col_num, $row_num, $formula) {
	// 	$this->objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($col_num, $row_num, $formula);
	// }

	function cellColor($cells, $color) {
		$this->objPHPExcel
			->getActiveSheet()
			->getStyle($cells)
			->getFill()
			->applyFromArray([
				'type'       => PHPExcel_Style_Fill::FILL_SOLID,
				'startcolor' => [
					'rgb' => $color,
				],
			]);

	}

	// function cellRangeColor($desde, $hasta, $color) {

	// }

	function Negritas($cells) {
		$this->objPHPExcel->getActiveSheet()->getStyle($cells)->getFont()->setBold(true);
	}

	function altoSet($fila, $numChart, $maxChart) {
		if ($numChart > $maxChart) {
			$row  = ceil($numChart / $maxChart);
			$alto = $row * 15;
			$this->objPHPExcel->getActiveSheet()->getRowDimension($fila)->setRowHeight($alto);
		}
	}

	function celdaNumerica($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}

	function celdaInt($celda) {
		$this->objPHPExcel->getActiveSheet()->getStyle($celda)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
	}

	function cabeceraTabla($columnas) {
		$this->combinarCeldas('A1', 'B4', '0');
		$this->generarImagen("public/Generales/img/logoLeft.png", "A1", $this->objPHPExcel->getActiveSheet(), 70);

		$rangoCabecera  = $columnas - 2;
		$rangoLetra     = PHPExcel_Cell::stringFromColumnIndex($rangoCabecera);
		$logoDerecha    = PHPExcel_Cell::stringFromColumnIndex($rangoCabecera + 1);
		$finLogoDerecha = PHPExcel_Cell::stringFromColumnIndex($columnas);

		$this->combinarCeldas('C1', $rangoLetra . '1', 0);
		$this->combinarCeldas('C2', $rangoLetra . '2', 0);
		$this->combinarCeldas('C3', $rangoLetra . '3', 0);
		$this->combinarCeldas('A5', $finLogoDerecha . '5', 0);

		$this->combinarCeldas($logoDerecha . '1', $finLogoDerecha . '4', 0);
		$this->generarImagen("public/Generales/img/logoRight.png", $logoDerecha . '1', $this->objPHPExcel->getActiveSheet(), 70);
	}

	function grupoBordes($desde, $hasta, $color) {
		$styleArray = array(
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
					'color' => array('rgb' => $color),
				),
			),
		);

		$this->objPHPExcel->getActiveSheet()->getStyle($desde . ':' . $hasta)->applyFromArray($styleArray);
		unset($styleArray);
	}

	function validadorBinario($celda, $valor, $colorVerdader = 'AEF35A', $colorFalso = 'D0D0D0') {

		if (isset($valor) && !empty($valor)) {
			$this->insertarTexto($celda, $valor);
			$this->cellColor($celda, $colorVerdader);
		} else {
			$this->insertarTexto($celda, $valor);
			$this->cellColor($celda, $colorFalso);
		}
	}

	function guardarArchivo($nombreArchivo) {
		if (ob_get_contents()) {
			ob_end_clean();
		}
		header_remove();
		$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xlsx"');

		$objWriter->save('php://output');
	}
}
?>
