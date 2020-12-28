<?php

	$user_id = $_GET["user_id"];
	
	include_once "../php/libraries/db_functions.php";
	open_connection();
	$user = execute_query("select * from user where id = '".$user_id."';");
	
	
	if (count($user) == 0) {
		echo "user not found";
	} else if ($user[0]["user_validated"] == 1) {
		echo "user already validated";
	} else {
		execute_insertion("update user set user_validated = 1 where id = '".$user_id."';");
		echo "user '".$user[0]["username"]."' has now been validated";
	}
	
	close_connection();
?>








