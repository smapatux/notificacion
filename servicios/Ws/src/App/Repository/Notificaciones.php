<?php
namespace App\Repository;

use Doctrine\DBAL\DriverManager;

class Notificaciones {

	public function __construct() {

		$this->connectionParams = [
			'dbname'   => NOMBRE_DB,
			'user'     => USUARIO_DB,
			'password' => PASSWORD_DB,
			'host'     => HOST_DB,
			'driver'   => 'pdo_' . DRIVER_DB,
			'charset'  => 'UTF8',

		];
	}

	public function getByUserid($id, $vistas = 0) {
		try {

			#CREAR
			# - Abstraer clase de BD

			$conn = DriverManager::getConnection($this->connectionParams);

			$sql = "
	            SELECT TOP 10
				     n_t.titulo as titulo
				    ,n_n.descripcion as texto
				    ,n_t.icono as icono
				    ,IIF(n_r.visto = 1, n_t.texto_visto, n_t.texto_no_visto ) as color
                    -- ,n_t.url as url
                    -- ,n_r.hash as hash
                    ,CONCAT('/notificaciones/ver?view=', n_r.hash) as url
				FROM Notificacion.notificaciones n_n
				INNER JOIN Notificacion.receptores n_r ON n_r.notificacion_id = n_n.id
				INNER JOIN Notificacion.tipos n_t ON n_t.id = n_n.tipo_id
				WHERE n_r.receptor_id = {$id} AND n_r.visto = {$vistas}
				ORDER BY n_n.id DESC
            ";

			$stmt = $conn->query($sql);

			if ($stmt->rowCount() != 0) {
				$result = $stmt->fetchAll();
			} else {
				$result = [];
			}

		} catch (Exception $e) {
			var_dump($e->getMessage(\n));
		}

		return $result;

	}

	public function totalNoLeidasByUserid($id) {
		try {

			#CREAR
			# - Abstraer clase de BD

			$conn = DriverManager::getConnection($this->connectionParams);

			$sql = "
	            SELECT
				    count(*)
				FROM Notificacion.notificaciones n_n
				INNER JOIN Notificacion.receptores n_r ON n_r.notificacion_id = n_n.id
				INNER JOIN Notificacion.tipos n_t ON n_t.id = n_n.tipo_id
				WHERE n_r.receptor_id = {$id} AND n_r.visto = 0
            ";

			$stmt = $conn->query($sql);

			$result = $stmt->fetchColumn(0);

		} catch (Exception $e) {
			var_dump($e->getMessage(\n));
		}

		return $result;

	}

	public function setRevisionByUserid(int $id, int $revisado) {
		try {
			try {
				# - Abstraer clase de BD
				$conn = DriverManager::getConnection($this->connectionParams);
				$sql  = "
						UPDATE Notificacion.pendientesRevisar
						SET revisado = {$revisado}
						WHERE usuario_id = {$id}
				        ";

				$stmt   = $conn->query($sql);
				$result = $stmt->execute();

			} catch (Exception $e) {
				var_dump($e);
			}

		} catch (Exception $e) {
			var_dump($e->getMessage(\n));

		}

		return $result;

	}

	public function getEstatusRevisado(int $usuario_id) {
		$result = false;
		try {
			#GET
			# - Abstraer clase de BD
			$conn = DriverManager::getConnection($this->connectionParams);

			$sql = "
				SELECT ISNULL((
					SELECT
						revisado
					FROM Notificacion.pendientesRevisar WHERE usuario_id = {$usuario_id}
				), 0) as revisado
			";

			$stmt   = $conn->query($sql);
			$result = $stmt->fetchColumn(0);

		} catch (Exception $e) {
			var_dump($e);
		}

		return $result;

	}

};
