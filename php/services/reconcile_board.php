<?php
	
	
	$game_id = $_GET["game_id"];
	
	open_connection();
	$state = execute_query("select * from move_procedural where game_id = '".$game_id."' order by id;");
	close_connection();
	
	$captures = [];
	$initiative = "W";
	$promote_pawn = [];
	$en_passant = [];
	
	foreach ($state as $s) {
		
		$en_passant = [];
		$player = $board[$s["start_square_i"]][$s["start_square_j"]]["piece"]["player"];
		$piece = $board[$s["start_square_i"]][$s["start_square_j"]]["piece"]["piece"];
		
		if (is_null($s["pawn_promotion"])) {
			// the move is not a pawn promotion
			$update = move($board,$s["start_square_i"],$s["start_square_j"],$s["target_square_i"],$s["target_square_j"]);
			if (!is_null($update["capture"])) array_push($captures,$update["capture"]);
			$board = $update["board"];
			
			//initiative to the other player
			$initiative = $player == "W" ? "B" : "W";
			
			// check if a pawn needs to be promoted and suspend passing of initiative if so
			if ($player == "W" & $piece == "P" & $s["target_square_i"] == 7) {
				$promote_pawn = [$s["target_square_i"],$s["target_square_j"],"W"];
				$initiative = "W";
			} else if ($player == "B" & $piece == "P" & $s["target_square_i"] == 0) {
				$promote_pawn = [$s["target_square_i"],$s["target_square_j"],"B"];
				$initiative = "B";
			}
		} else {
			// the move is a pawn promotion
			$board[$s["start_square_i"]][$s["start_square_j"]]["piece"]["piece"] = $s["pawn_promotion"];
			$promote_pawn = [];
			
			// pass initiative after reconciling the promotion
			$initiative = $player == "W" ? "B" : "W";
		}
		
		if ($piece == "P" & abs($s["start_square_i"] - $s["target_square_i"]) == 2) {
			$en_passant = [
				($s["start_square_i"] + $s["target_square_i"])/2,$s["target_square_j"],$player,
				$s["target_square_i"],$s["target_square_j"]
			];
		}
		
		if (!is_null($s["en_passant_rank"]) & !is_null($s["en_passant_file"])) {
			$board[$s["en_passant_rank"]][$s["en_passant_file"]]["piece"]["player"] = null;
			$board[$s["en_passant_rank"]][$s["en_passant_file"]]["piece"]["piece"] = null;
		}
	}
?>













