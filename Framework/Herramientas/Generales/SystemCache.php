<?php
	namespace Herramientas\Generales;

	class SystemCache
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

		public function get( $path )
		{
			$datos = $this->_cache->hget("system_cache", $path);
			return json_decode( $datos );
		}

		public function set( $path, $datos )
		{
			$datos = json_encode($datos);
			$this->_cache->hset("system_cache", $path, $datos );
		}

		public function del( $path )
		{
			return $this->_cache->hdel("system_cache", $path);	
		}

		public function existe( $path )
		{
			return $this->_cache->hexists("system_cache", $path) === 1;	
		}

		// public function hscan( $path, $matchStr )
		// {
		// 	return $this->_cache->hscan("system_cache", 0, 'MATCH', $matchStr);
		// }
		
	}
?>
