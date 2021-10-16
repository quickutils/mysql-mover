<?php

$password = 12345;
if ($_SERVER['REQUEST_METHOD'] != "POST") {
    echo "BOOYAHKASHA";
    return;
}
if ($_GET['pass'] != $password) {
    echo "Incorrect password";
    return;
}

include "mysql_connection.php";

$raw_query = file_get_contents('php://input');
$mysql_connection = new MySqlConnection();
$queries = $parts = explode(";\n", $raw_query);
foreach ($queries as $index => $query) {
    if (trim($query) == "") continue;
    //echo $query;
    $result = $mysql_connection->executeQuery($query);
}
echo 'SUCCESS';

?>