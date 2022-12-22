<?php
namespace App\Controller;

use App\Repository\Notificacion as RepoNotificacion;
use App\Repository\Notificaciones as RepoNotificaciones;
use App\Repository\Session as RepoSession;

class Notificacion {

	private $__id;
	private $__accion;
	private $__usuario;
	private $__datos;
	private $__timestap = '/';

	private $__acciones = ['notificacion', 'new_notify', 'get_no_leidas', 'set_revisado', 'getEstatusRevisado'];

	public $feedback = false;

	public $connectionParams;

	public function __construct($mensaje) {
		// $this->__id = $id ?? '';
		$this->__accion   = $mensaje->accion;
		$this->__usuario  = $mensaje->usuario;
		$this->__datos    = $mensaje->datos;
		$this->__timestap = $mensaje->timestamp;

		#Unificar o cambiar nombres
		$this->repoNotificacion   = new RepoNotificacion();
		$this->repoNotificaciones = new RepoNotificaciones();

	}

	public function issetAccion() {
		return in_array($this->__accion, $this->__acciones);
	}

	public function execAccion($from, $mensaje) {
		#ARREGLAR ESTA COSA... NO USAR CASE .1.
		switch ($this->__accion) {
		case 'notificacion':return $this->execNotificacion($from, $mensaje);
			break;
		case 'new_notify':return $this->execNewNotify($from, $mensaje);
			break;
		case 'get_no_leidas':return $this->execGetNoLeidas($from, $mensaje);
			break;
		case 'set_revisado':return $this->execSetRevisado($from, $mensaje);
			break;
		case 'getEstatusRevisado':return $this->execGetEstatusRevisado($from, $mensaje);
			break;
		default:return false;
			break;
		}

	}

	#NO SE USA
	public function execNotificacion($from, $mensaje) {

		$mensaje = [
			"accion"    => $mensaje->accion,
			"usuario"   => $from->resourceId,
			"datos"     => [
				'titulo' => 'XXNN Cumpleañeros',
				'texto'  => 'X cumpleañero(s) hoy',
				'icono'  => 'fa fa-birthday-cake',
				'url'    => '/',
			],
			"timestamp" => time(),
		];

		return $mensaje;
	}

	public function execGetNoLeidas($from, $mensaje) {
		$session              = new RepoSession();
		$session_info         = $session->get($mensaje->usuario);
		$notificaciones       = $this->repoNotificaciones->getByUserid($session_info['id']);
		$total_notificaciones = $this->repoNotificaciones->totalNoLeidasByUserid($session_info['id']);

		$this->receptores = [$session_info['id']];

		$this->response = [
			"accion"    => 'set_no_leidas',
			"usuario"   => $from->resourceId,
			"datos"     => [
				'notificaciones' => $notificaciones,
				'total'          => $total_notificaciones,
			],
			"timestamp" => time(),
		];

		$this->hasResponse = true;

		$exec_ok = true;

		return $exec_ok;
	}

	public function execNewNotify($from, $mensaje) {

		$notificacion     = $this->repoNotificacion->getById($mensaje->datos->notificacion);
		$this->receptores = $this->repoNotificacion->getReceptores($mensaje->datos->notificacion);

		$exec_ok = false;

		$this->hasResponse = true;

		if ($this->hasResponse) {
			$this->response = [
				"accion"    => 'notificacion',
				"usuario"   => $from->resourceId,
				"datos"     => $notificacion,
				"timestamp" => time(),
			];
		}

		$exec_ok = true;

		return $exec_ok;
	}

	public function execSetRevisado($from, $mensaje) {
		$session      = new RepoSession();
		$session_info = $session->get($mensaje->usuario);

		$notificaciones   = $this->repoNotificaciones->setRevisionByUserid($session_info['id'], false);
		$this->receptores = [$session_info['id']];

		$this->response = [
			"accion"    => 'set_revisado',
			"usuario"   => $from->resourceId,
			"datos"     => [
				'accion' => true,
			],
			"timestamp" => time(),
		];

		$this->hasResponse = true;

		$exec_ok = true;

		return $exec_ok;
	}

	public function execGetEstatusRevisado($from, $mensaje) {
		$session      = new RepoSession();
		$session_info = $session->get($mensaje->usuario);

		$estatus          = $this->repoNotificaciones->getEstatusRevisado($session_info['id']);
		$this->receptores = [$session_info['id']];

		$this->response = [
			"accion"    => 'getEstatusRevisado',
			"usuario"   => $from->resourceId,
			"datos"     => [
				'estatus' => $estatus,
			],
			"timestamp" => time(),
		];

		$this->hasResponse = true;

		$exec_ok = true;

		return $exec_ok;
	}

}
