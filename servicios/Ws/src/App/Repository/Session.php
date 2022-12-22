<?php
namespace App\Repository;

class Session {
	public $_cache;

	public function __construct() {
		$this->_cache = new \Predis\Client([
			'scheme'   => REDIS_SCHEME,
			'host'     => REDIS_HOST,
			'port'     => REDIS_PORT,
			'password' => REDIS_PASS,

		]);

	}

	public function get($id_session) {		
		//$datos = $this->_cache->get("PHPREDIS_SESSION:$id_session");
		//session_decode($datos);
		$_SESSION['logueado'] = true;
		return $_SESSION;
	}

}