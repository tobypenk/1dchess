<?php
	
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	$game_id = $_GET["game_id"];
	$start_rank = $_GET["start_rank"];
	$start_file = $_GET["start_file"];
	$player_id = $_GET["player_id"];
	
	include "create_board.php";
	include_once "../libraries/db_functions.php";
	include_once "../libraries/moves.php";
	include "reconcile_board.php";
	
	open_connection();
	$game = execute_query("select * from game where id = '".$game_id."';");
	$active_player = 
		($game[0]["white_player_id"] == $player_id ? "W" :
			($game[0]["black_player_id"] == $player_id ? "B" : null));
	close_connection();
	
	$player = $board[$start_rank][$start_file]["piece"]["player"];
	
	if ($initiative != $player | $active_player != $player) {
		echo json_encode(["moves" => []]);
	} else {
		$moves = scan_for_moves($board,$start_rank,$start_file);
		echo json_encode(["moves" => $moves]);
	}
	
	
	
?>