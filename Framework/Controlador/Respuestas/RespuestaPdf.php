<?php
namespace Controlador\Respuestas;
use \Herramientas\Generales\Pdf;

class RespuestaPdf {
	private $url;
	private $datos;
	private $filename;
	private $config;
	private $tieneHtml;

	function __construct($url, $datos = null, $filename = null, $config = null, $tieneHtml = true) {
		$this->url       = $url;
		$this->datos     = $datos;
		$this->filename  = $filename;
		$this->config    = $config;
		$this->tieneHtml = $tieneHtml;
	}

	public function render($file = false) {
		if ($file) {
			return Pdf::generarPdf($this->url, $this->datos, $this->filename, sys_get_temp_dir() . "/");
		} else {
			Pdf::descargarPdf($this->url, $this->datos, $this->filename, $this->config, $this->tieneHtml);
		}

	}

}
?>
