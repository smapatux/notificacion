<?php
namespace Controlador\Respuestas;

use \Herramientas\Herramientas;

Class RespuestaZip {

	private $archivos = null;
	private $filename = null;

	public function __construct($archivos = array(), $filename = 'file.zip') {
		$this->archivos = $archivos;
		$this->filename = $filename;
	}

	public function render() {
		$tmp_dir     = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
		$filename    = $this->filename;
		$filenameTmp = $tmp_dir . Herramientas::randomString(32) . ".zip";
		$result      = $this->create_zip($this->archivos, $filenameTmp);
		if ($result) {
			$zipped_size = filesize($filenameTmp);
			header("Content-Description: File Transfer");
			header("Content-type: application/zip");
			header("Content-Type: application/force-download"); // some browsers need this
			header("Content-Disposition: attachment; filename=$filename");
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header("Content-Length:" . " $zipped_size");
			if (ob_get_contents()) {
				ob_end_clean();
			}
			flush();
			readfile("$filenameTmp");
			unlink("$filenameTmp"); // Now delete the temp file (some servers need this option)
			exit;
		}

	}

	function create_zip($files = array(), $destination = '', $overwrite = false) {
		//if the zip file already exists and overwrite is false, return false
		if (file_exists($destination) && !$overwrite) {return false;}
		//vars
		$valid_files = array();
		//if files were passed in...
		if (is_array($files)) {
			//cycle through each file
			foreach ($files as $file) {
				//make sure the file exists
				if (file_exists($file)) {
					$valid_files[] = $file;
				}
			}
		}
		//if we have good files...
		if (count($valid_files)) {
			//create the archive
			$zip = new \ZipArchive();
			if ($zip->open($destination, $overwrite ? \ZIPARCHIVE::OVERWRITE : \ZIPARCHIVE::CREATE) !== true) {
				return false;
			}
			//add the files
			foreach ($valid_files as $file) {
				$zip->addFile($file, str_replace("public/Descargas/", "", $file));
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

			//close the zip -- done!
			$zip->close();

			//check to make sure the file exists
			return file_exists($destination);
			//return file_exists($destination);
		} else {
			return false;
		}
	}

}
?>
