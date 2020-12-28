<?php
	
	$files = [
		"a"=>0,
		"b"=>1,
		"c"=>2,
		"d"=>3,
		"e"=>4,
		"f"=>5,
		"g"=>6,
		"h"=>7,
	];
	
	$move_scan_map = [
		"P"=>"scan_pawn",
		"R"=>"scan_rook",
		"N"=>"scan_knight",
		"B"=>"scan_bishop",
		"K"=>"scan_king",
		"Q"=>"scan_queen"
	];
	
	function move($board,$start_square_i,$start_square_j,$target_square_i,$target_square_j) {
		
		$mover = $board[$start_square_i][$start_square_j];
		$target = $board[$target_square_i][$target_square_j];
		$promote_pawn = [];
		
		if (is_null($target["piece"])) {
			$capture = null;
		} else {
			$capture = $target["piece"];
		}
		
		$board[$target_square_i][$target_square_j]["piece"] = $mover["piece"];
		$board[$start_square_i][$start_square_j]["piece"]["player"] = null;
		$board[$start_square_i][$start_square_j]["piece"]["piece"] = null;
		
		return [
			"capture"=>$capture,
			"board"=>$board
		];
	}
	
	function move_is_legal($board,$proposed_move) {
		
	}
	
	function scan_for_moves($board,$start_square_i,$start_square_j) {
		
		global $move_scan_map;
		$piece = $board[$start_square_i][$start_square_j];
		$moves = $move_scan_map[$piece["piece"]["piece"]]($board,$start_square_i,$start_square_j);
		$legal_moves = [];
		foreach ($moves as $move) {
			if (!in_array($move["status"], ["free","capture","capture en passant"])) continue;
			$sim_board = move($board,$move["start_i"],$move["start_j"],$move["target_i"],$move["target_j"])["board"];
			$checks = scan_for_check($sim_board,$piece["piece"]["player"]);
			if (count($checks) == 0) {
				array_push($legal_moves,$move);
			}
		}
		return $legal_moves;
	}
	
	function scan_pawn($board,$start_square_i,$start_square_j) {
		
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		global $en_passant;
		//echo json_encode($en_passant).count($en_passant);
		
		if (count($en_passant)) {
			if ($en_passant[2] != $piece["player"] & 
				abs($start_square_j - $en_passant[1]) == 1 &
				$start_square_i == $en_passant[3]
			) {
				array_push($moves,[
					"status"=>"capture en passant",
					"capture"=>[$en_passant[3],$en_passant[4]],
					"target_i"=>$en_passant[0],"target_j"=>$en_passant[1],
					"start_i"=>$start_square_i,"start_j"=>$start_square_j
				]);
			}
		}
		
		
		// account for blockage in skip move
		if ($piece["player"] == "W") {
			
			array_push($moves,scan_square_no_capture($board,$start_square_i+1,$start_square_j,$piece["player"],$start_square_i,$start_square_j));
			array_push($moves,scan_square_capture_only($board,$start_square_i+1,$start_square_j+1,$piece["player"],$start_square_i,$start_square_j));
			array_push($moves,scan_square_capture_only($board,$start_square_i+1,$start_square_j-1,$piece["player"],$start_square_i,$start_square_j));
			
			if ($start_square_i == 1 & is_null($board[$start_square_i+1][$start_square_j]["piece"]["piece"])) {
				array_push($moves,scan_square_no_capture($board,$start_square_i+2,$start_square_j,$piece["player"],$start_square_i,$start_square_j));
			}
		} else {
			array_push($moves,scan_square_no_capture($board,$start_square_i-1,$start_square_j,$piece["player"],$start_square_i,$start_square_j));
			array_push($moves,scan_square_capture_only($board,$start_square_i-1,$start_square_j+1,$piece["player"],$start_square_i,$start_square_j));
			array_push($moves,scan_square_capture_only($board,$start_square_i-1,$start_square_j-1,$piece["player"],$start_square_i,$start_square_j));
			
			if ($start_square_i == 6) {
				array_push($moves,scan_square_no_capture($board,$start_square_i-2,$start_square_j,$piece["player"],$start_square_i,$start_square_j));
			}
		}
		
		
		return $moves;
	}
	
	function scan_knight($board,$start_square_i,$start_square_j) {
		
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		
		$ia = $ja = [-2,-1,1,2];
		
		foreach ($ia as $i) {
			foreach ($ja as $j) {
				if (abs($i) == abs($j)) continue;
				array_push($moves,scan_square($board,$start_square_i+$i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j));
			}
		}
		
		return $moves;
	}
	
	function scan_king($board,$start_square_i,$start_square_j) {
		
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		
		$ia = $ja = [-1,0,1];
		
		foreach ($ia as $i) {
			foreach ($ja as $j) {
				if ($i==0 & $j==0) continue;
				array_push($moves,scan_square($board,$start_square_i+$i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j));
			}
		}
		
		return $moves;
	}
	
	function scan_bishop($board,$start_square_i,$start_square_j) {
		
		$moves = [];
		array_push($moves,scan_diagonals($board,$start_square_i,$start_square_j));
		return array_reduce($moves, 'array_merge', array());
	}
	
	function scan_rook($board,$start_square_i,$start_square_j) {
		
		$moves = [];

		array_push($moves,scan_rank($board,$start_square_i,$start_square_j));
		array_push($moves,scan_file($board,$start_square_i,$start_square_j));
		
		return array_reduce($moves, 'array_merge', array());
	}
	
	function scan_queen($board,$start_square_i,$start_square_j) {
		
		$moves = [];
		
		array_push($moves,scan_rank($board,$start_square_i,$start_square_j));
		array_push($moves,scan_file($board,$start_square_i,$start_square_j));
		array_push($moves,scan_diagonals($board,$start_square_i,$start_square_j));
		
		return array_reduce($moves, 'array_merge', array());
	}
	
	function scan_diagonals($board,$start_square_i,$start_square_j) {
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		$i=1;
		$j=1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i+$i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=1;
			$j +=1;
		}
		
		$i=1;
		$j=-1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i+$i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=1;
			$j -=1;
		}
		
		$i=-1;
		$j=1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i+$i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=-1;
			$j +=1;
		}
		
		$i=-1;
		$j=-1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i+$i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=-1;
			$j +=-1;
		}
		
		return $moves;
	}
	
	function scan_rank($board,$start_square_i,$start_square_j) {
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		$j=1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$j +=1;
		}
		
		$j=-1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i,$start_square_j+$j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$j -=1;
		}
		
		return $moves;

	}
	
	function scan_file($board,$start_square_i,$start_square_j) {
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		$i=1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i+$i,$start_square_j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=1;
		}
		
		$i=-1;
		
		while (TRUE) {
			$move = scan_square($board,$start_square_i+$i,$start_square_j,$piece["player"],$start_square_i,$start_square_j);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i -=1;
		}
		
		return $moves;
	}
	
	function scan_square($board,$target_i,$target_j,$player,$start_i,$start_j) {
		
		if ($target_i < 0 | $target_i > 7 | $target_j < 0 | $target_j > 7) {
			$status = "off-board";
			$capture = null;
		} else {
			
			$target = $board[$target_i][$target_j]["piece"];
			
			if (is_null($target["piece"])) {
				$status = "free";
				$capture = null;
			} else {
				
				if ($target["player"] == $player) {
					$status = "blocked";
					$capture = null;
				} else {
					$status = "capture";
					$capture = $target["piece"];
				}
			}
		}
		
		
		
		return [
			"status"=>$status,
			"capture"=>$capture,
			"target_i"=>$target_i,"target_j"=>$target_j,
			"start_i"=>$start_i,"start_j"=>$start_j
		];
	}
	
	function clone_board($board) {
		
		$new_board = [];

		foreach ($board as $rank) {
			$new_rank = [];
		    foreach ($rank as $square) {
			    $new_square = [
					"square_color" => $square["square_color"],
					"piece" => [
						"player" => $square["piece"]["player"], 
						"piece" => $square["piece"]["piece"]
					]
				];
			    array_push($new_rank,$new_square);
		    }
		    array_push($new_board,$new_rank);
		}
		return $new_board;
	}
	
	function scan_square_no_capture($board,$target_i,$target_j,$player,$start_i,$start_j) {
		
		if ($target_i < 0 | $target_i > 7 | $target_j < 0 | $target_j > 7) {
			$status = "off-board";
			$capture = null;
		} else {
			$target = $board[$target_i][$target_j]["piece"];
			if (is_null($target["piece"])) {
				$status = "free";
				$capture = null;
			} else {
				
				$status = "blocked";
				$capture = null;
			}
		}	
		
		return [
			"status"=>$status,
			"capture"=>$capture,
			"target_i"=>$target_i,"target_j"=>$target_j,
			"start_i"=>$start_i,"start_j"=>$start_j
		];
	}
	
	function scan_square_capture_only($board,$target_i,$target_j,$player,$start_i,$start_j) {
		
		if ($target_i < 0 | $target_i > 7 | $target_j < 0 | $target_j > 7) {
			$status = "off-board";
			$capture = null;
		} else {
			$target = $board[$target_i][$target_j]["piece"];
			if (is_null($target["piece"]) | $target["player"] == $player) {
				$status = "illegal";
				$capture = null;
			} else {
				$status = "capture";
				$capture = $target["piece"];
			}
		}
		
		return [
			"status"=>$status,
			"capture"=>$capture,
			"target_i"=>$target_i,"target_j"=>$target_j,
			"start_i"=>$start_i,"start_j"=>$start_j
		];
	}
	
	function scan_for_threats($board,$start_square_i,$start_square_j) {
		
		global $move_scan_map;
		$threats = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		$player = $piece["player"];
		$opponent = $player == "W" ? "B" : "W";
		
		for ($i=0; $i<8; $i++) {
			for ($j=0; $j<8; $j++) {
				if ($board[$i][$j]["piece"]["player"] == $opponent) {
					$moves = $move_scan_map[$board[$i][$j]["piece"]["piece"]]($board,$i,$j);
					
					$moves = array_values(array_filter($moves,function($x) use ($start_square_i,$start_square_j) {
						return $x["status"] == "capture" & 
							$x["target_i"] == $start_square_i & 
							$x["target_j"] == $start_square_j;
					}));

					array_push($threats, $moves);
				}
			}
		}

		
		return array_reduce($threats, 'array_merge', array());
	}
	
	function scan_for_check($board,$player) {
		
		$king = locate_piece($board,"K",$player);
		
		$checks = scan_for_threats($board,$king["rank"],$king["file"]);
		return $checks;
	}
	
	function scan_for_conclusion($board,$initiative) {
		
		$check = scan_for_check($board,$initiative);
		
		$all_legal_moves = scan_for_options($board,$initiative);
		if (count($all_legal_moves) > 0) {
			if (count($check) > 0) return $initiative." checked";
			return "active";
		} else {
			if (count($check) > 0) return $initiative." mated";
		}
		return "stalemate";
	}
	
	function scan_for_options($board,$player) {
		
		$pieces_that_can_move = [];
		for ($i=0; $i<8; $i++) {
			for ($j=0; $j<8; $j++) {
				if (is_null($board[$i][$j]["piece"]) | $board[$i][$j]["piece"]["player"] != $player) continue;
				$moves = scan_for_moves($board,$i,$j);
				if (count($moves) > 0) array_push($pieces_that_can_move,$board[$i][$j]["piece"]["piece"]);
			}
		}
		return $pieces_that_can_move;
	}
	
	function scan_for_draw($board) {}
	
	function locate_piece($board,$piece,$player,$bishop_color=null) {

		for ($i=0; $i<8; $i++) {
			for ($j=0; $j<8; $j++) {
				$p = $board[$i][$j];
				if (!is_null($bishop_color) & $p["square_color"] != $bishop_color) continue;
				if ($p["piece"]["piece"] == $piece & $p["piece"]["player"] == $player) {
					return ["rank"=>$i,"file"=>$j];
				}
			}
		}
		
		return ["rank"=>null,"file"=>null];
	}
	
	
	
	
	
	
	function decompose_algebraic_move($algebraic_move) {}
	
	function compose_algebraic_move($player,$piece,$start_rank,$start_file,$target_rank,$target_file) {}
?>









