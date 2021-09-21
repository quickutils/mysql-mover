<?php

include "mysql_connection.php";

$test_connection = false;
$mysqlConnection = new MySqlConnection();

if ($test_connection) {
	$database = $mysqlConnection->getDatabase('assignment');
	echo ($database->getCreationQuery() . '<br/>');
	$test_table = $database->getTable('branch');
	echo ($test_table->getCreationQuery());
	echo '<br/>';
	$rows = $test_table->getRowsInRange(0, 20);
	$rows_query = $test_table->buildRowsQueryInRange(0, 20);
	echo ($rows_query);
}

$databases = $mysqlConnection->getDatabases();
$database = null;
$selected_table = null;
$tables = [];
$sql_migration_query = "";
$show_schema_creation_query = isset($_GET['show-schema-creation-query']) && $_GET['show-schema-creation-query'] == 'on';
$show_table_creation_query = isset($_GET['show-table-creation-query']) && $_GET['show-table-creation-query'] == 'on';
$row_from = isset($_GET['row-from']) ? $_GET['row-from'] : 0;
$row_to = isset($_GET['row-to']) ? $_GET['row-to'] : 50;
if (isset($_GET['database'])) {
	$database = $mysqlConnection->getDatabase($_GET['database']);
	$tables = $database->getTables();
	if ($show_schema_creation_query) {
		$sql_migration_query .= $database->getCreationQuery() . "\n\n";
	}
	
	if (isset($_GET['table'])) {
		$selected_table = $mysqlConnection->getDatabase($_GET['database'])->getTable($_GET['table']);
		if ($show_table_creation_query) {
			$sql_migration_query .= $selected_table->getCreationQuery() . "\n\n";
		}
		
		$sql_migration_query .= $selected_table->buildRowsQueryInRange($row_from, $row_to) . "\n";		
	}
}
$total_rows_count = ($selected_table != null ? $selected_table->getRowsCount() : 0);

?>
<html>
	<head>
		<title>MySQLMover</title>
	</head>
	<body>
		<div style="display: flex; flex-wrap: wrap; padding: 20px; height: 100%;">
			<form style="display: flex;">
				<div>
					<h3>Select Database</h3>
					<select name="database" size="<?php echo (count($databases) + 2)?>" onchange="this.form.submit()">
						<?php
							foreach ($databases as $index => $database) {
								echo '<option ' . (isset($_GET['database']) && $_GET['database'] == $database->getSchema() ? 'selected' : '') . '>'. $database->getSchema() .'</option>';
							}
						?>
					</select>
				</div>
				<div style="margin-left: 20px;">
					<h3>Select Table</h3>
					<select name="table" size="30" onchange="this.form.submit()">
						<?php
							foreach ($tables as $index => $table) {
								echo '<option ' . (isset($_GET['table']) && $_GET['table'] == $table->getName() ? 'selected' : '') . '>'. $table->getName() .'</option>';
							}
						?>
					</select>
				</div>
				<div style="margin: 20px; margin-top: 50px;">
					<label>Table Total Rows Counts: <?php echo $total_rows_count; ?></label>
					<br/><br/>
					<label>Show Schema Creation Query</label> <input type="checkbox" name="show-schema-creation-query" <?php echo ($show_schema_creation_query ? 'checked' : ''); ?> onchange="this.form.submit()"/><br/>
					<label>Show Table Creation Query</label> <input type="checkbox" name="show-table-creation-query" <?php echo ($show_table_creation_query ? 'checked' : ''); ?> onchange="this.form.submit()"/><br/>
					<br/><br/>
					<label>Create Insertion Value</label><br/>
					From: <input type="text" name="row-from" value="<?php echo $row_from; ?>" onblur="this.form.submit()"/><br/>
					To: <input type="text" name="row-to" value="<?php echo $row_to; ?>" onblur="this.form.submit()"/><br/>
				</div>
			</form>
			<div style="flex: 1; margin: 40px;">
				<textarea style="width: 100%; height: 80%;"><?php echo $sql_migration_query; ?></textarea>
			</div>
		</div>
	</body>
</html>

