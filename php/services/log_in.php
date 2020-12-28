<?php
	
	$username = $_GET["username"];
	$password = $_GET["password"];
	
	$hashed_password = md5($password);
	
	include_once "../libraries/db_functions.php";
	open_connection();
	$user = execute_query("select * from user where username = '".urlencode($username)."' and hashed_password = '".$hashed_password."';");
	close_connection();
	
	if (count($user) == 0) {
		echo json_encode([
			"status" => "failed",
			"error" => "username and password don't match",
			"user"=>null
		]);
	} else {
		include_once "start_session.php";
		$_SESSION["user_id"] = $user[0]["id"];
		include "check_session.php";
	}

	
	
?>