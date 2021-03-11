<?php
	
	/*
		
		procedure for constructing the HTML gameboard from data
		can only be called after the board data object is in namespace
		
		inputs (objects that must be in namespace):
			board
			
		outputs (objects that will be in namespace after running this script):
			html: the HTML object representing the HTML state of the board data structure
			end_view_html: the HTML object representing the HTML state of the end-board data structure
		
	*/

	$html = get_top_view($board);
	$end_view_html = end_view_html($board);
	
	function top_view_html($board) {
		
		/*
			
			creates HTML representation of a game board
			
			parameters:
				board: board object representing the game state
				
			returns:
				string representing board HMTL
			
		*/
		
		$html = "<div class='board'>";
	
		for ($i=0; $i<count($board); $i++) {
			$html .= "<div class='rank' data-rank-index='".$i."'>";
			for ($j=0; $j<count($board[$i]); $j++) {
				$html .= "<div ".
						" class='square ".$board[$i][$j]["square_color"]."' ".
						" data-file-index='".$j."'".
						" data-piece='".(
							is_null($board[$i][$j]["piece"]["piece"]) ? 
							"none" : 
							$board[$i][$j]["piece"]["player"]." ".$board[$i][$j]["piece"]["piece"]
						).
						"'".
					">".
					
				"</div>";
			}
			$html .= "</div>";
		}
		$html .= "</div>";
		
		return $html;
	}
	
	function end_view_html($board) {
		
		/*
			
			creates HTML representation of the end view of a game board
			
			parameters:
				board: board object representing the game state
				
			returns:
				string representing board end view HMTL
			
		*/
		
		$end_board = array_map(function($x){return get_end_view($x);}, $board);
	
		$end_view_html = "";
		
		for ($i=count($end_board)-1; $i>=0; $i--) {
			
			$end_view_html .= "<div class='rank' data-rank-index='".$i."'>";
			
			for ($j=0; $j<count($end_board[$i]); $j++) {
				$end_view_html .= "<div ".
					" class='square ".$end_board[$i][$j]["square_color"]." ".
						($end_board[$i][$j]["first-visible"] ? "display" : "no-display")." ".
						($end_board[$i][$j]["visible"] ? "visible" : "occluded")."'".
					" data-file-index='".$end_board[$i][$j]["file"]."'".
					" data-piece='".(
						is_null($end_board[$i][$j]["piece"]["piece"]) ? 
						"none" : 
						$end_board[$i][$j]["piece"]["player"]." ".$end_board[$i][$j]["piece"]["piece"]
					).
					"'".
				">".
				"</div>";
			}
			$end_view_html .="</div>";
		}
		
		return $end_view_html;
	}
	
	function get_end_view($rank) {
		
		/*
			
			gets end view (queenside) of a single rank
			
			parameters:
				rank: the slice of the board data object representing that rank
				
			returns:
				rank data object with added parameters for 'visible' and 'first-visible'
				
			note - first-visible means a square contains the first visible piece on a rank
			
		*/
		
		$visible = true;
		
		for ($j=count($rank)-1; $j>=0; $j--) {
			$rank[$j]["visible"] = $visible;
			$rank[$j]["first-visible"] = false;
			$rank[$j]["file"] = $j;
			
			if (!is_null($rank[$j]["piece"]["piece"]) & $visible) {
				$rank[$j]["first-visible"] = true;
				$visible = false;
			}
		}
		if ($visible) $rank[7]["first-visible"] = true;
		
		return $rank;
	}
?>