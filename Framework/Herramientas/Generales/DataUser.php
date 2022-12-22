<?php
	namespace Herramientas\Generales;

	class DataUser
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

		public function get( $user, $key )
		{
			$datos = $this->_cache->hget("user:$user", $key);
			return json_decode( $datos );
		}

		public function set( $user, $key, $datos )
		{
			$datos = json_encode($datos);
			$this->_cache->hset("user:$user", $key, $datos );
		}

		public function del( $user, $key )
		{
			return $this->_cache->hdel("user:$user", $key);	
		}

		public function existe( $user, $key )
		{
			return $this->_cache->hexists("user:$user", $key) === 1;	
		}

		public function hscan( $user, $matchStr )
		{
			return $this->_cache->hscan("user:$user", 0, 'MATCH', $matchStr);
		}

		# ------------------------------------------------------------
		# Imagen perfial empleado
		public function tieneImagenPerfil( $user, $ancho, $alto )
		{
			$strCache = sprintf("perfil_imagen_%u_%u", $ancho, $alto );
			return $this->existe( $user, $strCache );
		}

		public function setImagenPerfil( $user, $img, $ancho, $alto  )
		{
			$strCache = sprintf("perfil_imagen_%u_%u", $ancho, $alto );
			return $this->set( $user, $strCache, [ 'imagen' => $img ] );
		}

		public function getImagenPerfil( $user, $ancho, $alto )
		{
			$strCache = sprintf("perfil_imagen_%u_%u", $ancho, $alto );
			return $this->get( $user, $strCache );
		}

		# Borra todas las imagenes que esten en cache relacionadas al perfil
		public function delImagenPerfil( $user )
		{
			$set = $this->hscan($user, "perfil_imagen_*");
			foreach ($set[1] as $key => $s)
			{
				$this->del( $user, $key );				
			}
		}
		
		# Imagen perfial afiliado
		public function tieneImagenAfiliadoPerfil( $user, $afiliado, $ancho, $alto )
		{
			$strCache = sprintf("perfil_imagen_fam_%u_%u_%u", $afiliado, $ancho, $alto );
			return $this->existe( $user, $strCache );
		}

		public function setImagenAfiliadoPerfil( $user, $afiliado, $img, $ancho, $alto  )
		{
			$strCache = sprintf("perfil_imagen_fam_%u_%u_%u", $afiliado, $ancho, $alto );
			return $this->set( $user, $strCache, [ 'imagen' => $img ] );
		}

		public function getImagenAfiliadoPerfil( $user, $afiliado, $ancho, $alto )
		{
			$strCache = sprintf("perfil_imagen_fam_%u_%u_%u", $afiliado, $ancho, $alto );
			return $this->get( $user, $strCache );
		}

		# Borra todas las imagenes que esten en cache relacionadas al perfil
		public function delImagenAfiliadoPerfil( $user, $afiliado )
		{
			$strCache = sprintf("perfil_imagen_fam_%u_*", $afiliado );
			$set = $this->hscan($user, $strCache);
			foreach ($set[1] as $key => $s)
			{
				$this->del( $user, $key );				
			}
		}
	}
?>
