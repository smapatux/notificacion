<?php

namespace Controlador\Respuestas;

use Herramientas\Generales\DataUser;
use \Herramientas\Generales\UI;
use \Modulos\Configuracion\Usuario\Perfil;
use \Modulos\Home\Perfil\Perfil as UserPerfil;

class Respuesta {
	public $tipo;
	public $datos = array();
	public $seccion;
	public $plantilla;

	public function __construct($plantilla = null, $datos = array(), $seccion = null, $tipo = "html") {
		$this->tipo      = $tipo;
		$this->datos     = $datos;
		$this->seccion   = $seccion;
		$this->plantilla = $plantilla;
	}

	public function render($_Ejecutar)
	{			
		#Ordenar en archivos esta seccion.
		$contenido_minimo = $_REQUEST['__contenido'] ?? false;		

		$dataUser = new DataUser();
		if(!isset($_SESSION['id']))
			$_SESSION['id'] = 0;
		$dataUser->set($_SESSION['id'], "REQUEST_URI", $_SERVER['REQUEST_URI']);
	
		#Perfil usuario
		$perfil = new Perfil();		
		$perfil = $perfil->obtener();				

		$this->datos           = (array) $this->datos;
		$this->datos["perfil"] = $perfil->datos['respuesta']['Usuario'];

		if ($_SESSION['id'] != 0) {
			$this->datos["perfil"]->imagen = (new UserPerfil)->getImagenPerfil($_SESSION['id']);
		}

		#Opciones menus
		if (isset($_SESSION['logueado']) && $_SESSION['logueado'] == true) {
			$ui                        = new UI();
			$menu                      = $ui->panelIzquierdo($_SESSION['id']);
			$this->datos["UI"]["menu"] = $menu;

			if (isset($_Ejecutar->uri)) {
				$this->datos["UI"]["tituloSeccion"] = $ui->tituloSeccion($_Ejecutar->uri);
				$breadcrumb                         = $ui->getBreadcrumb($_Ejecutar->clase, $_Ejecutar->funcion);
				$this->datos["UI"]["breadcrumb"]    = $breadcrumb;
			}
		}

		#SESSION - WEB SOCKETS
		#GENERAR HASH
		$this->datos['ws_session'] = $_COOKIE['noti'] ?? null;

		//$loader = new \Twig_Loader_Filesystem('Framework/Vistas/');
		$loader = new \Twig\Loader\FilesystemLoader('Framework/Vistas/');

		# SET CACHE
		$twig = new \Twig\Environment($loader, array(
			'debug' => true,
			'cache' => 'Framework/Vistas/__cache',
			// ...
		));

		$twig->addExtension(new \Twig\Extension\DebugExtension());

		$twig->addFunction(new \Twig\TwigFunction('baseMedia', function () {
			echo "/public";
		}));

		$twig->addFunction(new \Twig\TwigFunction('public', function ($path) {
			$path = ltrim($path, "/");
			echo "/public/$path";
		}));

		#---------------------------------------------------------------------------------------
		# uriActivo
		$twig->addFunction(new \Twig\TwigFunction('uriActivo', function ($nivel, $palabra) {
			$uri     = explode('/', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
			$palabra = explode('/', $palabra);

			$uri     = !isset($uri[$nivel]) ? "" : $uri[$nivel];
			$palabra = !isset($palabra[$nivel]) ? "" : $palabra[$nivel];

			return preg_match("#^$palabra$#i", $uri) ? "active" : "";
		}));

		#---------------------------------------------------------------------------------------
		# hasGrupo
		$twig->addFunction(new \Twig\TwigFunction('hasGrupo', function ($gruposValidos = []) {
			return UI::hasGrupo($gruposValidos);
		}));

		#---------------------------------------------------------------------------------------
		# url
		$twig->addFunction(new \Twig\TwigFunction('url', function ($metodo, $clase, $funcion) {
			//Buscar URL en router
			global $router;
			$clase = '\\Modulos' . str_replace('/', '\\', $clase);
			return $router->getRuta($metodo, $clase, $funcion);
		}));

		#---------------------------------------------------------------------------------------
		# token_form
		$twig->addFunction(new \Twig\TwigFunction('token_form', function () {
			global $respuesta;
			$token = $respuesta->datos['token_form'] ?? "";
			echo "<input type='hidden' name='token_form' class='token_form' value='$token'>";
		}));

		#---------------------------------------------------------------------------------------
		# getToken
		$twig->addFunction(new \Twig\TwigFunction('getToken', function () {
			global $respuesta;
			$token = $respuesta->datos['token_form'];
			echo $token;
		}));

		#---------------------------------------------------------------------------------------
		# menuBloques
		$twig->addFunction(new \Twig\TwigFunction('menuBloques', function () {
			$ui = new UI();
			return $ui->getPanelModulos();
		}));

		#---------------------------------------------------------------------------------------
		# menuBloques
		$twig->addFunction(new \Twig\TwigFunction('existe_val', function ($valor, $replace = '---') {
			return $valor ?? $replace;
		}));

		$twig->addFilter(new \Twig\TwigFilter("obrasSaldo", function ($valor) {
			if ($valor < 0) {
				echo "bg-danger text-danger";
			} elseif ($valor > 0) {
				echo "bg-success text-success";
			} else {
				echo "bg-info";
			}
		}));		

		$this->datos["__contenido"] = $contenido_minimo;

		echo $twig->render($this->plantilla, $this->datos);
	}

}
