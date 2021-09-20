<?php

include "connection.php";

$test_connection = false;

if ($test_connection) {
	$mysqlConnection = new MySqlConnection();
	$database = $mysqlConnection->getDatabase('assignment');
	$test_table = $database->getTable('account');
	echo ($test_table->getCreationQuery());
	echo '<br/>';
	$rows = $test_table->getRowsInRange(0, 20);
	var_dump($rows);
}

?>
<html>
	<head>
		<title>MySQLMover - Sender</title>
	</head>
	<body>
		<h1>Hello World</h1>
	</body>
</html>