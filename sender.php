<?php

include "mysql_connection.php";

$mysqlConnection = new MySqlConnection();
$databases = $mysqlConnection->getDatabases();
$database = null;
$selected_table = null;
$tables = [];
$sql_migration_query = "";
$selected_columns = null;
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
		if ($selected_table != null) {
			if ($show_table_creation_query) {
				$sql_migration_query .= $selected_table->getCreationQuery() . "\n\n";
			}
			
			$selected_columns = $selected_table->getRowsInRange($row_from, $row_to);
			$sql_migration_query .= $selected_table->buildRowsQueryInRange($row_from, $row_to) . "\n";	
		}			
	}
}
$total_rows_count = ($selected_table != null ? $selected_table->getRowsCount() : 0);

?>
<html>
	<head>
		<title>MySQLMover - Sender</title>
		<style>
		table {
		  font-family: arial, sans-serif;
		  border-collapse: collapse;
		  width: 100%;
		  overflow: auto;
		}

		td, th {
		  border: 1px solid #dddddd;
		  text-align: left;
		  padding: 8px;
		}

		tr:nth-child(even) {
		  background-color: #dddddd;
		}
		</style>
	</head>
	<body>
		<div style="display: flex; padding: 20px; height: 100%;">
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
					<label>Include Schema Creation Query</label> <input type="checkbox" name="show-schema-creation-query" <?php echo ($show_schema_creation_query ? 'checked' : ''); ?> onchange="this.form.submit()"/><br/>
					<label>Include Table Creation Query</label> <input type="checkbox" name="show-table-creation-query" <?php echo ($show_table_creation_query ? 'checked' : ''); ?> onchange="this.form.submit()"/><br/>
					<br/><br/>
					<label>Create Insertion Value</label><br/>
					From: <input type="text" name="row-from" value="<?php echo $row_from; ?>" onblur="this.form.submit()"/><br/>
					To: <input type="text" name="row-to" value="<?php echo $row_to; ?>" onblur="this.form.submit()"/><br/>
					<br/><br/>
					<h3>Receiver</h3>
					Replacement Schema: <input type="text" name="replacement-db" id="replacement-db" value="<?php echo isset($_GET['replacement-db']) ? $_GET['replacement-db'] : ''; ?>"/><br/>
					Url: <input type="text" name="receiver-url" id="receiver-url" value="<?php echo isset($_GET['receiver-url']) ? $_GET['receiver-url'] : 'http://localhost:1212/receiver.php'; ?>"/><br/>
					Password: <input type="text" name="receiver-password" id="receiver-password" value="<?php echo isset($_GET['receiver-password']) ? $_GET['receiver-password'] : '12345'; ?>"/><br/>
					<br/><br/>
					<input onclick="sendMigrationQuery()" style="padding: 10px; color: white; background: blue; outline: none; border: none; border-radius: 10px;" type="button" value="Migrate to specified route"
						onkeydown="return event.key != 'Enter';"/>
				</div>
			</form>
			<div style="flex: 1; margin: 40px;">
				<?php if ($selected_table != null) {?>
				<table>
					<tr>
						<?php
							foreach ($selected_table->getColumnNames() as $index => $column_name) {
								echo "<th>$column_name</th>";
							}
						?>
					</tr>
					<?php
						foreach ($selected_columns as $index => $selected_column) {
							echo "<tr>";
							foreach ($selected_table->getColumnNames() as $index => $column_name) {
								echo "<td>" . $selected_column->$column_name . "</td>";
							}
							echo "</tr>";
						}
					?>
				</table>
				<?php }?>
			</div>
		</div>
	</body>
<script>
function sendMigrationQuery() {
	const replaceSchema = document.getElementById('replacement-db').value;
	const receiverUrl = document.getElementById('receiver-url').value;
	const receiverPassword = document.getElementById('receiver-password').value;
	let sqlMigrationQuery = `<?php echo str_replace('`', '\`', $sql_migration_query) ?>`;
	if (replaceSchema != "") {
		sqlMigrationQuery = sqlMigrationQuery.replaceAll('`<?php echo $_GET['database'] ?>`', `\`${replaceSchema}\``);
	}
	//console.log(receiverUrl + " " + receiverPassword);
	let httpRequest = new XMLHttpRequest();
	httpRequest.open("POST", receiverUrl + "?pass=" + receiverPassword, true);
	httpRequest.setRequestHeader('Content-Type', 'html/text');
	httpRequest.onerror = function(response, status, error) { alert(error); };
	httpRequest.onload = function() { alert(this.responseText); }
	//console.log(sqlMigrationQuery);
	httpRequest.send(sqlMigrationQuery);
}
</script>
</html>