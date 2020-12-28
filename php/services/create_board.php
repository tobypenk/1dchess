<?php
	
	$outer_rank = ["R","N","B","K","Q","B","N","R"];
	$pawn_rank = array_fill(0, 8, "P");
	$blank_rank = array_fill(0,8,null);
	
	$pieces = [
		$outer_rank,
		$pawn_rank,
		$blank_rank,
		$blank_rank,
		$blank_rank,
		$blank_rank,
		$pawn_rank,
		$outer_rank
	];
	
	$board = [];
	for ($i=0; $i<8; $i++) {
		$row = [];
		for ($j=0; $j<8; $j++) {
			array_push(
				$row,
				[
					"square_color" => ($i*8+$j+($i%2==0?0:1)) % 2 == 0 ? "W" : "B",
					"piece" => ["player" => $i<2?"W":($i<6?null:"B"), "piece" => $pieces[$i][$j]]
				]
			);
		}
		array_push($board,$row);
	}
	
	
	
?>