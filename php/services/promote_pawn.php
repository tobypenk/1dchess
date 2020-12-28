<?php
	
	$game_id = $_GET["game_id"];
	$player = $_GET["player"];
	
	$piece = $_GET["piece"];
	$rank = $_GET["rank"];
	$file = $_GET["file"];
	
	include_once "../libraries/db_functions.php";
	
	$q = "insert into move_procedural values (".$game_id.",".$rank.",".$file.",".$rank.",".$file.",null,'".$piece."',null,null);";
	
	open_connection();
	execute_insertion($q);
	close_connection();
	
?>