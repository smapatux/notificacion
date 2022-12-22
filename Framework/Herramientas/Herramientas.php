<?php
# CONTIENE TODAS LAS FUNCIONES GENERALES
# QUE PUEDEN SER UTILIZADAS EN CUALQUIER PARTE DEL SISTEMA

# ULTIMA ACTUALIZACION: 24 Junio 2015
namespace Herramientas;

use \stdclass;

class Herramientas {
	#private static $inicializadorEncriptador ="";
	# Constructor
	public function Herramientas() {
	}

	public static function pcClienteDatos($dato = "n") {

		//'a': Elegida por defecto. Contiene todos los modos en la secuencia "s n r v m".
		//'s': Nombre del sistema operativo. ej. FreeBSD.
		//'n': Nombre del Host. ej. localhost.example.com.
		//'r': Nombre de la versión liberada. ej. 5.1.2-RELEASE.
		//'v': Información de la versión. Varia mucho entre los sistemas operativos.
		//'m': Tipo de máquina. ej. i386.

		return php_uname($dato);
	}

	public static function objetoParseArray($Objeto) {
		$respuesta            = new \stdclass();
		$respuesta->tipo      = 0;
		$respuesta->resultado = 0;

		if (is_object($Objeto)) {
			if (!is_array($Objeto)) {
				$arreglo = array();
				array_push($arreglo, $Objeto);
				return $arreglo;
			}
		}
		return $Objeto;
	}

	# El primer parametro, es una fecha en formato date(d-m-y).
	# El segundo parametro es para sumar o quitar dias a la fecha.
	public static function obtenerFecha($fecha, $numeroDeDias) {
		list($dia, $mes, $ano) = explode('-', $fecha);
		return date('d/m/Y', mktime(0, 0, 0, $mes, $dia + $numeroDeDias, $ano));
	}

	public static function getFechaDB($fecha) {
		list($anio, $mes, $dia) = explode('-', $fecha);
		list($dia, $hora)       = explode(' ', $dia);
		return date('d-m-Y', mktime(0, 0, 0, $mes, $dia, $anio));
	}

	#Convierte a formato para la BD
	/*
	    public static function formatoFechaBd($fecha, $deliminator = '/')
	    {
	    	$resultado = null;
	    	if ( !empty( $fecha ) )
	    	{
	        	list($dia,$mes,$ano) = explode( $deliminator, $fecha );
				$resultado = date( 'Y-m-d', mktime(0,0,0,$mes,$dia,$ano) );
	    	}

	        return $resultado;
	    }
*/
	public static function formatoFechaBd($fecha, $deliminator = '/') {
		if (!empty($fecha)) {
			$fecha = str_replace($deliminator, "-", $fecha);
			return date('Y-m-d', strtotime($fecha));
		}
		return null;
	}

	public static function formatoFechaSystem($fecha, $deliminator = '/') {
		if (!empty($fecha)) {
			$fecha = str_replace($deliminator, "-", $fecha);
			return date('d/m/Y', strtotime($fecha));
		}
		return null;
	}

	public static function formatoFechaHoraBd($fecha, $deliminator = '/') {
		if (!empty($fecha)) {
			$fecha = str_replace($deliminator, "-", $fecha);
			return date('Y-m-d H:i:s', strtotime($fecha));
		}
		return null;
	}

	public static function formatoFechaHoraApi($fecha, $deliminator = '/') {
		if (!empty($fecha)) {
			$fecha = str_replace($deliminator, "-", $fecha);
			return date('Y-m-d H:i:s', strtotime($fecha));
		}
		return null;
	}

	# CONVIERTE UNA CANTIDAD EN FORMATO DE CENTAVOS A PESOS
	# Ejemplo: 34800 centavos serán 348 pesos
	public static function centavosAPesos($PrecioEnCentavos) {
		if ($PrecioEnCentavos > 0) {
			$resultadoXML = $PrecioEnCentavos / 100;
		}

		return $resultadoXML;
	}

	#Genera una cadena aleatoria.
	public static function randomString($chars = 8) {
		$letters = 'abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		return substr(str_shuffle($letters), 0, $chars);
	}

	#Genera una cadena aleatoria de numeros.
	public static function randomStringHex($chars = 40) {
		$randomString = "";
		$letters      = '1234567890ABCDEF';
		for ($i = 0; $i < $chars; $i++) {
			$randomString .= $letters[mt_rand(0, 15)];
		}

		return $randomString;
	}

	# Formato de moneda para paypal.
	public static function formatoMonedaPaypal($monto) {
		$montoFormateado = number_format($monto, 2, '.', '');
		return $montoFormateado;
	}

	# Formato de moneda estandar (1200.00)
	public static function moneda($monto) {
		return number_format(str_replace(',', '', $monto), 2, '.', ',');
	}

	# Calcular montos con Impuestos
	public static function montoConImpuestos($monto) {
		$respuesta                    = new stdclass();
		$respuesta->porcentaje        = 0;
		$respuesta->impuestos         = 0;
		$respuesta->montoConImpuestos = $monto;

		$precioConIVA = $monto;

		if (isset($_SESSION['IVA'])) {
			# Calcular IVA
			if ($monto > 0) {
				$iva          = $_SESSION['IVA'];
				$totalIVA     = ($iva * $monto) / 100;
				$precioConIVA = $monto + $totalIVA;

				$respuesta->porcentaje        = $iva;
				$respuesta->impuestos         = $totalIVA;
				$respuesta->montoConImpuestos = $precioConIVA;
			}
		}

		return $respuesta;
	}

	public static function cifrar($datos) {
		/*
			$algorithm = MCRYPT_BLOWFISH;
			$key = '1K2olry6RtML';
			$data = $datos;
			$mode = MCRYPT_MODE_CBC;

			$ini = mcrypt_create_iv(mcrypt_get_iv_size($algorithm, $mode),MCRYPT_DEV_URANDOM);
			$encrypted_data = mcrypt_encrypt($algorithm, $key, $data, $mode, $ini);
			$datosCifrados = base64_encode($encrypted_data);
		*/

		$datosCifrados = base64_encode($datos);

		return $datosCifrados;
	}

	public static function descifrar($datosCifrados) {
		/*
			$algorithm = MCRYPT_BLOWFISH;
			$key = '1K2olry6RtML';
			$mode = MCRYPT_MODE_CBC;
			$ini = mcrypt_create_iv(mcrypt_get_iv_size($algorithm, $mode),MCRYPT_DEV_URANDOM);
			$encrypted_data = base64_decode($datosCifrados);
			$decoded = mcrypt_decrypt($algorithm, $key, $encrypted_data, $mode, $ini);
		*/
		$decoded = base64_decode($datosCifrados);
		return $decoded;
	}

	public static function generarToken($size = 32) {
		return bin2hex(random_bytes($size));

	}

	/**
	 * { function_description }
	 *
	 * @param      string  $fecha_inicial  Fecha -> AÑO / MES / DIA
	 * @param      string  $fecha_final    Fecha -> AÑO / MES / DIA
	 * @param      string  $formato        The formato
	 *
	 * @return     array   Fechas en el intervalo
	 */
	public static function generarRangoFechas($fecha_inicial, $fecha_final, $formato = 'Y/m/d') {
		$fechas = [];

		$intervalo = new \DateInterval('P1D');

		$period = new \DatePeriod(
			new \DateTime($fecha_inicial),
			$intervalo,
			(new \DateTime($fecha_final))->add($intervalo)
		);

		foreach ($period as $fecha) {
			$fechas[] = $fecha->format($formato);

		}

		return $fechas;

	}

}
?>
