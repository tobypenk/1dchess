<?php
	
	///// UNFINISHED
	
	include "create_board.php";
	include_once "../libraries/db_functions.php";
	include_once "../libraries/moves.php";
	
	$game_id = $_GET["game_id"];
	$start_rank = $_GET["start_rank"];
	$start_file = $_GET["start_file"];
	$target_rank = $_GET["target_rank"];
	$target_file = $_GET["target_file"];
	$en_passant_rank = $_GET["en_passant_rank"] == -1 ? "null" : $_GET["en_passant_rank"];
	$en_passant_file = $_GET["en_passant_file"] == -1 ? "null" : $_GET["en_passant_file"];
	
	$q = "insert into move_procedural values (".
		$game_id.",".
		$start_rank.",".$start_file.",".
		$target_rank.",".$target_file.
		",null,null,".
		$en_passant_rank.",".$en_passant_file.
	")";
	
	open_connection();
	execute_insertion($q);
	close_connection();
	
	// everything after this is "fetch game"
	
	include "reconcile_board.php";
	include "board_to_html.php";
	
	$w_check = scan_for_check($board,"W");
	$b_check = scan_for_check($board,"B");
	
	$concluded = scan_for_conclusion($board,$initiative);
	
	open_connection();
	execute_insertion("update game set initiative = '".$initiative."' where id = '".$game_id."';");
	close_connection();
	
	echo json_encode([
		"html" => $html
		,"w_check" => $w_check
		,"b_check" => $b_check
		,"initiative" => $initiative
		,"concluded" => $concluded
		,"promote_pawn" => $promote_pawn
		,"en_passant" => $en_passant
		,"end_board"=>$end_board
		,"end_view_html"=>$end_view_html
	]);
	
	//echo json_encode($board);
	
?>