<?php
namespace App\Repository;

use Doctrine\DBAL\DriverManager;

class Notificacion {

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

	public function getByid($id) {
		try {

			#CREAR
			# - Abstraer clase de BD

			$conn = DriverManager::getConnection($this->connectionParams);

			$sql = "
	            SELECT
                     n_t.titulo as titulo
                    ,n_n.descripcion as texto
                    ,n_t.icono as icono
                    ,IIF(n_r.visto = 1, n_t.texto_visto, n_t.texto_no_visto ) as color
                    ,CONCAT('/notificaciones/ver?view=', n_r.hash) as url
	            FROM Notificacion.notificaciones n_n
	            INNER JOIN Notificacion.receptores n_r ON n_r.notificacion_id = n_n.id
	            INNER JOIN Notificacion.tipos n_t ON n_t.id = n_n.tipo_id
	            WHERE n_n.id = {$id}
            ";

			$stmt = $conn->query($sql);

			if ($stmt->rowCount() != 0) {
				$result = $stmt->fetch();
			}

		} catch (Exception $e) {
			var_dump($e->getMessage(\n));
		}

		return $result;

	}

	public function getReceptores($id) {
		$result = [];
		try {

			$conn = DriverManager::getConnection($this->connectionParams);

			$sql = "
	            SELECT
	            	receptor_id as id
            	FROM Notificacion.receptores WHERE notificacion_id = {$id}
            ";

			$stmt = $conn->query($sql);

			while ($row = $stmt->fetchColumn()) {
				$result[] = $row;
			}

		} catch (Exception $e) {
			var_dump($e->getMessage(\n));
		}

		return $result;

	}

};
