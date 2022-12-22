<?php
	namespace Herramientas\Generales;

	class TokenSession
	{
		private $_cache;
		private $timeSessionLive = 30;
		function __construct()
		{
			$this->_cache = new \Predis\Client([
				'scheme' => REDIS_SCHEME,
				'host'   => REDIS_HOST,
				'port'   => REDIS_PORT,
				'password'   => REDIS_PASS,

			], [ 'prefix' => REDIS_PREFIX ]);
		}

		public function get( $sessionKey )
		{
			$datos = $this->_cache->get("token:$sessionKey");
			return json_decode( $datos );
		}

		public function setex( $sessionKey, $datos )
		{
			$datos = json_encode($datos);
			$this->_cache->setex("token:$sessionKey", $this->timeSessionLive, $datos );
		}

		public function existe( $sessionKey )
		{
			return $this->_cache->exists("token:$sessionKey", $sessionKey) === 1;	
		}

	}
?>
