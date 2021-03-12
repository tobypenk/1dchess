<?php
	
?>



<!DOCTYPE html>
<html>
	<head>
		<?php include "../templates/head.html"; ?>
		
		<link type="text/css" rel="stylesheet" href="../stylesheets/main.css" />
	</head>
	<body>
		<?php include "../templates/header.html"; ?>
		<div class='game'>
			<div class='ui'>
			
			</div>
			<div class='end-view'></div>
			<div class='hud'>
				<div class='account-info'></div>
				<p class='initiative'></p>
				<p class='game-status'></p>
				<div class='promotion-ui'>
					<div class='promote-pawn'>
						<p>promote pawn:</p>
						<div class='pawn-promotion B' data-piece-full='bishop' data-piece='B'></div>
						<div class='pawn-promotion N' data-piece-full='knight' data-piece='N'></div>
						<div class='pawn-promotion R' data-piece-full='rook' data-piece='R'></div>
						<div class='pawn-promotion Q' data-piece-full='queen' data-piece='Q'></div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>

<script>
	
	document.onkeypress = function(e) {
		if (e.keyCode == 13) {
			var target = $(":focus").attr("data-return-keypress");
			if (target != undefined) $("."+target).click();
		}
	}
	
	$.ajax({
		url: "../php/services/check_session.php",
		method: "GET",
		success: function(e){
			e = JSON.parse(e);
			$(".hud > .account-info").html(e.html);
			if (e.status != "succeeded") {
				$(".error-message").html(e.error);
			} else {
				board_update = window.setInterval(function(){update_board();}, 500);
			}
		}
	});
	
	$(document).on("click",".create-account-toggle",function() {
		$(".account-stuff").toggleClass("inactive");
	});
	
	$(document).on("click",".confirm-account-creation",function() {
		var data = {
			"username": $(".new-username-input").val(),
			"email": $(".new-email-input").val(),
			"password": $(".new-password-input").val(),
			"password_check": $(".confirm-new-password-input").val()
		}
			
		$.ajax({
			url: "../php/services/create_user.php",
			method: "GET",
			data: data,
			success: function(e) {
				e = JSON.parse(e);
				if (e.status == "succeeded") {
					$(".hud > .account-info").html(e.html);
				} else {
					$(".error-message").html(e.error);
				}
			}
		})
	});
	
	var pawn_promotions = {
		"bishop":"B",
		"knight":"N",
		"rook":"R",
		"queen":"Q"
	}
	
	

	var board = [];
	var board_update;
	
	function update_board(data) {
		
		$(".promote-pawn").addClass("hidden");
		if (data == undefined) {
			$.ajax({
				url: "../php/services/fetch_board.php",
				method: "GET",
				data: {game_id: get_game_id()},
				success: function(e) {
					console.log(e);
					e = JSON.parse(e);
					console.log(e);
					update_dom(e);
				}
			});
		} else {
			update_dom(data);
		}
	}
	
	function boards_are_equivalent(b1,b2) {
		var r,f,rank,square;
		if (b1.length != b2.length) return false;
		for (r=0; r<b1.length; r++) {
			if (b1[r].length != b2[r].length) return false;
			rank = b1[r];
			for (f=0; f<rank.length; f++) {
				square = rank[f];
				if (b1[r][f].square_color != b2[r][f].square_color) return false;
				if (b1[r][f].piece.player != b2[r][f].piece.player) return false;
				if (b1[r][f].piece.piece != b2[r][f].piece.piece) return false;
			}
		}
		return true;
	}
	
	function update_dom(data) {
		if (boards_are_equivalent(data.end_board,board)) return 0;
		
		$(".ui").html(data.html);
		$(".initiative").html("initiative to "+data.initiative);
		$(".game-status").html("game status "+data.concluded);
		
		if (data.promote_pawn.length > 0) {
			$(".promote-pawn").removeClass("hidden");
			$(".promote-pawn").attr("data-rank",data.promote_pawn[0]);
			$(".promote-pawn").attr("data-file",data.promote_pawn[1]);
			$(".promote-pawn").attr("data-player",data.promote_pawn[2]);
		}
		
		if (data.en_passant.length > 0) {
			$(".rank[data-rank-index="+data.en_passant[0]+"] > .square[data-file-index="+data.en_passant[1]+"]")
				.attr("data-en-passant-rank",data.en_passant[3]);
			$(".rank[data-rank-index="+data.en_passant[0]+"] > .square[data-file-index="+data.en_passant[1]+"]")
				.attr("data-en-passant-file",data.en_passant[4]);
		}
		$(".end-view").html(data.end_view_html);
		board = data.end_board;
	}
	
	function clear_dom() {
		$(".ui").html("");
		$(".initiative").html("");
		$(".game-status").html("");
		
		$(".promote-pawn").addClass("hidden");

		$(".end-view").html("");
	}	
	
	
	
	
	$(document).on("click",".log-out",function(){
		$.ajax({
			url: "../php/services/log_out.php",
			method: "GET",
			success: function(e) {
				e = JSON.parse(e);
				$(".hud > .account-info").html(e.html);
				window.clearInterval(board_update);
				clear_dom();
			}
		})
	});
	
	$(document).on("click",".log-in",function(){
		var data = {
			username: $(".username-input").val(),
			password: $(".password-input").val()
		}
		$.ajax({
			url: "../php/services/log_in.php",
			method: "GET",
			data: data,
			success: function(e) {
				e = JSON.parse(e);
				if (e.status == "succeeded") {
					$(".hud > .account-info").html(e.html);
					board_update = window.setInterval(function(){update_board();}, 500);
				} else {
					$(".error-message").html(e.error);
				}
			}
		});
	});
	
	
	
	
	
	$(document).on("click",".pawn-promotion",function() {
		var data = {
			"piece": $(this).attr("data-piece"),
			"rank": $(this).parent().attr("data-rank"),
			"file": $(this).parent().attr("data-file"),
			"player": $(this).parent().attr("data-player"),
			"game_id": get_game_id()
		}
			
		$.ajax({
			url: "../php/services/promote_pawn.php",
			method: "GET",
			data: data,
			success: function(e) {
				//update_board();
			}
		});
	});
	
	$(document).on("click",".square",function(){
		var prevent_active = false;
		if (
			$(this).hasClass("active") || 
			$(this).hasClass("available") || 
			$(this).hasClass("capturable") || 
			$(this).hasClass("capturable-en-passant")
		) {
			$(this).removeClass("active");
			prevent_active = true;
		} else {
			$(".square").removeClass("active");
		}
		
		$(".square").removeClass("available");
		$(".square").removeClass("capturable");
		$(".square").removeClass("capturable-en-passant");
		
		if ($(this).is(":not([data-piece='none']")) {
			
			if (!prevent_active) {

				$(this).addClass("active");
				var rank = $(this).parent().attr("data-rank-index"),
					file = $(this).attr("data-file-index"),
					piece = $(this).attr("data-piece");
				
				$.ajax({
					url: "../php/services/fetch_moves.php",
					method: "GET",
					data: {
						game_id:get_game_id(),
						start_rank:rank,
						start_file:file,
						player_id:$(".user-logged-in-info").attr("data-user-id")
					},
					success: function(e) {
						
						e = JSON.parse(e);
						var m = e.moves;
						
						show_potential_moves(m.filter(function(x){return x.status=="free";}));
						show_potential_captures(m.filter(function(x){return x.status=="capture";}));
						show_potential_captures_en_passant(m.filter(function(x){return x.status=="capture en passant";}));
					}
				});
			}
		}
	});
	
	function show_potential_moves(m) {
		var i;
		for (i=0; i<m.length; i++) {
			$(".rank[data-rank-index="+m[i]["target_i"]+"] > .square[data-file-index="+m[i]["target_j"]+"]").addClass("available");
		}
	}
	
	function show_potential_captures(c) {
		var i;
		for (i=0; i<c.length; i++) {
			$(".rank[data-rank-index="+c[i]["target_i"]+"] > .square[data-file-index="+c[i]["target_j"]+"]").addClass("capturable");
		}
	}
	
	function show_potential_captures_en_passant(c) {
		var i;
		for (i=0; i<c.length; i++) {
			$(".rank[data-rank-index="+c[i]["target_i"]+"] > .square[data-file-index="+c[i]["target_j"]+"]").addClass("capturable-en-passant");
		}
	}
	
	$(document).on("click",".square.available, .square.capturable",function(){
		var game_id = get_game_id(),
			start_rank = $(".square.active").parent().attr("data-rank-index"),
			start_file = $(".square.active").attr("data-file-index"),
			target_rank = $(this).parent().attr("data-rank-index"),
			target_file = $(this).attr("data-file-index");
			
		move_piece(game_id,start_rank,start_file,target_rank,target_file,-1,-1);
	});
	
	$(document).on("click",".square.capturable-en-passant",function(){
		var game_id = get_game_id(),
			start_rank = $(".square.active").parent().attr("data-rank-index"),
			start_file = $(".square.active").attr("data-file-index"),
			target_rank = $(this).parent().attr("data-rank-index"),
			target_file = $(this).attr("data-file-index")
			en_passant_rank = $(this).attr("data-en-passant-rank")
			en_passant_file = $(this).attr("data-en-passant-file");
			
			
		move_piece(game_id,start_rank,start_file,target_rank,target_file,en_passant_rank,en_passant_file);
	});
	
	function move_piece (game_id,start_rank,start_file,target_rank,target_file,en_passant_rank,en_passant_file) {
		
		$.ajax({
			url: "../php/services/move_piece.php",
			method: "GET",
			data: {
				game_id:game_id,
				start_rank:start_rank,
				start_file:start_file,
				target_rank:target_rank,
				target_file:target_file,
				en_passant_rank:en_passant_rank,
				en_passant_file:en_passant_file
			},
			success: function(e) {
				e = JSON.parse(e);
				//update_board(e);
				display_check(e.w_check);
				display_check(e.b_check);
			}
		});
	}
	
	function display_check(c) {
		var threat_i,threat_j,king_i,king_j;
		for (var i=0; i<c.length; i++) {
			threat_i = c[i].start_i;
			threat_j = c[i].start_j;
			target_i = c[i].target_i;
			target_j = c[i].target_j;
			
			$(".rank[data-rank-index="+threat_i+"] .square[data-file-index="+threat_j+"]").addClass("regicide");
			$(".rank[data-rank-index="+target_i+"] .square[data-file-index="+target_j+"]").addClass("checked");
		}
	}
	
	function display_mate(m) {
		
	}
	
	$(document).on("click",".reset",function() {
		var game_id = get_game_id();
		$.ajax({
			url: "../php/services/reset_board.php",
			method: "GET",
			data: {game_id: game_id},
			success: function(e) {
				//update_board();
			}
		});
	});
	
	update_board();
	
	
	
	
	
	
	
	
	
	
	function parse_get_parameters() {

		var params = params = window.location.search
				.slice(1)
				.split("&")
				.map(function(x){return x.split("=");})
				.filter(function(x){return x[1] != 0;});

		return dedupe_parameters(params).filter(function(x){return x.length == 2;});
	}

	function dedupe_parameters(params) {

		var p = [], n = [], i

		for (i=params.length-1; i>=0; i--) {
			if (contains(n,params[i][0])) {
				continue;
			} else {
				p.push(params[i]);
				n.push(params[i][0]);
			}
		}
		return p;
	}
	
	function get_game_id() {
		return parse_get_parameters()
			.filter(function(x){
				return x[0]=="game_id";
			})[0][1];
	}

	function contains(arr,el) {

		for (var i=0; i<arr.length; i++) {
			if (arr[i] == el) return true;
		}
		return false;
	}

/*
	function relocate_window(params) {
		param_string = stringify_parameter_array(params);

		var new_location = window.location.protocol + 
			"//" + 
			window.location.host + 
			window.location.pathname + "?" +
			param_string;
		if (history.pushState) {
		    window.history.pushState({path:new_location},'',new_location);
		}
	}

	function stringify_parameter_array(params) {
		return params.map(function(x){return x.join("=")})
			.join("&");
	}

    function reconcile_dom_to_window_location() {
	    var ss = parse_get_parameters()
	    		.filter(function(x) {return x[0] == "search_string"; }),
	    	mo = parse_get_parameters()
	    		.filter(function(x) {return x[0] == "macro_order"; }),
	    	mb = parse_get_parameters()
	    		.filter(function(x) {return x[0] == "sort_basis"; });
	    	
	    if (ss.length == 1) {
		    $(".search-string-ui").val(ss[0][1]);
	    }
	    
	    if (mo.length == 1 && mb.length == 1) {
		    $(".search-result-header .macros-slider."+mo[0][1]).addClass(mb[0][1]);
	    }
    }
*/

</script>













