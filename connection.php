<?php

function mysqlMoverMashal($column_attr, $columns) {
	$mashaled = new MySQLMoverMashalClass();
	foreach ($columns as $index => $column) {
		$mashaled->createProperty($column_attr[$index]->Field, $column);
	}
	return $mashaled;
}

function getColumnKeyDesc($field, $key) {
	if ($key == "PRI") {
		return "PRIMARY KEY";
	} else if ($key == "UNI") {
		return "UNIQUE";
	} else if ($key == "MUL") {
		return "index(" . $field . ")";
	}
	return "";
}

function getColumnDefaultDesc($field, $defaut) {
	if ($defaut != "") {
		return "DEFAULT " . $defaut;
	}
	return "";
}

function getColumnExtraDesc($field, $extra) {
	return $extra;
}

class MySQLMoverMashalClass {
	
	public function createProperty($name, $value){
        $this->{$name} = $value;
    }
	
}

class MySQLColumn {
	public $Field;
	public $Type;
	public $Null;
	public $Key;
	public $Default;
	public $Extra;
}

class MySQLTable {
	private $properties;
	private $columns = [];
	private $column_names = [];
	private $schema;
	private $name;
	private $row_count;
	private $mysql_connection;
	private $creation_query;
	
	public function __construct($mysql_connection, $properties) {
		$this->properties = $properties;
		$this->schema = $properties[1];
		$this->name = $properties[2];
		$this->row_count = $properties[7];
		$this->mysql_connection = $mysql_connection;
		$this->fetchColumns();
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getCreationQuery() {
		return $this->creation_query;
	}
	
	public function getRowsInRange($offset, $limit) {
		//$tables_info_headers = $this->mysql_connection->getTableHeader(null, "INFORMATION_SCHEMA.TABLES");
		$rows = $this->mysql_connection->executeQueryFetchMultiple("SELECT * FROM " . $this->schema . "." . $this->name . " LIMIT " . $limit . " OFFSET " . $offset . ";");
		$mashaled_rows = [];
		foreach ($rows as $index => $row) {
			array_push($mashaled_rows, mysqlMoverMashal($this->columns, $row));
		}
		return $mashaled_rows;
	}
	
	public function buildRowsQueryInRange($offset, $limit) {
		$store_query = "";
		$rows = $this->getRowsInRange($offset, $limit);
		foreach ($rows as $index => $row) {
			array_push($mashaled_rows, mysqlMoverMashal($this->columns, $row));
		}
		return $store_query;
	}
	
	private function fetchColumns() {
		$this->creation_query = "CREATE TABLE " . $this->schema . "." . $this->name . " (\n";
		$rows = $this->mysql_connection->getTableHeader($this->schema, $this->name);
		$rows_count = count($rows);
		foreach ($rows as $index => $row) {
			$column = new MySQLColumn();
			$column->Field = $row[0];
			$column->Type = $row[1];
			$column->Null = $row[2] == 'YES';
			$column->Key = $row[3];
			$column->Default = $row[4];
			$column->Extra = $row[5];
			array_push($this->columns, $column);
			array_push($this->column_names, $column->Field);
			$this->creation_query .= "	" . $column->Field . " " . $column->Type . " " . getColumnExtraDesc($column->Field, $column->Extra) . " " . 
				(!$column->Null ? "NOT NULL" : "") . " " . getColumnDefaultDesc($column->Field, $column->Default) . " " . getColumnKeyDesc($column->Field, $column->Key) . 
				($index < $rows_count-1 ? "," : "") . "\n";
		}
		$this->creation_query .= ")";
	}
	
}

// use map for tables collection, for speed and fast lookup
class MySQLDatabase {
	private $schema;
	private $tables = [];
	private $mysql_connection;
	
	public function __construct($mysql_connection, $schema) {
		$this->schema = $schema;
		$this->mysql_connection = $mysql_connection;
		$this->fetchTables();
	}
	
	public function getTables() {
		return $this->tables;
	}
	
	public function getTable($table_name) {
		foreach ($this->tables as $index => $table) {
			if ($table->getName() == $table_name) {
				return $table;
			}
		}
		return null;
	}
	
	private function fetchTables() {
		$table_rows = $this->mysql_connection->executeQueryFetchMultiple("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '" . $this->schema . "';");
		foreach ($table_rows as $index => $table_row) {
			array_push($this->tables, new MySQLTable($this->mysql_connection, $table_row));
		}
	}
	
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
		$database = new MySQLDatabase($this, $schema);
		return $database;
	}
	
}


?>