<?php
namespace Herramientas\Generales;

class UI {
	function __construct() {

	}

	public $colores = array(
		//'bg-green',
		//'bg-primary',
		//'bg-aqua',
		//'bg-orange',
		//'bg-blue',
		//'bg-purple',
		//'bg-maroon',
		//'bg-red',
		//'bg-navy',
		//'bg-fuchsia',
		//'bg-lime',
		//'bg-light-green',
		//'bg-deep-orange',
		'bg-blue-grey',
	);

	public function urlPermitidos($Urls = array()) {
		global $dbGlobal;

		$urls  = "";
		$query = "";
		foreach ($Urls as $url) {
			$urls .= "'" . $url['url'] . "',";
			$query .= " (Uri = '$url[url]' and atributo = '$url[funcion]') or";
		}

		$query = rtrim($query, "or");

		$urls = $dbGlobal->getArray("SELECT Uri, atributo FROM dbo.getPermisosUsuario($_SESSION[id]) WHERE $query");

		foreach ($urls as $key => $url) {
			$alias                 = explode('/', $url["Uri"]);
			$urls[$key]["alias"]   = $this->formatoAlias(end($alias));
			$urls[$key]["destino"] = end($alias);
		}

		return $urls;
	}

	public function tituloSeccion($uri = "/") {

		$uri     = explode('/', $uri);
		$sizeUri = sizeof($uri);
		$seccion = empty($uri[$sizeUri - 2]) ? "SMAPA" : $uri[$sizeUri - 2];

		array_pop($uri);
		if (isset($uri[$sizeUri - 3])) {
			$seccionPadre["nombre"] = $this->formatoAlias($uri[$sizeUri - 3]);
			array_pop($uri);
			$seccionPadre["uri"] = "/" . implode('/', $uri);
		} else {
			$seccionPadre["nombre"] = "";
		}

		return array(
			"seccion"      => $this->formatoAlias($seccion),
			"seccionPadre" => $seccionPadre,
		);

	}

	public function formatoAlias($titulo = "") {
		return ucfirst(str_replace('-', ' ', $titulo));
	}

	public function panelIzquierdo($idUsuario = 0) {
		global $dbGlobal;

		$menu = $dbGlobal->getArray("SELECT
					icono,
					Uri,
					alias
				FROM dbo.getPermisosUsuario( $idUsuario ) gPU
				INNER JOIN Sistema_Menus sm ON sm.AtributoId = gPU.attr_usuario
				WHERE sm.TipoMenuId = 1 and sm.show = 1");

		return $this->generarArrayMenu($menu);
	}

	public function getPanelModulos() {
		global $dbGlobal;

		$menu = $dbGlobal->getArray("SELECT
					icono,
					Uri,
					alias
				FROM dbo.getPermisosUsuario( $_SESSION[id] ) gPU
				INNER JOIN Sistema_Menus sm ON sm.AtributoId = gPU.attr_usuario
				WHERE sm.TipoMenuId = 3 and sm.show = 1");

		$menu = $this->generarArrayMenu($menu);

		$uri     = explode('/', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
		$sizeUri = sizeof($uri);
		$ref     = $menu;

		for ($i = 1; $i < $sizeUri; $i++) {
			if (empty($uri[$i])) {
				break;
			}

			if (isset($ref[$uri[$i]]['subMenu'])) {
				$ref = $ref[$uri[$i]]['subMenu'];
			} else {
				$ref = array();
				break;
			}
		}

		$i          = 0;
		$sizeColors = sizeof($this->colores);
		$bloques    = array();
		foreach ($ref as $key => $m) {
			if(isset($m['detalles'])){
				$bloques[] = array(
					'alias' => $m['detalles']['alias'],
				 	'icono' => $m['detalles']['icono'],
				 	'uri'   => $m['detalles']['uri'],
					'color' => $this->colores[$i],
				);
				$i = ($i == $sizeColors - 1) ? 0 : $i + 1;				
			}
		}
		return $bloques;
	}

	public function getBreadcrumb($clase = "", $funcion = "") {
		global $dbGlobal;

		$breadcrumb = $dbGlobal->getRow("SELECT TOP 1
					icono,
					Uri,
					alias
				FROM dbo.getPermisosUsuario( $_SESSION[id] ) gPU
				INNER JOIN Sistema_Menus sm ON sm.AtributoId = gPU.attr_usuario
				WHERE sm.TipoMenuId = 2 AND sm.show = 1 AND Ruta = '$clase' AND Atributo = '$funcion'");

		if (count($breadcrumb)) {
			$alias = explode('/', $breadcrumb['Uri']);
			$alias = end($alias);
			$alias = $this->formatoAlias($alias);

			$elementosBreadcrum[] = array(
				'uri'   => '',
				'icono' => $breadcrumb['icono'],
				'alias' => $alias,
			);

		} else {
			$breadcrumb['Uri'] = "/";
		}

		$uri = preg_replace('/\/[a-z\-]*$/', '', $breadcrumb['Uri']);
		if ($breadcrumb['Uri'] === "/") {
			$elementosBreadcrum = array();
		}

		while (strlen($uri) > 0) {
			$breadcrumb = $dbGlobal->getRow("SELECT
						icono,
						Uri,
						alias
					FROM dbo.getPermisosUsuario( $_SESSION[id] ) gPU
					INNER JOIN Sistema_Menus sm ON sm.AtributoId = gPU.attr_usuario
					WHERE sm.TipoMenuId = 2 AND sm.show = 1 AND Uri = '$uri'");

			if (isset($breadcrumb['Uri'])) {
				$alias = explode('/', $breadcrumb['Uri']);
				$alias = $this->formatoAlias(end($alias));

				$elementosBreadcrum[] = array(
					'uri'   => $breadcrumb['Uri'],
					'icono' => $breadcrumb['icono'],
					'alias' => $alias,
				);
			}
			$uri = preg_replace('/\/[a-z\-\d]+$/', '', $uri);

		}

		return array_reverse($elementosBreadcrum);
	}

	public function generarArrayMenu($menu) {
		$tmp = array();
		foreach ($menu as $key => $value) {
			$m       = explode('/', $value['Uri']);
			$sizeUri = sizeof($m);
			unset($r);
			for ($i = $sizeUri - 1; $i > 0; $i--) {
				$nombreElemento = $m[$i] . (is_numeric($m[$i]) ? "a" : "");
				if (!isset($r)) {
					$r     = array();
					$alias = $this->formatoAlias($nombreElemento);

					$r[$nombreElemento]["detalles"] = array(
						'icono' => $value['icono'],
						'uri'   => $value['Uri'],
						'alias' => $alias,
					);
				} else {
					$a = $r;
					unset($r);
					$r[$nombreElemento]["subMenu"] = $a;
				}

			}
			$tmp = array_merge_recursive($tmp, $r);
		}
		return $tmp;
	}

	static public function hasGrupo($gruposValidar = []) {
		global $dbGlobal;

		$str = '';
		foreach ($gruposValidar as $value) {
			$str .= "'$value',";
		}

		$str = rtrim($str, ',');

		$usuario = $_SESSION['id'];

		$existe = $dbGlobal->getValue("SELECT
					COUNT(*)
				FROM dbo.getGruposDelUsuario( $usuario )
				where alias IN ( $str ) OR 9 = $usuario");

		return intval($existe) > 0;

	}

}
?>
