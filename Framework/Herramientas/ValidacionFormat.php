<?php
namespace Herramientas;

use \finfo;
use \Herramientas\VariablesGlobales as SG;
use \Respect\Validation\Exceptions\ValidationException;
use \Respect\Validation\Validator as validacion;

Class ValidacionFormat {
	private $hayError    = false;
	private $noErrores   = 0;
	private $infoErrores = array();
	private $tipo;

	public function __construct($_tipo = null) {
		$this->tipo = ($_tipo != null) ? strtolower($_tipo) : "post";
	}

	private function peticion($validacion, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$valorCampo = (sizeof($params)) ? SG::inputVariable($nombreCampo) : $this->obtenerValorPeticion($nombreCampo);
		$aliasCampo = ($alias == "" && sizeof($params) == 0) ? $nombreCampo : $alias;

		try
		{
			$validacion->setName($aliasCampo)->check($valorCampo);

			if ($fnSucess != null) {
				$fnSucess($valorCampo);
			}

		} catch (ValidationException $exception) {
			$arrayParams = $exception->getParams();

			$nombreCampo     = sizeof($params) ? $params["nombre"] : $arrayParams["name"];
			$valorErrorCampo = isset($params["valor"]) ? $params["valor"] : $arrayParams["input"];

			$this->anadirError($nombreCampo, $valorErrorCampo, $exception->getMainMessage());

			if ($fnFail != null) {
				$fnFail($nombreCampo, $valorErrorCampo, $exception->getMainMessage());
			}

		}

		return $valorCampo;
	}

	public function anadirError($campo, $contenido, $mensaje) {
		$this->hayError = true;
		$this->noErrores++;

		array_push
			(
			$this->infoErrores,
			array
			(
				"campo"   => $campo,
				"valor"   => $contenido,
				"mensaje" => $mensaje,
			)
		);
	}

	public function anadirArrayError($nombreArray, $arrayErrores) {
		$this->hayError = true;
		$this->noErrores += count($arrayErrores);

		foreach ($arrayErrores as $key => $value) {
			$arrayErrores[$key]["campo"] = $nombreArray . "[" . $value["campo"] . "]";
		}

		$this->infoErrores = array_merge($this->infoErrores, $arrayErrores);
	}

	public function combinarErrores($arrayErrores) {
		// $this->hayError = true;
		$this->hayError |= count($arrayErrores) != 0;
		$this->noErrores += count($arrayErrores);

		$this->infoErrores = array_merge($this->infoErrores, $arrayErrores);
	}

	public function obligatorio($validacion, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function opcional($validacion, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		return $this->peticion(validacion::Optional($validacion), $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function hayError() {
		return $this->hayError;
	}

	public function noErrores() {
		return $this->noErrores;
	}

	public function infoErrores() {
		return $this->infoErrores;
	}

	private function obtenerValorPeticion($nombre) {
		if ($this->tipo == "get") {
			return SG::GET($nombre);
		} else if ($this->tipo == "post") {
			return SG::POST($nombre);
		} else {
			return null;
		}

	}

	public function v_uploadSituaciones($datosFile) {
		$tamanoPermitidoBytes = 10000000; //kb
		$tamanoPermitidoMB    = $tamanoPermitidoBytes / 1000000;

		$retorno = false;

		$validacion = validacion::oneOf(
			validacion::extension('png'),
			validacion::extension('jpg'),
			validacion::extension('jpeg'),
			validacion::extension('pdf')
		)->validate($datosFile["name"]);
		if ($validacion) {
			$validacion = validacion::Between(0, $tamanoPermitidoBytes)->validate($datosFile["size"]);

			if ($validacion) {
				$mimeTypesPermitidos = ["image/png", "image/jpeg", "application/pdf"];

				$file_info = new finfo(FILEINFO_MIME_TYPE);
				$mime_type = $file_info->buffer(file_get_contents($datosFile["tmp_name"]));

				if (in_array($mime_type, $mimeTypesPermitidos)) {
					$retorno = true;
				} else {
					$this->anadirError("FILE_TYPE", $mime_type, "Formato de archivo no permitido " . $mime_type);
					$retorno = false;
				}
			} else {
				$this->anadirError("FILE_SIZE", $tamanoPermitidoMB, "el archivo excede el limite de tamaño permitido " . $tamanoPermitidoMB . "MB.");
				$retorno = false;
			}
		} else {
			$this->anadirError("FILE_EXTENSION", $datosFile["name"], "Extension no permitida " . $datosFile["name"]);
			$retorno = false;
		}

		return $retorno;
	}

	public function v_sizeFileSituaciones($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		if ($esObligatorio) {
			$validacion = validacion::Between(0, 10000000);
		}
		// 10 mb
		else {
			$validacion = validacion::Optional(validacion::size('0MB', '10MB'));
		}

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_checkBox($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		if ($esObligatorio) {
			$validacion = validacion::IntVal()->Between(0, 1);
		} else {
			$validacion = validacion::Optional(validacion::IntVal()->Between(0, 1));
		}

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_clavePrincipal($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {

		$validacion = $esObligatorio ?
		validacion::IntVal()->Positive() :
		validacion::Optional(validacion::IntVal()->Positive());
		$valor = $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
		return $valor < 1 ? null : intval($valor);
	}

	public function v_texto($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_claveForanea($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::IntVal()->Positive() :
		validacion::Optional(validacion::IntVal());
		$valor = $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
		return $valor < 1 ? null : IntVal($valor);
	}

	public function v_nombre($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_descripcion($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_unidadesDeMedida($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::FloatVal() :
		validacion::Optional(validacion::FloatVal());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_monedas($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::FloatVal() :
		validacion::Optional(validacion::FloatVal());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_consumos($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::FloatVal() :
		validacion::Optional(validacion::FloatVal());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_monedasPositivo($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::FloatVal()->Positive() :
		validacion::Optional(validacion::FloatVal()->Positive());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_fecha($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Date('d/m/Y') :
		validacion::Optional(validacion::Date('d/m/Y'));

		return Herramientas::formatoFechaBd($this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess));
	}

	public function v_fecha_html($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Date('Y-m-d') :
		validacion::Optional(validacion::Date('Y-m-d'));

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_fechaHora($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Date('d/m/Y H:i') :
		validacion::Optional(validacion::Date('d/m/Y H:i'));

		return Herramientas::formatoFechaHoraApi($this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess));
	}

	public function v_hora($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Date('H:i') :
		validacion::Optional(validacion::Date('H:i'));

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_numeroPositivo($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::IntVal()->Positive() :
		validacion::Optional(validacion::IntVal()->Positive());

		$valor = $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
		return $valor < 1 ? null : $valor;
	}
	public function v_formula($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Formula() :
		validacion::Optional(validacion::Formula());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_entero($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::IntVal() :
		validacion::Optional(validacion::IntVal());
		$valor = $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);

		return $valor === "" ? null : (int) $valor;
	}

	public function v_telefono($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_cheque($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_rfc($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_curp($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_claveElectoral($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_claveEmpleado($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_claveCatastral($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_contrato($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::IntVal()->Positive() :
		validacion::Optional(validacion::IntVal()->Positive());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_claveContrato($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Texto() :
		validacion::Optional(validacion::Texto());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_email($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Email() :
		validacion::Optional(validacion::Email());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_in_array($esObligatorio, $nombreCampo, $array = array(), $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::In($array) :
		validacion::Optional(validacion::In($array));
		$valor = $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
		return $valor === "" ? null : $valor;
	}

	public function v_password($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Password() :
		validacion::Optional(validacion::Password());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_userName($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::username() :
		validacion::Optional(validacion::username());

		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_netkey($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::NetKey() :
		validacion::Optional(validacion::NetKey());
		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_netkeyCode($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::NetKeyCode() :
		validacion::Optional(validacion::NetKeyCode());
		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_tokenform($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Token() :
		validacion::Optional(validacion::Token());
		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}

	public function v_sha1($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		$validacion = $esObligatorio ?
		validacion::Sha1() :
		validacion::Optional(validacion::Sha1());
		return $this->peticion($validacion, $nombreCampo, $alias, $params, $fnFail, $fnSucess);
	}
	public function v_papeleta($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		return true;
	}

	public function v_papeleta_codigosExtranos($esObligatorio, $nombreCampo, $alias = "", $params = array(), $fnFail = null, $fnSucess = null) {
		return true;
	}
}
?>
