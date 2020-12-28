<?php

	include_once "../libraries/db_functions.php";
	
	$game_id = $_GET["game_id"];
	
	open_connection();
	execute_insertion("delete from move_procedural where game_id = '".$game_id."';");
	close_connection();
?>