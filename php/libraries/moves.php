<?php
	
	// map files to 0-based indices
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
	
	// map letters to piece scanning functions
	$move_scan_map = [
		"P"=>"scan_pawn",
		"R"=>"scan_rook",
		"N"=>"scan_knight",
		"B"=>"scan_bishop",
		"K"=>"scan_king",
		"Q"=>"scan_queen"
	];
	
	function move($board,$start_square_i,$start_square_j,$target_square_i,$target_square_j) {
		
		/*
			update board to reflect a moved piece
			
			parameters:
				board: the board object on which the move will be made
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				target_square_i: the destination rank of the piece to be moved
				target_square_j: the destination file of the piece to be moved
				
			returns: 
				array:
					capture: captured piece, or null if no piece was captured
					board: board object representing the updated board with the moved piece
		*/
		
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
		// not yet implemented
	}
	
	function scan_for_moves($board,$start_square_i,$start_square_j) {
		
		/*
			finds legal moves for a given piece
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing every legal move
		*/
		
		global $move_scan_map;
		$piece = $board[$start_square_i][$start_square_j];
		$legal_moves = [];
		
		$moves = $move_scan_map[$piece["piece"]["piece"]]($board,$start_square_i,$start_square_j);
		foreach ($moves as $move) {
			
			// free, capture, and capture en passant are the only statuses representing legal moves
			if (!in_array($move["status"], ["free","capture","capture en passant"])) continue;
			
			// even if legal, need to ensure the move doesn't place the moving player in check
			$sim_board = move($board,$move["start_i"],$move["start_j"],$move["target_i"],$move["target_j"])["board"];
			$checks = scan_for_check($sim_board,$piece["piece"]["player"]);
			if (count($checks) == 0) {
				// if the move doesn't place the moving player in check, add it to the legal moves array
				array_push($legal_moves,$move);
			}
		}
		
		return $legal_moves;
	}
	
	function scan_pawn($board,$start_square_i,$start_square_j) {
		
		/*
			scan for moves using the pawn move rules
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing legal moves; each object contains:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
				
			note - this function does not currently scan to ensure the moving player isn't placed in check by
				their own move. a future version / refactor would likely perform this task within the scan
				functions and not in the wrapper (scan_for_moves()).
		*/
		
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
		
		/*
			scan for moves using the knight move rules
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing legal moves; each object contains:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
				
			note - this function does not currently scan to ensure the moving player isn't placed in check by
				their own move. a future version / refactor would likely perform this task within the scan
				functions and not in the wrapper (scan_for_moves()).
		*/
		
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
		
		/*
			scan for moves using the king move rules
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing legal moves; each object contains:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
				
			note - this function does not currently scan to ensure the moving player isn't placed in check by
				their own move. a future version / refactor would likely perform this task within the scan
				functions and not in the wrapper (scan_for_moves()).
		*/
		
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
		
		/*
			scan for moves using the bishop move rules
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing legal moves; each object contains:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
				
			note - this function does not currently scan to ensure the moving player isn't placed in check by
				their own move. a future version / refactor would likely perform this task within the scan
				functions and not in the wrapper (scan_for_moves()).
		*/
		
		$moves = [];
		array_push($moves,scan_diagonals($board,$start_square_i,$start_square_j));
		return array_reduce($moves, 'array_merge', array());
	}
	
	function scan_rook($board,$start_square_i,$start_square_j) {
		
		/*
			scan for moves using the rook move rules
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing legal moves; each object contains:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
				
			note - this function does not currently scan to ensure the moving player isn't placed in check by
				their own move. a future version / refactor would likely perform this task within the scan
				functions and not in the wrapper (scan_for_moves()).
		*/
		
		$moves = [];

		array_push($moves,scan_rank($board,$start_square_i,$start_square_j));
		array_push($moves,scan_file($board,$start_square_i,$start_square_j));
		
		return array_reduce($moves, 'array_merge', array());
	}
	
	function scan_queen($board,$start_square_i,$start_square_j) {
		
		/*
			scan for moves using the queen move rules
			
			parameters:
				board: the board object to search for moves
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				
			returns:
				array of move objects representing legal moves; each object contains:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
				
			note - this function does not currently scan to ensure the moving player isn't placed in check by
				their own move. a future version / refactor would likely perform this task within the scan
				functions and not in the wrapper (scan_for_moves()).
		*/
		
		$moves = [];
		
		array_push($moves,scan_rank($board,$start_square_i,$start_square_j));
		array_push($moves,scan_file($board,$start_square_i,$start_square_j));
		array_push($moves,scan_diagonals($board,$start_square_i,$start_square_j));
		
		return array_reduce($moves, 'array_merge', array());
	}
	
	function scan_diagonals($board,$start_square_i,$start_square_j) {
		
		/*
			
			/*
			
			scans a rank to determine what moves would be available to a long-range, diagonal-covering piece (bishop, 
				queen)
			
			parameters:
				board: board object representing current game state
				start_square_i: the rank of the piece to evaluate
				start_square_j: the file of the piece to evaluate
				
			returns:
				array of move objects representing all legal moves along both diagonals in both directions
						
		*/
		
		
		
		$moves = [];
		$piece = $board[$start_square_i][$start_square_j]["piece"];
		$i=1;
		$j=1;
		
		while (TRUE) {
			$move = scan_square(
				$board,
				$start_square_i+$i,$start_square_j+$j,
				$piece["player"],
				$start_square_i,$start_square_j
			);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=1;
			$j +=1;
		}
		
		$i=1;
		$j=-1;
		
		while (TRUE) {
			$move = scan_square(
				$board,
				$start_square_i+$i,$start_square_j+$j,
				$piece["player"],
				$start_square_i,$start_square_j
			);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=1;
			$j -=1;
		}
		
		$i=-1;
		$j=1;
		
		while (TRUE) {
			$move = scan_square(
				$board,
				$start_square_i+$i,$start_square_j+$j,
				$piece["player"],
				$start_square_i,$start_square_j
			);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=-1;
			$j +=1;
		}
		
		$i=-1;
		$j=-1;
		
		while (TRUE) {
			$move = scan_square(
				$board,
				$start_square_i+$i,$start_square_j+$j,
				$piece["player"],
				$start_square_i,$start_square_j
			);
			array_push($moves,$move);
			if ($move["status"] != "free") break;
			$i +=-1;
			$j +=-1;
		}
		
		return $moves;
	}
	
	function scan_rank($board,$start_square_i,$start_square_j) {
		
		/*
			
			scans a rank to determine what moves would be available to a long-range, rank-covering piece (rook, queen)
			
			parameters:
				board: board object representing current game state
				start_square_i: the rank of the piece to evaluate
				start_square_j: the file of the piece to evaluate
				
			returns:
				array of move objects representing all legal moves along that rank in both directions
						
		*/
		
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
		
		/*
			
			scans a rank to determine what moves would be available to a long-range, file-covering piece (rook, queen)
			
			parameters:
				board: board object representing current game state
				start_square_i: the rank of the piece to evaluate
				start_square_j: the file of the piece to evaluate
				
			returns:
				array of move objects representing all legal moves along that file in both directions
						
		*/
		
		
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
		
		/*
			scan a single square to determine whether a piece can move there
			
			parameters:
				board: the board object on which the move will be made
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				player: the color of the moving player
				start_i: the destination rank of the piece to be moved
				start_j: the destination file of the piece to be moved
				
			returns: 
				move object:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
		*/
		
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
		
		/*
			
			determine the legality and effects a potential non-capturing move. this applies to pawns moving 
				forward in the same file, as this is legal only if the destination square is empty.
			
			parameters:
				board: the board object on which the move will be made
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				player: the color of the moving player
				start_i: the destination rank of the piece to be moved
				start_j: the destination file of the piece to be moved
				
			returns: 
				move object:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
		
		*/
		
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
		
		/*
			
			determine the legality and effects a potential capturing-only move. this applies to pawns capturing
				diagonally, as this move results in capture but isn't legal to make without a capture.
			
			parameters:
				board: the board object on which the move will be made
				start_square i: the starting rank of the piece to be moved
				start_square_j: the starting file of the piece to be moved
				player: the color of the moving player
				start_i: the destination rank of the piece to be moved
				start_j: the destination file of the piece to be moved
				
			returns: 
				move object:
					status: [free, blocked, capture, capture en passant, off-board]
					capture: coordinates of captured piece if any, null if none
					target_i: rank to which the piece moves
					target_j: file to which the piece moves
					start_i: rank from which the piece started
					start_j: file from which the piece started
		
		*/
		
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
		
		/*
			
			determine what are the threats to a given piece
			
			parameters:
				board: board object representing current game state
				start_square_i: the rank of the piece to evaluate
				start_square_j: the file of the piece to evaluate
				
			returns:
				array of move objects representing the pieces threatening the target piece
		*/
		
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
		
		/*
			
			determines whether a player is in check
			
			parameters:
				board: board object representing current game state
				player: player whose position to evaluate ("W" or "B")
				
			returns:
				array of move objects representing the pieces checking the king
			
		*/
		
		$king = locate_piece($board,"K",$player);
		
		$checks = scan_for_threats($board,$king["rank"],$king["file"]);
		return $checks;
	}
	
	function scan_for_conclusion($board,$initiative) {
		
		/*
			
			determine whether the game has ended
			
			parameters:
				board: board object representing current game state
				initiative: player whose turn it is
			
			returns:
				string representing game state: one of "active," "stalemate," "x checked," "x mated," where x
					is one of "W" or "B"
			
		*/
		
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
		
		/*
			
			finds all pieces that a player can move
			
			parameters:
				board: board object representing the game's current state
				player: the color of the player whose position to evaluate ("W" or "B")
				
			returns:
				array of pieces that can move
				
			note - this does not return the locations of pieces that can move, or the potential moves themselves.
				it is useful only to check for the existence of available moves, to determine checkmate/stalemate.
			
		*/
		
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
	
	function scan_for_draw($board) {
		// not yet implemented
	}
	
	function locate_piece($board,$piece,$player,$bishop_color=null) {
		
		/*
			
			find the location of a piece on the board; returns null / null if the piece does not exist
			
			parameters:
				board: the board object representing the current game state
				piece: the piece to be located (by letter; for example, "K" for the king)
				player: the color of the player whose piece to find ("W" or "B")
				bishop_color: to be specified only if a bishop is sought
				
			returns:
				rank (int): the rank (i) of the piece 
				filt (int): the file (j) of the piece
				
			note - the current implementation assumes this function will only be used to find a player's king (in=
				spite of the bishop_color parameter). a future version may include the ability to locate an arbitrary
				piece, but at present, if a piece other than a bishop or king is searched, this function returns only 
				the first instance of that piece and is therefore unsuitable for arbitrary lookups.
			
		*/

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
	
	
	
	
	
	
	// algebraic notation not yet supported
	
	function decompose_algebraic_move($algebraic_move) {}
	
	function compose_algebraic_move($player,$piece,$start_rank,$start_file,$target_rank,$target_file) {}
?>









