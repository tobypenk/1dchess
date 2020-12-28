<!DOCTYPE html>
<html>
	<head>
		<?php include "templates/head.html"; ?>
		
		<link type="text/css" rel="stylesheet" href="stylesheets/main.css" />
		<link type="text/css" rel="stylesheet" href="stylesheets/reset.css" />
	</head>
	<body>
		<?php include "templates/header.html"; ?>
		<div class='hud'>
			<div class='account-info'></div>
		</div>
	</body>
</html>

<script>
	
	// list of invites? (and a send-invite function) - can only have one account per email
	// log game outcomes
	// en passant isn't working??
	// create new game doesn't work from game page
	
	// refactor the JS and PHP to be less redundant and shitty
	
	document.onkeypress = function(e) {
		if (e.keyCode == 13) {
			var target = $(":focus").attr("data-return-keypress");
			if (target != undefined) $("."+target).click();
		}
	}
	
	function send_email(recipient_email,recipient_username,subject,message){
		$.ajax({
			url:"php/services/send_email.php",
			method: "GET",
			data:{recipient_email:recipient_email,recipient_username:recipient_username,subject:subject,message:message},
			success: function(e){
				console.log(e);
			}
		});
	}
	
	// lots of this should be in one place sourced from a script
	$.ajax({
		url: "php/services/check_session.php",
		method: "GET",
		success: function(e){
			e = JSON.parse(e);
			console.log(e);
			$(".hud > .account-info").html(e.html);
			if (e.status != "succeeded") {
				$(".error-message").html(e.error);
			}
		}
	});
	
	$(document).on("click",".create-new-game",function(){
		
		var data = {
			"creator_id": $(".user-logged-in-info").attr("data-user-id")
		}
		
		$.ajax({
			url: "php/services/create_game.php",
			method: "GET",
			data: data,
			success: function(e) {
				console.log(e);
				e=JSON.parse(e);
				// open a new window with the game
				window.open("game/?game_id="+e.game_id,"_blank");
			}
		});
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
			url: "php/services/create_user.php",
			method: "GET",
			data: data,
			success: function(e) {
				console.log(e);
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
	
	function update_board(data) {
		$(".promote-pawn").addClass("hidden");
		if (data == undefined) {
			$.ajax({
				url: "php/services/fetch_board.php",
				method: "GET",
				data: {game_id: 1},
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
	
	$(document).on("click",".log-out",function(){
		$.ajax({
			url: "php/services/log_out.php",
			method: "GET",
			success: function(e) {
				console.log(e);
				e = JSON.parse(e);
				$(".hud > .account-info").html(e.html);
			}
		})
	});
	
	$(document).on("click",".log-in",function(){
		var data = {
			username: $(".username-input").val(),
			password: $(".password-input").val()
		}
		$.ajax({
			url: "php/services/log_in.php",
			method: "GET",
			data: data,
			success: function(e) {
				console.log(e);
				e = JSON.parse(e);
				if (e.status == "succeeded") {
					$(".hud > .account-info").html(e.html);
				} else {
					$(".error-message").html(e.error);
				}
			}
		});
	});
	
	function update_dom(data) {
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
	}
	
	$(document).on("click",".pawn-promotion",function() {
		var data = {
			"piece": $(this).attr("data-piece"),
			"rank": $(this).parent().attr("data-rank"),
			"file": $(this).parent().attr("data-file"),
			"player": $(this).parent().attr("data-player"),
			"game_id": 1
		}
			
		$.ajax({
			url: "php/services/promote_pawn.php",
			method: "GET",
			data: data,
			success: function(e) {
				console.log(e);
				// update_board();
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
					url: "php/services/fetch_moves.php",
					method: "GET",
					data: {game_id:1,start_rank:rank,start_file:file},
					success: function(e) {
						
						console.log(e);
						e = JSON.parse(e);
						//console.log(e);
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
		var game_id = 1,
			start_rank = $(".square.active").parent().attr("data-rank-index"),
			start_file = $(".square.active").attr("data-file-index"),
			target_rank = $(this).parent().attr("data-rank-index"),
			target_file = $(this).attr("data-file-index");
			
		move_piece(game_id,start_rank,start_file,target_rank,target_file,-1,-1);
	});
	
	$(document).on("click",".square.capturable-en-passant",function(){
		var game_id = 1,
			start_rank = $(".square.active").parent().attr("data-rank-index"),
			start_file = $(".square.active").attr("data-file-index"),
			target_rank = $(this).parent().attr("data-rank-index"),
			target_file = $(this).attr("data-file-index")
			en_passant_rank = $(this).attr("data-en-passant-rank")
			en_passant_file = $(this).attr("data-en-passant-file");
			
		//console.log(en_passant_rank,en_passant_file);
			
		move_piece(game_id,start_rank,start_file,target_rank,target_file,en_passant_rank,en_passant_file);
	});
	
	function move_piece (game_id,start_rank,start_file,target_rank,target_file,en_passant_rank,en_passant_file) {
		
		$.ajax({
			url: "php/services/move_piece.php",
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
				console.log(e);
				e = JSON.parse(e);
				console.log(e);
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
		var game_id = 1;
		$.ajax({
			url: "php/services/reset_board.php",
			method: "GET",
			data: {game_id: game_id},
			success: function(e) {
				//update_board();
			}
		});
	});

</script>













