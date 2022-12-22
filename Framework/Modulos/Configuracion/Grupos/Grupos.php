<?php
namespace Modulos\Configuracion\Grupos;

# Herramientas
use \Controlador\Respuestas\Respuesta;
use \Controlador\Respuestas\RespuestaJson;
use \Herramientas\DbFormat as extraDb;
use \Herramientas\VariablesGlobales as SG;
# Respuestas disponibles
use \Modulos\Configuracion\ControlModulos;
use \Modulos\Configuracion\Grupos\Modulos;
use \Modulos\Configuracion\Grupos\Usuarios;
use \Respect\Validation\Exceptions\ValidationException;
use \Respect\Validation\Validator as validacion;

class Grupos {

	private $datos   = array();
	private $errores = array();
	private $seccion = 'Configuracion';
	function __construct() {}

	function principal() {
		$plantilla = "Configuracion/Grupos/Listar.html";
		return new Respuesta($plantilla, $this->datos, $this->seccion);
	}

	function detalle() {
		global $dbGlobal;

		try
		{
			$id     = validacion::intVal()->setName('id')->check(SG::GET('id')) ? SG::GET('id') : 0;
			$valido = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {

			unset($_GET);
			$_GET["clave"] = $id;
			$grupo         = $this->listar();
			if (count($grupo->datos["respuesta"]['Grupos'])) {
				$grupo                = $grupo->datos["respuesta"]['Grupos'][0];
				$this->datos['grupo'] = $grupo;

				//SIstemas
				$contorlModulos          = new ControlModulos();
				$sistemas                = $contorlModulos->getSistemas();
				$this->datos['Sistemas'] = $sistemas->datos["respuesta"]["Sistemas"];
				foreach ($this->datos['Sistemas'] as $key => $sistema) {
					$idGrupo                                        = $id;
					$idSistema                                      = $sistema['idSistema'];
					$this->datos['Sistemas'][$key]['tienePermisos'] = $dbGlobal->getValue("select dbo.grupoTienePermisos($idSistema, $idGrupo)");
				}

				//Modulos usuario
				$modulos                = new Modulos();
				$modulos                = $modulos->listar();
				$this->datos['Modulos'] = $modulos->datos["respuesta"]["Modulos"];

				//Usuarios en grupo
				unset($_GET);
				$_GET['grupo']           = $id;
				$usuarios                = new Usuarios();
				$usuarios                = $usuarios->listarUsuarios();
				$this->datos['Usuarios'] = $usuarios->datos["respuesta"]["Usuarios"];
				//print_r($this->datos['Usuarios']); exit;

			} else {
				$errores = new \Modulos\Home\Principal\Errores();
				return $errores->error500("Revise los parametros enviados.");
			}

		} else {
			$errores = new \Modulos\Home\Principal\Errores();
			return $errores->error500("Revise los parametros enviados.");
		}

		$plantilla = "Configuracion/Grupos/Detalle.html";
		return new Respuesta($plantilla, $this->datos, $this->seccion);
	}

	function listar() {
		global $dbGlobal;

		try {

			$id      = validacion::optional(validacion::intVal())->check(SG::GET('clave')) ? SG::GET('clave') : 0;
			$nombre  = validacion::optional(validacion::alnum())->check(SG::GET('nombre')) ? SG::GET('nombre') : '';
			$alias   = validacion::optional(validacion::alnum())->check(SG::GET('alias')) ? SG::GET('alias') : '';
			$estatus = validacion::optional(validacion::boolVal())->check(SG::GET('activo')) ? SG::GET('activo') : '';
			$valido  = true;

		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$filtros  = "";
			$formatDb = new extraDb($filtros);

			if (!empty($id)) {
				$formatDb->where("IDGrupo", $id, "OR");
			}

			if (!empty($nombre)) {
				$formatDb->like("Grupo", $nombre, "OR");
			}

			if (!empty($alias)) {
				$formatDb->like("alias", $alias, "OR");
			}

			if ($estatus != '') {
				$formatDb->where("Estatus", $estatus);
			}

			$formatDb->extraWhere("IDGrupo != 24");

			$this->datos["Grupos"] = $dbGlobal->getArray("SELECT
						IDGrupo as id,
						Grupo,
						alias,
						Descripcion,
						IIF(Estatus = 1, 'ACTIVO', 'INACTIVO') as Estatus
					FROM sistema_grupos" . $formatDb->getConditionComplete());
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function agregar() {
		global $dbGlobal;

		$Grupo = array();
		try {
			validacion::texto()->check($_POST['nombre']);
			validacion::texto()->check($_POST['alias']);
			validacion::boolVal()->check($_POST['activo']);
			validacion::optional(validacion::texto())->check(SG::POST('descripcion'));

			$valido = true;

		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {

			$Grupo = [
				'Estatus'     => $_POST['activo'] == 'on',
				'Grupo'       => $_POST['nombre'],
				'alias'       => $_POST['alias'],
				'Descripcion' => SG::POST('descripcion'),
			];

			$Grupo['IDGrupo'] = $dbGlobal->qqInsert("Sistema_Grupos", $Grupo);

			$Grupo['Estatus'] = $Grupo['Estatus'] ? 1 : 0;

		} else {
			$Grupo           = array();
			$this->errores[] = "Datos incompletos";
		}
		$this->datos['Grupos'][] = $Grupo;

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function editar() {
		global $dbGlobal;

		$Grupo = array();
		try {
			validacion::intVal()->check($_POST['id']);
			validacion::optional(validacion::texto())->check(SG::POST('nombre'));
			validacion::optional(validacion::texto())->check(SG::POST('alias'));
			validacion::optional(validacion::boolVal())->check(SG::POST('activo'));
			validacion::optional(validacion::texto())->check(SG::POST('Descripcion'));

			$valido = true;

		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {

			$Grupo            = array();
			$Grupo['IDGrupo'] = $_POST['id'];
			if (isset($_POST['nombre'])) {
				$Grupo['nombre'] = $_POST['nombre'];
			}

			if (isset($_POST['alias'])) {
				$Grupo['alias'] = $_POST['alias'];
			}

			if (isset($_POST['Descripcion'])) {
				$Grupo['Descripcion'] = SG::POST('Descripcion');
			}

			$Grupo['Estatus'] = $_POST['activo'] == 'on';

			$dbGlobal->qqUpdate("Sistema_Grupos", $Grupo);
			$Grupo['Mensaje'] = "Cambio realizado con exito.";

		} else {
			$Grupo           = array();
			$this->errores[] = "Datos incompletos";
		}
		$this->datos['Grupos'] = $Grupo;

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

}

?>