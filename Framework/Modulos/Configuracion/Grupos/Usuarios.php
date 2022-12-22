<?php
namespace Modulos\Configuracion\Grupos;

# Herramientas
use \Controlador\Respuestas\RespuestaJson as RespuestaJson;
use \Controlador\__Base;
use \Herramientas\VariablesGlobales as SG;
# Respuestas disponibles
use \Modulos\Configuracion\Grupos\Grupos;
use \Modulos\Configuracion\Usuario\Usuario;
use \Respect\Validation\Exceptions\ValidationException;
use \Respect\Validation\Validator as validacion;

class Usuarios extends __Base {
	private $datos   = array();
	private $errores = array();
	private $seccion = 'Configuracion';
	function __construct() {}

	public function listar() {
		global $dbGlobal;

		try {
			$IDEmpleado = validacion::intVal()->setName('IdEmpleado')->check(SG::GET('IdEmpleado')) ? $_GET['IdEmpleado'] : 0;
			$valido     = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido) {
			$this->datos["Grupos"] = $dbGlobal->getArray("SELECT
						s_g.IDGrupo,
						s_g.Grupo,
						s_g.Descripcion,
						s_g.Estatus
					FROM Sistema_Grupos s_g
					INNER JOIN Sistema_Usuario_Grupos s_u_g ON s_u_g.IDGrupo = s_g.IDGrupo
					INNER JOIN Empleados_Acceso ea ON ea.id = s_u_g.idUsuario
					WHERE IDEmpleado = $IDEmpleado ORDER BY Grupo");
		}

		return new RespuestaJson(
			array("respuesta" => $this->datos, "errores" => $this->errores),
			$this->seccion
		);
	}

	public function listarUsuarios() {
		global $dbGlobal;

		try {
			$grupo  = validacion::intVal()->setName('grupo')->check(SG::GET('grupo')) ? $_GET['grupo'] : 0;
			$valido = true;
		} catch (ValidationException $exception) {
			$this->errores[] = $exception->getMainMessage();
			$valido          = false;
		}

		if ($valido)
		{
			$this->datos["Usuarios"] = $dbGlobal->getArray("SELECT
						u.id,
						u.id_empleado as id_personal,
						e.clave_empleado as clave,
						dbo.empleadoNombreCompleto(e.id_personal) as NombreCompleto,
						u.usuario
					FROM Sistema_Usuario_Grupos sug
					INNER JOIN usuarios u ON u.id = sug.idUsuario
					INNER JOIN RecursosHumanos.Personal e ON e.id_personal = u.id_empleado WHERE IDGrupo = $grupo");

		}

		return new RespuestaJson(
			["respuesta" => $this->datos, "errores" => $this->errores],
			$this->seccion
		);
	}

	public function eliminar() {
		global $dbGlobal;
		$validar = $this->setValidacion("post");

		# Validar si existe el usuario
		$id = $validar->v_clavePrincipal(true, 'id');
		if (!$validar->hayError()) {
			if (!$dbGlobal->getValue("SELECT count(*) FROM dbo.usuarios WHERE id = $id")) {
				$validar->anadirError("", "", "Usuario invalido");
			}
		}

		# Existe grupo?
		$grupo = $validar->v_claveForanea(true, 'grupo');
		if (!$validar->hayError()) {
			if (!$dbGlobal->getValue("SELECT count(*) FROM dbo.Sistema_Grupos WHERE IDGrupo = $grupo")) {
				$validar->anadirError("", "", "Grupo invalido");
			}
		}

		# El usuario existe en el grupo?
		if (!$validar->hayError()) {
			if (!$dbGlobal->getValue("SELECT dbo.existeUsuarioEnGrupo($id, $grupo)")) {
				$validar->anadirError("", "", "El usuario no esta registrado en el grupo");
			}
		}

		$datos = [];
		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$dbGlobal->run("DELETE FROM dbo.Sistema_Usuario_Grupos WHERE IDGrupo = $grupo AND idUsuario = $id");
			$datos["Mensaje"] = "El usuario a sido eliminado del grupo.";
		}

		return $this->json(
			["respuesta" => $datos, "errores" => $this->errores],
			$this->seccion
		);

	}

	public function guardar() {
		global $dbGlobal;
		$validar = $this->setValidacion("post");

		# Validar si existe el usuario
		$id = $validar->v_clavePrincipal(true, 'id');
		if (!$validar->hayError()) {
			if (!$dbGlobal->getValue("SELECT count(*) FROM dbo.usuarios WHERE id = $id")) {
				$validar->anadirError("", "", "Usuario invalido");
			}
		}

		# Existe grupo?
		$grupo = $validar->v_claveForanea(true, 'grupo');
		if (!$validar->hayError()) {
			if (!$dbGlobal->getValue("SELECT count(*) FROM dbo.Sistema_Grupos WHERE IDGrupo = $grupo")) {
				$validar->anadirError("", "", "Grupo invalido");
			}
		}

		# El usuario existe en el grupo?
		if (!$validar->hayError()) {
			if ($dbGlobal->getValue("SELECT dbo.existeUsuarioEnGrupo($id, $grupo)")) {
				$validar->anadirError("", "", "El usuario ya esta registrado en el grupo");
			}
		}

		$datos = [];
		if ($validar->hayError()) {
			$this->errores = $validar->infoErrores();
		} else {
			$dbGlobal->run("INSERT INTO dbo.Sistema_Usuario_Grupos (IDGrupo, idUsuario) VALUES ( $grupo, $id )");

			unset($_GET);
			$_GET['id']                = $id;
			$usuario                   = new Usuario();
			$usuario                   = $usuario->buscar();
			$datos["datos"]["Usuario"] = $usuario->datos["respuesta"]->Usuarios[0] ?? null;

			unset($_GET);
			$_GET['clave']                      = $grupo;
			$grupo                              = new Grupos();
			$grupo                              = $grupo->listar();
			$datos["datos"]["Usuario"]["Grupo"] = $grupo->datos["respuesta"]["Grupos"][0] ?? null;

			$datos["Mensaje"] = "Usuario asignados correctamente.";
		}

		return $this->json(
			["respuesta" => $datos, "errores" => $this->errores],
			$this->seccion
		);

	}

}

?>