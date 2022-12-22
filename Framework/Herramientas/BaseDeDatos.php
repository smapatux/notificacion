<?php

namespace Herramientas;

use \Exception;
use \Herramientas\Log as Log;
use \PDO as PDO;

class BaseDeDatos extends PDO {
	public $contadorTransacciones = 0;
	public function __construct($host = HOST_DB, $user = USUARIO_DB, $pass = PASSWORD_DB, $database = NOMBRE_DB) {
		if (DRIVER_DB == 'dblib') {
			$str_connection = DRIVER_DB . ":host=$host:1433;dbname=$database;charset=UTF-8;";
		} else {
			$str_connection = DRIVER_DB . ":Server=" . $host . ";Database=" . $database;
		}

		try
		{
			parent::__construct(
				$str_connection,
				$user,
				$pass
			);
		} catch (Exception $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		$this->run("SET LANGUAGE spanish; ");
		$this->run("SET DATEFORMAT ymd; ");

	}

	public function iniciarTransaccion() {
		try
		{
			if ($this->contadorTransacciones++ == 0) {
				parent::beginTransaction();
			}

		} catch (Exception $e) {
			throw new Exception('Transaccion: ' . $e->getMessage());
		}
	}

	public function guardarTransaccion() {
		if (--$this->contadorTransacciones == 0) {
			parent::commit();
		}
	}

	public function cancelarTransaccion() {
		$this->contadorTransacciones = 0;
		parent::rollback();
	}

	public function run($query) {
		try
		{
			$result = array();
			$rs     = parent::query($query);

			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}
			$result = $rs->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}
		return $result;
	}

	public function execProc($procedimiento, $params = array(), $debug = false) {
		if (!is_array($params)) {
			throw new Exception("Los parametros deben de estar en un arreglo.");
			return false;
		}

		$query = "EXEC " . $procedimiento . " ";
		foreach ($params as $key => $value) {
			if (!is_null($value)) {
				$query .= "@$key = '$value',";
			}

		}
		$query = rtrim($query, ",");

		if ($debug) {
			var_dump($query);
		}

		return $this->getArray($query);
	}

	public function execProcValue($procedimiento, $params = array(), $debug = false) {
		if (!is_array($params)) {
			throw new Exception("Los parametros deben de estar en un arreglo.");
			return false;
		}

		$query = "EXEC " . $procedimiento . " ";
		foreach ($params as $key => $value) {
			if (!is_null($value)) {
				$query .= "@$key = '$value',";
			}

		}
		$query = rtrim($query, ",");

		if ($debug) {
			var_dump($query);
		}

		return $this->getValue($query) ?? 0;
	}

	public function getArray($query, $debug = false) {
		try
		{
			$result = array();
			if ($debug) {
				var_dump($query);
			}

			$rs = parent::query($query);

			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new \Exception($error[2]);
			}
			$result = $rs->fetchAll(PDO::FETCH_ASSOC);

		} catch (\Exception $e) {
			throw new \Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		return $result;
	}

	public function getPaginador($query, $key = "id", $pagina = 1, $size = 100, $orden = "ASC") {
		$queryTotal     = preg_replace('/^select(.|\s)*?from/i', "SELECT COUNT(1) FROM", $query, 1);
		$totalElementos = $this->getValue($queryTotal) ?? 0;
		$pagina         = $pagina < 1 ? 1 : $pagina;
		#$query = preg_replace('/^select/i', "SELECT COUNT(1) OVER() AS __totalElementos,", $query, 1);
		$result = array();
		try
		{
			$result = array();
			$query  = "
				$query
				ORDER BY $key $orden
				OFFSET (($pagina - 1) * $size) ROWS
				FETCH NEXT $size ROWS ONLY
				";

			$rs = parent::query($query, PDO::FETCH_ASSOC);

			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}
			$result = $rs->fetchAll(PDO::FETCH_ASSOC);

			if ($rs->rowCount() > 0) {
				#$totalElementos = $result[0]['__totalElementos'];
				$total = $totalElementos / $size;
				$total += ((explode('.', $totalElementos / $size)[1] ?? 0) > 0) ? 1 : 0;
				$paginacion = array(
					'actual' => (int) $pagina,
					'total'  => (int) $total,
				);
			} else {
				$paginacion = array(
					'actual' => 1,
					'total'  => 1,
				);
			}

		} catch (Exception $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		return array($result, $paginacion);
	}

