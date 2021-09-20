<?php

function mysqlMoverMashal($table_headers, $columns) {
	$mashaled = new MySQLMoverMashalClass();
	foreach ($columns as $index => $column) {
		$mashaled->createProperty($table_headers[$index][0], $column);
	}
	return $mashaled;
}

class MySQLTable {
	private $name;
	
	__construct($name) {
		$this->name = $name;
	}
	
}

class MySQLDatabase {
	private $tables = [];
}


class MySqlConnection {
	private $servername = "localhost";
	private $username = "root";
	private $password = "";
	private $conn = null;
	
	private function createConnection() {
		if ($this->conn != null) {
			return;
		}
		$this->conn = new mysqli($this->servername, $this->username, $this->password);
		if ($this->conn->connect_error) {
			die("Connection failed: " . $this->conn->connect_error);
		}
	}
	
	function getConn() {
		$this->createConnection();
		return $this->conn;
	}
	
	function executeQuery($query) {
		$this->createConnection();
		return $this->conn->query($query);
	}
	
	function executeQueryFetchMultiple($query) {
		$result_rows = $this->executeQuery($query)->fetch_all();
		return $result_rows;
	}
	
	function getTableHeader($schema, $table) {
		$query = $schema != null ? "DESCRIBE " . $schema . "." . $table . ";" : "DESCRIBE " . $table . ";";
		$result_rows = $this->executeQuery($query)->fetch_all();
		return $result_rows;
	}
	
	function getDatabase($schema) {
		$tables_info_headers = $this->getTableHeader(null, "INFORMATION_SCHEMA.TABLES");
		$table_rows = $this->executeQueryFetchMultiple("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '" . $schema . "';");
		$mashaled_values = [];
		foreach ($table_rows as $index => $table_row) {
			array_push($mashaled_values, mysqlMoverMashal($tables_info_headers, $table_row));
		}
		var_dump($mashaled_values);
	}
	
}

class MySQLMoverMashalClass {
	
	public function createProperty($name, $value){
        $this->{$name} = $value;
    }
	
}


?>