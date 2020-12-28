<?php

	$email = $_GET["email"];
	$username = $_GET["username"];
	$password = $_GET["password"];
	$password_check = $_GET["password_check"];
	$hashed_password = md5($password);
	
	$email = filter_var($email, FILTER_SANITIZE_EMAIL);
	
	include_once "../libraries/db_functions.php";
	open_connection();
	$user_exists = count(execute_query(
		"select * from user where username = '".urlencode($username)."';"
	)) > 0 ? true : false;
	close_connection();
	$email_is_valid = filter_var($email, FILTER_VALIDATE_EMAIL);
	
	
	if ($password != $password_check) {
		
		echo json_encode([
			"status" => "failed",
			"error" => "passwords must match",
			"user"=>null
		]);
	} else if ($user_exists) {
		
		echo json_encode([
			"status" => "failed",
			"error" => "username is taken",
			"user"=>null
		]);
	} else if (!$email_is_valid) {
		
		echo json_encode([
			"status" => "failed",
			"error" => "$email is not a valid email address",
			"user"=>null
		]);
	} else if (strlen($password) < 6) {
		
		echo json_encode([
			"status" => "failed",
			"error" => "choose a password that's at least six characters long",
			"user"=>null
		]);
	} else {

		$q = "insert into user ".
			" (username,hashed_password,email) ".
			" values ".
			" ('".urlencode($username)."','".urlencode($hashed_password)."','".urlencode($email)."');";
		
		
		
		include_once "start_session.php";
		open_connection();
		$_SESSION["user_id"] = execute_insertion_and_return_insert_id($q,"user");
		close_connection();
		
		$_GET["recipient_email"] = $email;
		$_GET["recipient_username"] = $username;
		$_GET["subject"] = "verify your 1dchess account";
		$_GET["message"] = "<a href='http://www.1dchess.com/verify_account?user_id=".$_SESSION["user_id"]."'>please click this link to verify your account and start playing.</a>";
		$prevent_echo = true;
		include "send_email.php";

		include "check_session.php";
	}
	//close_connection();
	
?>








