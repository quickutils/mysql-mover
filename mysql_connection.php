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
	}/* else if ($key == "MUL") {
		return "index(" . $field . ")";
	}*/
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
	public $properties;
	private $columns = [];
	private $column_names = [];
	private $schema;
	private $name;
	private $row_count;
	private $mysql_connection;
	private $creation_query;
	private $insertion_column_names_cahced;
	
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
	
	public function getColumnNames() {
		return $this->column_names;
	}
	
	public function getSchema() {
		return $this->schema;
	}
	
	public function getCreationQuery() {
		return $this->creation_query;
	}
	
	public function getRowsCount() {
		return $this->row_count;
	}
	
	public function getRowsInRange($offset, $limit) {
		return $this->executeQuery("SELECT * FROM `" . $this->schema . "`.`" . $this->name . "` LIMIT " . $limit . " OFFSET " . $offset . ";");
	}
	
	public function executeQuery($sql) {
		$rows = $this->mysql_connection->executeQueryFetchMultiple($sql);
		return $this->mashalRows($rows);
	}
	
	public function executeQueryRaw($sql) {
		$result = $this->mysql_connection->executeQuery($sql);
		return $result;
	}
	
	public function mashalRow($row) {
		return mysqlMoverMashal($this->columns, $row);
	}
	
	public function mashalRows($rows) {
		$mashaled_rows = [];
		foreach ($rows as $index => $row) {
			array_push($mashaled_rows, $this->mashalRow($row));
		}
		return $mashaled_rows;
	}
	
	public function buildRowsQueryInRange($offset, $limit) {
		$store_query = "INSERT INTO `" . $this->schema . "`.`" . $this->name . "` (" . $this->insertion_column_names_cahced . ") VALUES ";
		$mashaled_rows = $this->getRowsInRange($offset, $limit);
		$mashaled_rows_count = count($mashaled_rows);
		if ($mashaled_rows_count == 0) return "";
		$column_names_count = count($this->column_names);
		foreach ($mashaled_rows as $index => $mashaled_row) {
			$store_query .= "	(";
			foreach ($this->column_names as $column_name_index => $column_name) {
				$column_value = 'null';
				if ($mashaled_row->$column_name != null && $mashaled_row->$column_name != "") {
					$column_value = "'" . $mashaled_row->$column_name . "'";
				}
				$store_query .= $column_value . ($column_name_index < $column_names_count-1 ? ", " : "");
			}
			$store_query .= ")" . ($index < $mashaled_rows_count-1 ? ", \n" : "") . "";
		}
		$store_query .= ';';
		return $store_query;
	}
	
	private function fetchColumns() {
		$this->creation_query = "CREATE TABLE IF NOT EXISTS `" . $this->schema . "`.`" . $this->name . "` (\n";
		$this->insertion_column_names_cahced = "";
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
			$this->insertion_column_names_cahced .= $column->Field . "" . ($index < $rows_count-1 ? ", " : "");
		}
		$this->creation_query .= ");";
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
	
	public function getSchema() {
		return $this->schema;
	}
	
	public function getCreationQuery() {
		return "CREATE SCHEMA IF NOT EXISTS `" . $this->schema . "`;";
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
	private $port = "3306";
	private $default_db = "mysql";
	private $conn = null;
	private $databases = [];
	
	public function __construct() {
		$this->createConnection();
		$this->fetchAllDatabases();
	}
	
	private function createConnection() {
		if ($this->conn != null) {
			return;
		}
		$this->conn = new mysqli($this->servername . ':' . $this->port, $this->username, $this->password, $this->default_db);
		if ($this->conn->connect_error) {
			die("Connection failed: " . $this->conn->connect_error);
		}
	}
	
	private function fetchAllDatabases() {
		$result_rows = $this->executeQuery("SHOW DATABASES;")->fetch_all();
		foreach ($result_rows as $index => $result_row) {
			array_push($this->databases, new MySQLDatabase($this, $result_row[0]));
		}
	}
	
	function getConn() {
		return $this->conn;
	}
	
	function executeQuery($query) {
		$result = $this->conn->query($query);
		if (!$result) die(mysqli_error($this->conn));
		return $result;
	}
	
	function executeQueryFetchMultiple($query) {
		$result_rows = $this->executeQuery($query)->fetch_all();
		return $result_rows;
	}
	
	function getTableHeader($schema, $table) {
		$query = $schema != null ? "DESCRIBE `" . $schema . "`.`" . $table . "`;" : "DESCRIBE `" . $table . "`;";
		$result_rows = $this->executeQuery($query)->fetch_all();
		return $result_rows;
	}
	
	function getDatabase($schema) {
		foreach ($this->databases as $index => $database) {
			if ($database->getSchema() == $schema) {
				return $database;
			}
		}
		return null;
	}
	
	function getDatabases() {
		return $this->databases;
	}
	
}


?>