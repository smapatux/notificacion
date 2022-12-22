<?php
namespace Herramientas\TaskMaster;

use Herramientas\Log;
use Herramientas\TaskMaster\Task;

class TaskMaster
{

    private $__cache;
    private $__tareas = [];
    private $__tbl_tareas = 'dbo.Sistema_Tareas';
    private $__max_reintentos = 3;
    private $__default_path = "Servicios\Tareas";

    public function __construct()
    {

        $this->__cache = new \Predis\Client([
            'scheme'   => REDIS_SCHEME,
            'host'     => REDIS_HOST,
            'port'     => REDIS_PORT,
            'password' => REDIS_PASS,

        ], ['prefix' => REDIS_PREFIX]);

    }

    public function add($clase, $metodo, $parametros)
    {
        global $dbGlobal;

        $t = new Task(
            $this->__default_path . $clase,
            $metodo,
            $parametros
        );

        $id = $dbGlobal->qqInsert($this->__tbl_tareas, [
            'proceso'    => (string)$t,
            'nombre'     => $t->getName(),
            'reintento'  => 0,
            'procesando' => 0,
        ]);

        Log::guardarReporte("tarea", "NuevaTarea:\t" . $t->getName(), 'taskmaster');

        $this->__cache->publish('taskmaster', 'exec');

    }

    public function pendiente()
    {
        global $dbGlobal;

        $pendiente = $dbGlobal->getRow("
                SELECT TOP 1
                    proceso,
                    id_tarea
                FROM {$this->__tbl_tareas}
                WHERE
                    reintento < {$this->__max_reintentos} AND
                    procesando = 0
                ORDER BY id_tarea ASC
            ");

        $tarea = json_decode($pendiente['proceso'] ?? '');

        if ($tarea) {
            $tarea = new Task(
                $tarea->clase,
                $tarea->metodo,
                $tarea->parametros
            );

            $tarea->setId($pendiente['id_tarea']);

        }

        return $tarea;

    }

    public function hasPendientes()
    {
        global $dbGlobal;

        return $dbGlobal->getValue("
                SELECT COUNT(*)
                FROM {$this->__tbl_tareas}
                WHERE reintento < {$this->__max_reintentos} AND
                procesando = 0
            ");

    }

    public function exec()
    {
        global $dbGlobal;

        $tarea = $this->pendiente();

        if (is_null($tarea)) {
            return false;
        }

        $this->setProcesando($tarea->getId(), 1);

        echo "\n[EXEC]\t{$tarea->__log_msg()}";
        Log::guardarReporte("tarea", "\n[LOG]\t{$tarea->__log_msg()}", 'taskmaster');

        $tarea->ejecutar();

        if (!$tarea->error) {
            echo "\n[LOG]\t{$tarea->__log_msg()}";

            #borrar tarea
            $tarea->del();

            unset($tarea);

        } else {
            echo "\n[ERROR]\t{$tarea->__log_msg()}";

            #incrementar reintentos o repoprtar error
            Log::guardarReporte("tarea", "\n[ERROR]\t{$tarea->__log_msg()}", 'taskmaster');
            $tarea->incReintento();
            $this->setProcesando($tarea->getId(), 0);

        }

        return true;

    }

    public function setProcesando($id, $estado = 0)
    {
        global $dbGlobal;

        $dbGlobal->qqUpdate($this->__tbl_tareas, [
            'id_tarea'   => $id,
            'procesando' => $estado,
        ]);
    }

}
