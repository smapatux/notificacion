<?php
/**
 *
 */
namespace Modulos\Configuracion;

# Herramientas
use \Controlador\Respuestas\RespuestaJson;
use \Herramientas\DbFormat as extraDb;

# Respuestas disponibles
use \Herramientas\VariablesGlobales as SG;

# Validacion
use \Respect\Validation\Exceptions\ValidationException;
use \Respect\Validation\Validator as validacion;

class ControlModulos {
	private $datos   = array();
	private $errores = array();
	private $seccion = 'Configuracion';

	function __construct() {
	}

	function usuarioModulos() {
		global $dbGlobal;

		try {
			$IdEmpleado = validacion::intVal()->setName('IdEmpleado')->check(SG::GET('IdEmpleado')) ? $_GET['IdEmpleado'] : 0;
			$Ruta       = validacion::optional(validacion::regex('/^\\\\([\\w_\\d]+\\\\{0,1})+$/'))->setName('Ruta')->check(SG::GET('Ruta')) ? SG::GET('Ruta') : 0;
			$valido     = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$filtros = "";

			$formatDb = new extraDb($filtros);

			$parametrosWhere = "";
			if (!empty($IdEmpleado)) {
				$formatDb->where("ea.IDEmpleado", $IdEmpleado);
			}

			if (!empty($Ruta)) {
				$formatDb->where("'\Modulos\'+st.Sistema+'\'+sm.Modulo", $Ruta);
			}

			if (!empty($filtros)) {
				$parametrosWhere .= "WHERE  $filtros ";
			}

			$this->datos["Modulos"] = $dbGlobal->getArray("SELECT
						sm.id,
						'\Modulos\'+st.nombre+'\'+sm.Modulo Ruta,
						sm.Modulo,
						sm.Alias
					FROM Sistema_Tipo st
					INNER JOIN Sistema_Modulos sm ON sm.id_sistema = st.IDSistema
					INNER JOIN Sistema_Usuario_Modulos s_u_m ON s_u_m.idModulo = sm.id
					INNER JOIN dbo.usuarios usr ON usr.id = s_u_m.idUsuario
					$parametrosWhere ORDER BY Ruta");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function listarAtributos() {
		global $dbGlobal;

		try {
			$idModulo   = validacion::optional(validacion::intVal())->setName('idModulo')->check(SG::GET('idModulo')) ? SG::GET('idModulo') : 0;
			$idSistema  = validacion::optional(validacion::intVal())->setName('idSistema')->check(SG::GET('idSistema')) ? SG::GET('idSistema') : 0;
			$idAtributo = validacion::optional(validacion::intVal())->setName('idAtributo')->check(SG::GET('idAtributo')) ? SG::GET('idAtributo') : 0;

			$valido = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$query       = "";
			$filtroLocal = "";
			$formatDb    = new extraDb($filtroLocal);
			if (!empty($idModulo)) {
				$formatDb->where("idModulo", $idModulo);
			}

			if (!empty($idSistema)) {
				$formatDb->where("id_sistema", $idSistema);
			}

			if (!empty($idAtributo)) {
				$formatDb->where("idAtributo", $idAtributo);
			}

			if (!empty($filtroLocal)) {
				$query = " WHERE $filtroLocal";
			}

			$this->datos["Atributos"] = $dbGlobal->getArray("SELECT
						IDAtributo,
						IDModulo,
						Atributo,
						dbo.getRutaModuloPadre(IDModulo, '') AS Ruta
					FROM Sistema_Atributos
					INNER JOIN Sistema_Modulos sm on sm.id = IDModulo $query");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	function listarPermisos() {
		global $dbGlobal;

		try {
			$valido = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {

			$this->datos["Permisos"] = $dbGlobal->getArray("SELECT IDAtributo, Ruta, Atributo FROM Sistema_Atributos ORDER BY Ruta ");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	# Mover funcion a clase Atributos
	function buscarAtributos() {
		global $dbGlobal;

		try {
			$IDAtributo = validacion::optional(validacion::intVal())->check(SG::GET('IDAtributo')) ? SG::GET('IDAtributo') : 0;
			$valido     = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$filtros  = "";
			$formatDb = new extraDb($filtros);

			if (!empty($IDAtributo)) {
				$formatDb->where("IDAtributo", $IDAtributo);
			}

			$strWhere                = !empty($filtros) ? " WHERE $filtros" : "";
			$this->datos["Permisos"] = $dbGlobal->getArray("SELECT
						IDAtributo
						,Ruta
						,Atributo
						FROM Sistema_Atributos $strWhere ORDER BY Ruta ");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	function listarModulos() {
		global $dbGlobal;

		$this->datos["Modulos"] = $dbGlobal->getArray("SELECT
					sm.id,
					'\Modulos\'+st.nombre+'\'+sm.Modulo Ruta,
					sm.Modulo,
					sm.Alias
				FROM Sistema_Tipo st
				INNER JOIN Sistema_Modulos sm ON sm.id_sistema = st.IDSistema ORDER BY ruta");

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function getSistemas() {
		global $dbGlobal;

		# Agregar filtros para realizar busquedas.

		$this->datos['Sistemas'] = $dbGlobal->getArray("SELECT
					idSistema,
					nombre,
					ISNULL(alias, nombre) as alias,
					show_menu
				FROM Sistema_Tipo");

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);

	}

	function actualizarIconos() {
		global $dbGlobal;

		global $router;

		$router->limpiaRutas();
		include "Framework/Rutas.php";
		$rutas = $router->obtenerRutas();

		foreach ($rutas as $indice => $ruta) {
			#Ignora rutas API
			$re = '/^api\/*/i';
			if (!preg_match($re, $ruta["uri"], $matches)) {
				$dbGlobal->run("UPDATE Sistema_Atributos SET icono = '$ruta[icono]' WHERE Ruta='$ruta[ruta]' AND Atributo='$ruta[atributo]'");
			}

		}

		$this->datos['mensaje'] = 'ok';

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	function actualizarDB() {
		ini_set('max_execution_time', 0);
		global $dbGlobal;

		global $router;

		$router->limpiaRutas();
		include "Framework/Rutas.php";
		$rutas = $router->obtenerRutas();

		$operacionesRealizadas                  = new \stdClass;
		$operacionesRealizadas->insertSistemas  = 0;
		$operacionesRealizadas->insertModulos   = 0;
		$operacionesRealizadas->insertAtributos = 0;

		# Insertando nuevos elementos
		foreach ($rutas as $indice => $ruta) {
			$re = '/\\\\(?P<modulo>[\w\d]*)/';
			preg_match_all($re, $ruta["ruta"], $matches);

			$idSistema = $dbGlobal->getValue("SELECT IDSistema FROM Sistema_Tipo WHERE nombre = '$ruta[sistema]'");
			if (!$idSistema) {
				$idSistema = $dbGlobal->qqInsert("Sistema_Tipo", [
					"nombre" => $ruta["sistema"],
				]);
				$operacionesRealizadas->insertSistemas++;
			}

			$idPadre = 0;
			for ($offSet = 2; $offSet < count($matches["modulo"]); $offSet++) {
				$nivel  = $offSet - 2;
				$modulo = $matches["modulo"][$offSet];

				$modulo = $dbGlobal->getRow("
							SELECT
								id,
								id_padre
							FROM
								Sistema_Modulos
							WHERE
								id_sistema = $idSistema AND
								modulo = '$modulo' AND
								nivel =  $nivel AND
								id_padre = $idPadre");

				if (!isset($modulo['id'])) {
					$idModulo = $dbGlobal->qqInsert("Sistema_Modulos", [
						"id_sistema" => $idSistema,
						"modulo"     => $matches["modulo"][$offSet],
						"nivel"      => $nivel,
						"id_padre"   => $idPadre,
					]);
					$idPadre = $idModulo;
					$operacionesRealizadas->insertModulos++;
				} else {
					$idPadre  = $modulo['id'];
					$idModulo = $modulo['id'];

				}
			}

			$idAtributo = $dbGlobal->getValue("SELECT IDAtributo FROM Sistema_Atributos WHERE IDModulo = $idModulo AND Atributo = '$ruta[atributo]' AND Ruta = '$ruta[ruta]'");
			if (!$idAtributo) {
				# Agregando atributo
				$idAtributo = $dbGlobal->qqInsert("Sistema_Atributos", [
					"IDModulo" => $idModulo,
					"Ruta"     => $ruta['ruta'],
					"icono"    => $ruta['icono'],
					"Uri"      => "/" . $ruta['uri'],
					"Atributo" => $ruta['atributo'],
				]);

				$re = '/^api\/*/i';
				if (!preg_match($re, $ruta["uri"], $matches)) {
					# Agregando menus del atributo
					$id = $dbGlobal->qqInsert("Sistema_Menus", array(
						[
							"TipoMenuId" => 1,
							"AtributoId" => $idAtributo,
							"show"       => $ruta['icono'] == "" ? 0 : 1,
						],
						[
							"TipoMenuId" => 2,
							"AtributoId" => $idAtributo,
							"show"       => 1,
						],
						[
							"TipoMenuId" => 3,
							"AtributoId" => $idAtributo,
							"show"       => 1,

						],
					));
				}

				$operacionesRealizadas->insertAtributos++;
			}

			$rutas[$indice]["IDSistema"]  = $idSistema;
			$rutas[$indice]["IDModulo"]   = $idModulo;
			$rutas[$indice]["IDAtributo"] = $idAtributo;
		}

		$rutasExistentes = implode(',', array_column($rutas, 'IDAtributo'));

		# Borrando permisos ya no usados de usuarios, grupos
		$dbGlobal->run("
				DELETE dbo.Sistema_Usuario_Permisos WHERE idAtributo IN (
					SELECT IDAtributo FROM dbo.Sistema_Atributos WHERE IDAtributo NOT IN ( $rutasExistentes )
				)
			");

		$dbGlobal->run("
				DELETE dbo.Sistema_Grupos_Permisos WHERE idAtributo IN (
					SELECT IDAtributo FROM dbo.Sistema_Atributos WHERE IDAtributo NOT IN ( $rutasExistentes )
				)
			");

		# Borrando elementos obsoletos
		# Borrando atributos
		/*
			$valor = "";
			$columnas = array_column($rutas, 'IDAtributo');
			foreach (array_column($rutas, 'IDAtributo') as $id)
				$valor .= (String)$id . ",";
			$valor = rtrim ($valor, ',');

			$dbGlobal->run("DELETE FROM Sistema_Atributos WHERE IDAtributo NOT IN ($valor)");

			# Borrando modulos
			$valor = "";
			$columnas = array_column($rutas, 'IDModulo');
			foreach (array_column($rutas, 'IDModulo') as $id)
				$valor .= (String)$id . ",";
			$valor = rtrim ($valor, ',');

			$dbGlobal->run("DELETE FROM Sistema_Modulos WHERE IDModulo NOT IN ($valor)");

			# Borrando sistemas
			$valor = "";
			$columnas = array_column($rutas, 'IDSistema');
			foreach (array_column($rutas, 'IDSistema') as $id)
				$valor .= (String)$id . ",";
			$valor = rtrim ($valor, ',');
			$dbGlobal->run("DELETE FROM Sistema_Tipo WHERE IDSistema NOT IN ($valor)");
			*/

		$this->datos["Configuracion"]["Modulos"] = [
			"Mensaje"               => "Operacion realizada con exito.",
			"OperacionesRealizadas" => $operacionesRealizadas,
		];

		$resp            = new \stdClass;
		$resp->respuesta = $this->datos;
		$resp->errores   = $this->errores;
		return new RespuestaJson((Array) $resp, $this->seccion);

	}

	function permisosDesarrollo() {
		global $dbGlobal;

		$dbGlobal->run("INSERT INTO Sistema_Usuario_Permisos
				select 9, IDAtributo from Sistema_Atributos
				where IDAtributo not in (
				select idAtributo from Sistema_Usuario_Permisos where idUsuario = 9
				)");

		$this->datos["Configuracion"]["Modulos"]["Mensaje"] = "Operacion realizada con exito.";

		$resp            = new \stdClass;
		$resp->respuesta = $this->datos;
		$resp->errores   = $this->errores;

		return new RespuestaJson((Array) $resp, $this->seccion);

	}

	public function getRutaModulo($id = 0) {
		global $dbGlobal;

		#echo "select idmodulo from Sistema_Atributos where IDAtributo = $id";
		$idModulo = $dbGlobal->getValue("select idmodulo from Sistema_Atributos where IDAtributo = $id");
		$ruta     = $this->moduloPadre($idModulo, "");

		echo "Ruta: | $ruta | <br>";
	}

	public function moduloPadre($idModulo, $ruta = "") {
		global $dbGlobal;

		$modulo = $dbGlobal->getRow("select id_padre, modulo from Sistema_Modulos where id = $idModulo");

		$ruta = $modulo['modulo'] . " - " . $ruta;

		return $modulo['id_padre'] ?
		$this->moduloPadre($modulo['id_padre'], $ruta) :
		$ruta;

	}

}

?>