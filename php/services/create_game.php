<?php
	
	$creator_id = $_GET["creator_id"];
	$q = "insert into game (white_player_id) values (".$creator_id.");";
	include_once "../libraries/db_functions.php";
	
	open_connection();
	$game_id = execute_insertion_and_return_insert_id($q,"game");
	close_connection();
	
	echo json_encode([
		"game_id" => $game_id,
		"query"=>$q
	]);

	
?>