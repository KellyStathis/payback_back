<?php
	// Connect to MySQL database news via php user
	$mysqli = new mysqli('localhost', 'php', 'phpuserpass', 'payback_app');
	 
	if($mysqli->connect_errno) {
		printf("Connection Failed: %s\n", $mysqli->connect_error);
		exit;
	}
?>
