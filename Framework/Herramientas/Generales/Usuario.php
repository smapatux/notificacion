<?php
	namespace Herramientas\Generales;

	class Usuario
	{
		private $_cache;
		function __construct()
		{
			$this->_cache = new \Predis\Client([
				'scheme' => REDIS_SCHEME,
				'host' => REDIS_HOST,
				'port' => REDIS_PORT,
				'password' => REDIS_PASS,

			], [ 'prefix' => REDIS_PREFIX ]);
		}

		public function del( $id )
		{
			$this->_cache->del("user:$id");
		}

		public function existe( $id )
		{
			return $this->_cache->exists("user:$id") === 1;	
		}

	}
?>
