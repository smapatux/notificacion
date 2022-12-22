<?php
namespace Controlador\Respuestas;
class RespuestaCsv
{
	public $datos;
	public $seccion;
	private $nombreArchivo = "file.csv";

	function __construct($datos = null, $seccion = null)
	{
		#Generar IdToken por cada una de las llamadas
		# Guardar en sesion para comprobar si es la misma sesion

		$this->datos = $datos;
		$this->seccion = $seccion;
	}

	public function render()
	{
		ob_end_clean();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=data.csv');

		$fp = fopen('php://output','w');

		if ( count($this->datos) > 0 )
		{
			# Cabezera
			fputcsv($fp, array_keys( array_change_key_case( $this->datos[0], CASE_UPPER )));
			
			# Contenido
			foreach( $this->datos as $dato )
				fputcsv($fp, $dato);

		}
		
	
		fpassthru($fp);
		exit();
	}


	public function saveFile( $filename )
	{
		$timestamp = time();
		$nombreArchivo = !empty( $filename ) ? $filename : $this->nombreArchivo;
		$nombreArchivo = "$nombreArchivo-$timestamp.csv";

		$file = fopen("../public/Descargas/$nombreArchivo", 'w');
		fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

		if ( count($this->datos) > 0 )
		{
			# Cabezera
			fputcsv($file, array_keys( array_change_key_case( $this->datos[0], CASE_UPPER )));
			
			# Contenido
			foreach ($this->datos as $k => $d)
				fputcsv($file, $d);
		}


		fclose( $file );

		return $nombreArchivo;
	}

}
?>
