<?php
	namespace Herramientas\Generales;

	class Paginador
	{
		private $_cache;
		function __construct()
		{
			$this->_cache = new \Predis\Client([
				'scheme' => REDIS_SCHEME,
				'host'   => REDIS_HOST,
				'port'   => REDIS_PORT,
				'password'   => REDIS_PASS,

			], [ 'prefix' => REDIS_PREFIX ]);
		}

		public function get( $uri )
		{
			$datos = $this->_cache->hget('paginador', $uri);
			return json_decode( $datos );
		}

		public function set( $uri, $datos )
		{
			$datos = json_encode($datos);
			$this->_cache->hset('paginador', $uri, $datos );
		}

		public function existe( $uri )
		{
			return $this->_cache->hexists('paginador', $uri) === 1;	
		}

	}
?>
