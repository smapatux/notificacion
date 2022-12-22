<?php

namespace Modulos\Configuracion\Usuario;

# Herramientas
use \Herramientas\Log as Log;
use \Herramientas\VariablesGlobales as SG;
use \Herramientas\DbFormat as extraDb;

use \Respect\Validation\Validator as validacion;
use \Respect\Validation\Exceptions\ValidationException;

# Validacion
use \Herramientas\ValidacionFormat;

# Respuestas disponibles
use \Controlador\Respuestas\Respuesta;
use \Controlador\Respuestas\RespuestaJson;

use Predis\Client;

class Perfil
{
	private $_cache = null;
	private $datos = array();
	private $errores = array();
	private $seccion = 'Configuracion';
	function __construct() {
		$this->_cache = new Client([
			'scheme' => REDIS_SCHEME,
			'host'   => REDIS_HOST,
			'port'   => REDIS_PORT,
			'password'   => REDIS_PASS,
		]);
	}

	public function obtener()
	{
		$usuario = null;

		if ($_SESSION['id'] != 0)
		{
			$id = $_SESSION['id'];
			$infoBasica = $_GET["infoBasica"] ?? true;

			#Datos usuario
			if ( $this->_cache->hExists("user:$id", "usuario") == 0 || $infoBasica == false)
			{
				$usuario = new \Modulos\Configuracion\Usuario\Usuario();			

				$_GET = [
					'id' => $_SESSION['id'],
					'infoBasica' => $infoBasica
				];

				$usuario = $usuario->datosUsuario();
				$usuario = count($usuario->datos["respuesta"]) ? 
					$usuario->datos["respuesta"][0] :
					null;
				$this->_cache->hset("user:$id", "usuario", json_encode($usuario));
			}
			$usuario = json_decode( $this->_cache->hget("user:$id", "usuario") );
		}
		
		$this->datos["Usuario"] = $usuario;
		return new RespuestaJson(
				array( "respuesta" => $this->datos, "errores" => $this->errores ),
				$this->seccion
			);
	}


	public function cambioPassword()
	{
		global $dbGlobal;
		$validar = new ValidacionFormat("post");
		$password = $validar->v_password(true,"password");
		$nuevoPassword = $validar->v_password(true,"nuevoPassword");
		$confirmarPassword = $validar->v_password(true,"confirmarPassword");

		if ( $nuevoPassword != $confirmarPassword )
			$validar->anadirError("", "", "La nueva contraseña no coincide.");


		if($validar->hayError())
			$this->errores=$validar->infoErrores();
		else
		{
			$usuario = $dbGlobal->getRow("SELECT TOP 1 nip, id FROM usuarios WHERE id = $_SESSION[id]");

			if ( password_verify($password, $usuario['nip']) )
			{
				$usuario["nip"] = password_hash($nuevoPassword, PASSWORD_DEFAULT);				
				$dbGlobal->qqUpdate("usuarios", $usuario);
				$this->datos["mensaje"] = "Cambio realizado con exito.";
			}
			else
			{
				$validar->anadirError("", "", "Verifique la contraseña.");
				$this->errores=$validar->infoErrores();
			}
		}

		return new RespuestaJson(
			array( "respuesta" => $this->datos, "errores" => $this->errores ),
			$this->seccion
		);
	}


}


 ?>
