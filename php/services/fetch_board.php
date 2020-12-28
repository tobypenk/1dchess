<?php
	
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	include "create_board.php";
	include_once "../libraries/db_functions.php";
	include_once "../libraries/moves.php";
	include "reconcile_board.php";
	
	$prevent_echo = true;
	include "check_session.php";
	
	include "board_to_html.php";
	$w_check = scan_for_check($board,"W");
	$b_check = scan_for_check($board,"B");
	
	$concluded = scan_for_conclusion($board,$initiative);
	
	open_connection();
	$game = execute_query("select ".
		" game.*".
		", white.username white_player ".
		", black.username black_player ".
		" from game ".
		" left join user white on game.white_player_id = white.id".
		" left join user black on game.black_player_id = black.id".
		" where game.id = ".$_GET["game_id"].";")[0];
	$initiative_book = ["W"=>$game["white_player"],"B"=>$game["black_player"]];
	
	if (!isset($_SESSION["user_id"]) | $_SESSION["user_id"] == 0) {
		echo json_encode([
			"html"=>"<p>no user.</p>"
		]);
	} else if ((is_null($game["black_player_id"]) | $game["black_player_id"] == 0) & $game["white_player_id"] != $_SESSION["user_id"]) {
		execute_insertion("update game set black_player_id = '".$_SESSION["user_id"]."' where id = '".$_GET["game_id"]."';");
		$game["black_player_id"] = $_SESSION["user_id"];
	}
	close_connection();
	
	if ($game["white_player_id"] == $_SESSION["user_id"] | $game["black_player_id"] == $_SESSION["user_id"]) {
		echo json_encode([
			"html" => $html
			,"w_check" => $w_check
			,"b_check" => $b_check
			,"initiative" => $initiative." (".$initiative_book[$initiative].")"
			,"concluded" => $concluded
			,"promote_pawn" => $promote_pawn
			,"en_passant" => $en_passant
			,"end_board"=>$end_board
			,"end_view_html"=>$end_view_html
		]);
	} else if (count($game) == 0) {
		echo json_encode([
			"html"=>"<p>no game.</p>"
		]);
	} else {
		echo json_encode([
			"html"=>"<p>you are not a player of this game.</p>"
		]);
	}
	
	

?>