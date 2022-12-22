<?php
namespace App\Canales;

use App\Controller\Notificacion;
use App\Repository\Session as RepoSession;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

// use Predis

class Notificaciones implements MessageComponentInterface {
	public $clients;
	private $logs;
	public $connectedUsers;
	public $connectedUsersNames;

	public function __construct() {
		$this->clients             = new \SplObjectStorage;
		$this->logs                = [];
		$this->connectedUsers      = [];
		$this->connectedUsersNames = [];

	}

	public function onOpen(ConnectionInterface $conn) {
		// system('clear');

		#LA CONEXION ES VALIDA?
		$this->clients->attach($conn);

		printf(
			"CLIENTES CONECTADOS: %u - ID - ULTIMA CONEXION: %u \n",
			$this->clients->count(),
			$conn->resourceId
		);

		#Actualiza el visor con los datos mas recientes
		// $datos = [
		// 	'accion' => 'historial',
		// 	'datos'  => $this->logs,
		// ];

		// $conn->send(json_encode($datos));

		$this->connectedUsers[$conn->resourceId] = $conn;

	}

	public function onMessage(ConnectionInterface $from, $msg) {

		// Do we have a username for this user yet?
		if (isset($this->connectedUsers[$from->resourceId]->usuario)) {

			// $msg = json_decode(str_replace("'", "\"", $msg));
			$msg = json_decode($msg);

			$mensaje = (Object) [
				"accion"    => $msg->accion ?? 'undefined',
				"usuario"   => $this->connectedUsers[$from->resourceId]->usuario,
				"datos"     => $msg->datos,
				"timestamp" => time(),
			];

			$notificacion = new Notificacion($mensaje); #Renombrar clase

			if ($notificacion->issetAccion()) {
				$is_ok = $notificacion->execAccion($from, $mensaje);

				if ($is_ok && $notificacion->hasResponse) {
					$this->sendMessage($notificacion->receptores, $notificacion->response);
				}

			}

			// If we do, append to the chat logs their message
			$this->logs[] = $mensaje;

			// $this->sendMessage(end($this->logs));

			$users_online = count($this->connectedUsers);
			echo "Usuarios: $users_online \n";

		} else {
			// If we don't this message will be their username
			# Registra el usuario a la lista de clientes. El primer mensaje es su usuario
			# Agregar validaciones...
			$session      = new RepoSession();
			$session_info = $session->get($msg);

			if ($session_info['logueado'] || $msg == 'sistema') {
				$this->connectedUsers[$from->resourceId]->usuario = $msg;
				printf("ACCESO CORRECTO\n");
			} else {
				$from->close();
				$this->clients->detach($from);
				printf("ACCESO DENEGADO\n");
			}

		}

	}

	private function sendMessage($receptores, $message) {
		# Esto se podria implementar soble $this->clients
		$session = new RepoSession();
		foreach ($this->connectedUsers as $user) {

			$session_info = $session->get($user->usuario);

			#Si el usuario ya no esta logueado, eliminar conexion

			if (isset($session_info['id']) && in_array($session_info['id'], $receptores)) {
				$user->send(json_encode($message));

			}

		}
	}

	public function onClose(ConnectionInterface $conn) {
		// Detatch everything from everywhere
		$this->clients->detach($conn);
		unset($this->connectedUsers[$conn->resourceId]);

	}

	public function onError(ConnectionInterface $conn, \Exception $e) {

		$conn->close();

	}

}