	/**
	 * Gets the row.
	 *
	 * @param      string     $query  The query
	 *
	 * En caso de fallar, devuelve arraglo vacio
	 *
	 * @return     array      Resultado.
	 */
	public function getRow($query) {
		try
		{
			$result = array();
			$rs     = parent::query($query);
			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}

		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		if ($rs->rowCount() != 0) {
			$result = $rs->fetch(PDO::FETCH_ASSOC);
		}
		return $result;
	}

	public function getColumn($query) {
		try
		{
			$result = array();
			$rs     = parent::query($query);

			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		while ($row = $rs->fetchColumn()) {
			$result[] = $row;
		}

		return $result;
	}

	public function getValue($query, $debug = false) {
		try
		{
			$result = null;
			if ($debug) {
				var_dump($query);
			}

			$rs = parent::query($query);
			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}
		$result = $rs->fetchColumn(0);

		return $result;
	}

	public function getJson($query, $debug = false) {

		try
		{
			$result = json_decode($this->getValue($query, $debug));
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		return $this->getValue($query, $debug);
	}

	public function getRowCount($table, $criteria = "") {

		try
		{
			$query = "SELECT count(*) FROM " . $table . (($criteria != "") ? " WHERE " . $criteria : "");
			$rs    = parent::query($query);
			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		$result = $rs->fetchColumn(0);
		return $result;
	}

	public function getFieldCollection($query) {
		try
		{
			$result = null;
			$rs     = parent::query($query);
			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		$result = $rs->fetchObject();

		return $result;
	}

	protected function getFields($table) {
		if (!$table) {
			throw new Exception("Cannot create fields because table name is not properly set");
			return false;
		}

		try
		{
			$fields = $this->getArray("SELECT
					C.COLUMN_NAME,
					C.DATA_TYPE,
					C.IS_NULLABLE,
					T.CONSTRAINT_TYPE,
					C.COLUMN_DEFAULT,
					IIF(COLUMNPROPERTY ( OBJECT_ID('$table') , C.COLUMN_NAME , 'IsIdentity' )=1,'auto_increment','') as Extra
				FROM INFORMATION_SCHEMA.COLUMNS C
				left join INFORMATION_SCHEMA.KEY_COLUMN_USAGE K on C.COLUMN_NAME=k.COLUMN_NAME and C.TABLE_NAME=K.TABLE_NAME
				LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS T on c.TABLE_NAME=t.TABLE_NAME AND T.CONSTRAINT_NAME = K.CONSTRAINT_NAME
				WHERE
				IIF(CHARINDEX('.', '$table') > 0, CONCAT(C.TABLE_SCHEMA,'.',C.TABLE_NAME), C.TABLE_NAME) = '$table'");

			//CONCAT(REPLACE(C.TABLE_SCHEMA, 'dbo', ''),C.TABLE_NAME) = REPLACE('log_sistema', '.', '')

			#sql 2008
			/*
	        $fields = $this->getArray("SELECT
	        C.COLUMN_NAME,
	        C.DATA_TYPE,
	        C.IS_NULLABLE,
	        T.CONSTRAINT_TYPE,
	        C.COLUMN_DEFAULT,
	        (
	        CASE COLUMNPROPERTY ( OBJECT_ID(c.TABLE_SCHEMA+'.'+t.TABLE_NAME) , C.COLUMN_NAME , 'IsIdentity' )
	        WHEN '1' THEN 'auto_increment'
	        ELSE ''
	        END
	        ) as Extra
	        FROM
	        INFORMATION_SCHEMA.COLUMNS C
	        left join
	        INFORMATION_SCHEMA.KEY_COLUMN_USAGE K on C.COLUMN_NAME=k.COLUMN_NAME and C.TABLE_NAME=K.TABLE_NAME
	        LEFT JOIN
	        INFORMATION_SCHEMA.TABLE_CONSTRAINTS T on c.TABLE_NAME=t.TABLE_NAME AND T.CONSTRAINT_NAME = K.CONSTRAINT_NAME
	        WHERE
	        C.TABLE_NAME = '".$table."'");
*/
		} catch (PDOException $e) {
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}

		$structure = array("primary" => array(), "data" => array());
		$numerics  = "/^(int|tinyint|float|bigint|decimal)/";
		foreach ($fields as $field) {
			$element = array("type" => (preg_match($numerics, $field["DATA_TYPE"])) ? "number" : "string", "null" => ($field["IS_NULLABLE"] == "NO") ? false : true, "default" => $field["COLUMN_DEFAULT"], "autonum" => ($field["Extra"] == "auto_increment") ? true : false, "value" => null, "exists" => false);
			if ($field["CONSTRAINT_TYPE"] == "PRIMARY KEY") {
				$structure["primary"][$field["COLUMN_NAME"]] = $element;
			} else {
				$structure["data"][$field["COLUMN_NAME"]] = $element;
			}
		}

		return $structure;
	}

	private function isMultiArray($arrayData) {
		rsort($arrayData);
		return isset($arrayData[0]) && is_array($arrayData[0]);
	}

	private function makePairs($table, $data) {
		if (!$table) {
			throw new Exception("Cannot create fields because table name is not properly set");
			return false;
		}

		if (!is_array($data)) {
			throw new Exception("Data must be an associative array");
			return false;
		}

		if ($this->isMultiArray($data)) {
			if (!is_array($data[0])) {
				throw new Exception("Data must be an associative array");
				return false;
			}
		}

		$structure = $this->getFields($table);

		foreach ($structure["primary"] as $pkk => $pkd) {
			if (isset($data[$pkk])) {
				$structure["primary"][$pkk]["value"]  = $data[$pkk];
				$structure["primary"][$pkk]["exists"] = true;
			}
		}

		$datosTabla = $structure["data"];
		if ($this->isMultiArray($data)) {
			$structure["data"] = [];
		}

		foreach ($datosTabla as $pkk => $pkd) {
			if ($this->isMultiArray($data)) {
				foreach ($data as $index => $d) {
					if (isset($d[$pkk])) {
						$structure["data"][$index][$pkk]           = $datosTabla[$pkk];
						$structure["data"][$index][$pkk]["value"]  = $d[$pkk];
						$structure["data"][$index][$pkk]["exists"] = true;
					}
				}

			} else {
				if (isset($data[$pkk])) {
					$structure["data"][$pkk]["value"]  = $data[$pkk];
					$structure["data"][$pkk]["exists"] = true;
				}
			}
		}

		return $structure;
	}

	private function qqGetVal($element, $for = "insert") {
		if ($for == "insert" && $element["autonum"]) {
			return "NULL";
		}
		switch ($element["type"]) {
		case "number":
			$valor = null;
			if ($element["exists"]) {
				$valor = (is_numeric($element["value"]) || is_bool($element["value"])) ?
				$element['value'] :
				preg_match("/^true/i", $element["value"]);
			}
			return $valor;
			break;
		case "string":
			return ($element["value"] !== null) ? "'" . $element["value"] . "'" : "NULL";
			break;
		}
	}

	public function qqUpdate($table, $data, $debug = false) {
		$tdata = $this->makePairs($table, $data);
		if (!$tdata) {
			return false;
		}

		$query = "UPDATE " . $table . " SET ";

		$queryparts = array();

		foreach ($tdata["primary"] as $tk => $tp) {
			$where = " WHERE ";

			if (!$tp["exists"]) {
				throw new Exception("Cannot update record because primary key input is incomplete");
				return false;
			}

			$where .= $tk . "=" . $this->qqGetVal($tp, "update");
		}
		foreach ($tdata["data"] as $tk => $tp) {
			if ($tp["exists"]) {
				$queryparts[] = $tk . "=" . $this->qqGetVal($tp);
			} else {
				if (array_key_exists($tk, $data)) {
					$queryparts[] = $tk . "=NULL";
				}

			}
		}
		$query .= implode(", ", $queryparts) . $where;

		if ($debug) {
			print_r($query);
		}

		$rs = parent::query($query);
		if (parent::errorCode() != '00000') {
			$error = parent::errorInfo();
			throw new Exception($error[2]);
		}
		$result = $rs->fetchAll();
		return $result;
	}

	/**
	 *     qqInsert - Ejemplo:
	 *    #$datos = array( "nombre" => "a", "descripcion" => "b" , "valor" => 10);
	 *    $datos[] = array( "nombre" => "a", "descripcion" => "b" , "valor" => 10);
	 *    $datos[] = array( "nombre" => "a", "descripcion" => "b" , "valor" => 10);
	 *    $datos[] = array( "nombre" => "a", "descripcion" => "b" , "valor" => 10);
	 *    $datos[] = array( "nombre" => "a", "descripcion" => "b" , "valor" => 10);
	 *    $datos[] = array( "nombre" => "a", "descripcion" => "b" , "valor" => 10);
	 *    $dbGlobal->qqInsert("testInsert", $datos);
	 */
	public function qqInsert($table, $data, $debug = false) {
		$tdata = $this->makePairs($table, $data);

		if (!$tdata) {
			return false;
		}

		$query       = "INSERT INTO " . $table . " ";
		$queryfields = array();
		$queryvalues = array();

		//foreach ($tdata["primary"] as $tk=>$tp) {
		//$queryfields[] = $tk;
		//$queryvalues[] = $this->qqGetVal($tp);
		//}
		//
		if ($this->isMultiArray($data)) {
			//N Ver como quitar esto (Foreach)
			foreach ($tdata["data"][0] as $tk => $tp) {
				if ($tp["exists"]) {
					$queryfields[] = $tk;
				}

			}

			foreach ($tdata["data"] as $indice => $elemento) {
				foreach ($elemento as $tk => $tp) {
					if ($tp["exists"]) {
						$queryvalues[$indice][] = $this->qqGetVal($tp);
					}
				}
			}
		} else {
			foreach ($tdata["data"] as $tk => $tp) {
				if ($tp["exists"]) {
					$queryfields[] = $tk;
					$queryvalues[] = $this->qqGetVal($tp);
				}
			}
		}

		$query .= "(" . implode(", ", $queryfields) . ") VALUES ";
		if ($this->isMultiArray($data)) {
			foreach ($queryvalues as $value) {
				$query .= "(" . implode(", ", $value) . "),";
			}
			$query = rtrim($query, ",");
		} else {
			$query .= "(" . implode(", ", $queryvalues) . ")";
		}

		try {

			if ($debug) {
				print_r($query);
			}

			$rs = parent::query($query);
			if (parent::errorCode() != '00000') {
				$error = parent::errorInfo();
				throw new Exception($error[2]);
			}

		} catch (Exception $e) {
			Log::guardarReporte('db-errores-insert', 'DATABASE ERROR: ' . $e->getMessage(), 'Excepciones');
			throw new Exception('DATABASE ERROR: ' . $e->getMessage());
		}
		return $this->lastInsertId();
	}

}

function mssqlEscape($data) {
	if (is_numeric($data)) {
		return $data;
	}

	$unpacked = unpack('H*hex', $data);
	return '0x' . $unpacked['hex'];
}
