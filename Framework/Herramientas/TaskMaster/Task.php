<?php
namespace Herramientas\TaskMaster;

use Herramientas\Log;

class Task {

	private $__id;
	private $__clase;
	private $__metodo;
	private $__parametros;
	private $__formatExecTime = 'Y-m-d H:m:s';

	public $error;
	public $resultado;

	public function __construct($clase, $metodo, $parametros) {
		$this->__clase      = $clase;
		$this->__metodo     = $metodo;
		$this->__parametros = $parametros;

	}

	public function __log_msg() {
		return "{$this->getExecTime()}\tTAREA\t{$this->getId()} - {$this->getName()}";
	}

	public function setId($id) {
		$this->__id = $id;
	}

	public function getId() {
		return $this->__id;
	}

	public function getName() {
		return $this->__clase . '@' . $this->__metodo;
	}

	public function ejecutar() {

		Log::guardarReporte(
			'tarea',
			"\n[LOG]\t{$this->__log_msg()}",
			'taskmaster');

		try {

			if (!class_exists($this->__clase)) {
				throw new \Exception("No existe la clase", 1);
			}

			$clase = new $this->__clase;

			if (!method_exists($clase, $this->__metodo)) {
				throw new \Exception("No existe el metodo", 1);
			}

			$this->resultado = call_user_func(
				[ & $clase, $this->__metodo],
				$this->__parametros
			);

			unset($clase);
			clearstatcache();

			$this->error = false;

		} catch (\Exception $e) {
			Log::guardarReporte(
				'tarea',
				"\n[Exception]\t{$this->__log_msg()}\t{$e}",
				'taskmaster');
			$this->error = true;

		}

	}

	public function del() {
		global $dbGlobal;

		$dbGlobal->run("DELETE FROM dbo.Sistema_Tareas WHERE id_tarea = {$this->__id}");

	}

	public function incReintento() {
		global $dbGlobal;

		$dbGlobal->run("
                UPDATE dbo.Sistema_Tareas
                SET
                    reintento = ( reintento + 1 )
                WHERE
                    id_tarea = {$this->__id}"
		);

	}

	public function __toString() {
		return json_encode([
			'clase'      => $this->__clase,
			'metodo'     => $this->__metodo,
			'parametros' => $this->__parametros,

		]);
	}

	public function getExecTime() {
		return date($this->__formatExecTime);
	}

}
