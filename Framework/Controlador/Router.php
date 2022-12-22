<?php
	namespace Controlador;

	class Router
	{
		private $_cache;
		private $_cache_key_router = "router_rutas";
		private $_rutas = array(
			"GET"    => array(),
			"POST"   => array(),
			"PUT"    => array(),
			"DELETE" => array(),
		);

		public $valor       = "";
		private $__path_api = "#^/*api/#i";

		function __construct() {
			$this->_cache = new \Predis\Client([
				'scheme'   => REDIS_SCHEME,
				'host'     => REDIS_HOST,
				'port'     => REDIS_PORT,
				'password' => REDIS_PASS,
			], ['prefix' => REDIS_PREFIX]);
		}

		public function limpiaRutas() {
			$this->_cache->del($this->_cache_key_router);
		}

		public function existenRutas() {
			return $this->_cache->exists($this->_cache_key_router) === 1;
		}

		# Redirecciona a una URI en especifico
		public static function redireccion($ruta = '', $variables = [], $foraneo = FALSE) {
			if (isset($_REQUEST["__contenido"]) && $_REQUEST["__contenido"] === "1") {
				$variables["__contenido"] = "1";
			}

			$urlVariables = '';
			if (count($variables) > 0) {
				$urlVariables = '?';
				$urlVariables .= http_build_query($variables);
			}

			$ruta = ltrim($ruta, '/');

			if ($foraneo) {
				header('Location: ' . $ruta . $urlVariables);
			} else {			
				header('Location: ' . DIRECCION . $ruta . $urlVariables);
			}

			exit();
		}

		# Redireccionar a la pagina donde se origino la llamada
		public static function noRedireccionar($variables = [])
		{
			if (isset($_REQUEST["__contenido"]) && $_REQUEST["__contenido"] === "1") {
				$variables["__contenido"] = "1";
			}
			$urlVariables = '';
			if (count($variables) > 0) {
				$urlVariables = '?';
				$urlVariables .= http_build_query($variables);
			}
			if (isset($_SERVER['HTTP_REFERER'])) {
				$host   = 'http://' . $_SERVER['HTTP_HOST'] . DIRECCION;
				$origen = str_replace($host, '', $_SERVER['HTTP_REFERER']);
			} else {
				$origen = '';
			}
			header('Location: ' . DIRECCION . $origen . $urlVariables);
			exit();
		}

		#Agrega uris a el router 
		public function add($uri, $clase, $funcion, $tipoRequest, $icono = "") {
			#$__router = new Predis\Client('tcp://127.0.0.1:6379?password=sam.smapa');
			$datos              = new \stdClass();
			$datos->uri         = trim($uri, '/');
			$datos->clase       = $clase;
			$datos->funcion     = $funcion;
			$datos->tipoRequest = $tipoRequest;
			$datos->icono       = $icono;
			$this->_cache->hset($this->_cache_key_router, $tipoRequest . '|' . $uri, str_replace('\\', '|', $clase) . ',' . $funcion . ',' . $icono);
			array_push($this->_rutas[$datos->tipoRequest], $datos);
		}

		# Agregar metodos POST al router.
		public function POST($uri, $clase, $funcion, $icono = "") {			
			$this->add($uri, $clase, $funcion, "POST", $icono);
		}

		# Agregar metodos GET al router.
		public function GET($uri, $clase, $funcion, $icono = "") {
			$this->add($uri, $clase, $funcion, "GET", $icono);
		}

		# Agregar metodos PUT al router.
		public function PUT($uri, $clase, $funcion, $icono = "") {
			$this->add($uri, $clase, $funcion, "PUT", $icono);
		}

		# Agregar metodos DELETE al router.
		public function DELETE($uri, $clase, $funcion, $icono = "") {
			$this->add($uri, $clase, $funcion, "DELETE", $icono);
		}

		# Analiza todas las rutas registradas en el sitio para generar
		# la respuesta.
		public function proceso()
		{
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				$metodo = isset($_POST["__metodo"]) ? $_POST["__metodo"] : "POST";
			}
			else {
				$metodo = $_SERVER["REQUEST_METHOD"];
			}

			if ($_SERVER["REQUEST_METHOD"] != "GET"){
				parse_str(file_get_contents('php://input'), $_POST);
			}

			$uri = $this->getUri();
			$r   = $this->_cache->hget($this->_cache_key_router, $metodo . "|" . $uri);	

			if (isset($r))
			{
				list($clase, $funcion, $icono) = explode(',', $r);
				$r              = new \stdClass();
				$r->uri         = $uri;
				$r->clase       = str_replace('|', '\\', $clase);
				$r->funcion     = $funcion;
				$r->tipoRequest = $metodo;
				$r->icono       = $icono;
			}
			return $r;
		}

		public function obtenerRutas()
		{
			$respuesta = array();
			#$sistemaRgx = "/\\Modulos\\(?P<sistema>[\w\d]*)\\(?P<modulo>.*)\\.*/i";
			$sistemaRgx = '/\\\\Modulos\\\\(?P<sistema>[\w\d]*)\\\\(?P<modulo>[\w\d]*)\\\\*.*/';

			foreach (array_keys($this->_rutas) as $metodo){
				foreach ($this->_rutas[$metodo] as $ruta){
					preg_match($sistemaRgx, $ruta->clase, $matches);
					$respuesta[] = array(
						"sistema"  => $matches["sistema"],
						"modulo"   => $matches["modulo"],
						"ruta"     => $ruta->clase,
						"atributo" => $ruta->funcion,
						"uri"      => $ruta->uri,
						"icono"    => $ruta->icono,
					);
				}
			}
			return $respuesta;
		}

		public function getRuta($metodo, $clase, $funcion)
		{
			$uri       = '/';
			$findClass = array_keys(array_column($this->_rutas[$metodo], "clase"), $clase);

			foreach ($findClass as $key => $iMetodo)
			{
				if ($this->_rutas[$metodo][$iMetodo]->funcion == $funcion)
				{
					$uri = $this->_rutas[$metodo][$iMetodo]->uri;
				}
			}
			return '/' . $uri;
		}

		public function getUri()
		{
			$uri = isset($_GET['uri']) ? $_GET['uri'] : '/';
			$uri .= substr($uri, -1) == '/' ? '' : '/';
			$uri = ($uri == '' || $uri == '/') ? '/' : ltrim($uri, '/');

			return $uri;
		}

		public function requestIsApi()
		{
			return preg_match($this->__path_api, $this->getUri(), $matches);
		}

	}

?>