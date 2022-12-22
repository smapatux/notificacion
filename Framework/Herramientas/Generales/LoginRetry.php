<?php
	namespace Herramientas\Generales;

	class LoginRetry
	{
		public $counter = 0;

		private $_cache;
		private $timeInit = 30;
		private $timeDisabled = 60 * 5;
		function __construct( $usuario = null )
		{
			$this->_cache = new \Predis\Client([
				'scheme' => REDIS_SCHEME,
				'host'   => REDIS_HOST,
				'port'   => REDIS_PORT,
				'password'   => REDIS_PASS,

			], [ 'prefix' => REDIS_PREFIX ]);

			if ( isset( $usuario ) )
				$this->counter = ( $this->existe( $usuario ) && $this->get( $usuario ) >= 3 ) ?
					$this->disabled( $usuario ) : $this->incr( $usuario );
		}

		public function get( $sessionKey )
		{
			return $this->_cache->get("loginRetry:$sessionKey");
		}

		public function cuentaHabilitada( $sessionKey )
		{
			return $this->get( $sessionKey ) <= 3;
		}

		public function incr( $sessionKey )
		{
			$count = $this->_cache->incr("loginRetry:$sessionKey");
			$this->_cache->expire("loginRetry:$sessionKey", $this->timeInit);
			return $count;
		}

		public function disabled( $sessionKey )
		{
			$this->incr( $sessionKey );
			$this->_cache->expire("loginRetry:$sessionKey", $this->timeDisabled);
			return -1;
		}

		public function existe( $sessionKey )
		{
			return $this->_cache->exists("loginRetry:$sessionKey", $sessionKey) === 1;	
		}

		public function del( $sessionKey )
		{
			return $this->_cache->del("loginRetry:$sessionKey");	
		}

	}
?>
