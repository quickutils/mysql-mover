<?php

include "connection.php";

$mysqlConnection = new MySqlConnection();
$database = $mysqlConnection->getDatabase('assignment');

?>
<html>
	<head>
		<title>MySQLMover - Sender</title>
	</head>
	<body>
		<h1>Hello World</h1>
	</body>
</html>