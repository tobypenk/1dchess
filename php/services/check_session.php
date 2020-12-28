<?php
	
	include_once "start_session.php";
	include_once "../libraries/db_functions.php";
	
	if (isset($_SESSION["user_id"])) {
		// user is already logged in
		$q = "select email,id,username from user where id = ".$_SESSION["user_id"].";";
		$gq = "select game.*".
			", case when white.username is null then 'W not assigned' else white.username end white_username".
			", case when black.username is null then 'B not assigned' else black.username end black_username".
			" from game ".
			" left join user white on game.white_player_id = white.id".
			" left join user black on game.black_player_id = black.id".
			" where white_player_id = '".$_SESSION["user_id"]."' or black_player_id = '".$_SESSION["user_id"]."';";
			
		open_connection();
		$user = execute_query($q);
		$user_games = execute_query($gq);
		close_connection();
		
		$finished_games_html = "";
		$active_games_html = "<table><tr><th>game id</th><th>opponent</th><th>outcome</th></tr>";
		
		foreach ($user_games as $g) {
			$html = "<tr>".
				"<td>".$g["id"]."</td>".
				"<td><a href='http://www.1dchess.com/game?game_id=".$g["id"]."'>".
					($g["white_player_id"] == $_SESSION["user_id"] ? $g["black_username"] : $g["white_username"]).
				"</a></td>".
				"<td>".(is_null($g["outcome"]) ? ("in progress: initiative to ".$g["initiative"]) : $g["outcome"])."</td>".
			"</tr>";
			
			if (is_null($g["outcome"])) {
				$active_games_html .= $html; 
			} else {
				$finished_games_html .= $html;
			}
		}
		
		$finished_games_html .= "</table>";
		$active_games_html .= "</table>";
		
		$_SESSION["user"] = [];		
		foreach ($user[0] as $k=>$v) {
			$_SESSION["user"][$k]=urldecode($v);
		}
		
		if (!isset($prevent_echo)) {
			echo json_encode([
				"status"=>"succeeded",
				"error"=>null,
				"user"=>null,
				"session"=>$_SESSION,
				"html"=>"<p class='user-logged-in-info' data-user-id='".$_SESSION["user_id"]."'>".
					"logged in as ".$_SESSION["user"]["username"].
						". <span class='log-out'>log out</span>".
					"</p>".
					"<div class='create-new-game'>create new game</div>".$active_games_html.$finished_games_html
			]);
		}
		
		// should also display a list of that user's active games
	} else {
		// user is not logged in or has no account
		
		if (!isset($prevent_echo)) {
			echo json_encode([
				"status"=>"no-user",
				"error"=>null,
				"user"=>null,
				"html"=>"<div class='account-stuff'>".
					"<div class='error-message'></div>".
					"<div class='existing-account'>".
						"<p class='create-account-toggle'>i don't have an account</p>".
						"<input class='username-input' data-return-keypress='log-in' type='username' placeholder='your username'/>".
						"<input class='password-input' data-return-keypress='log-in' type='password' placeholder='your password'/>".
						"<div class='log-in'>log in</div>".
					"</div>".
					"<div class='create-account inactive'>".
						"<p class='create-account-toggle'>i already have an account</p>".
						"<input class='new-username-input' data-return-keypress='confirm-account-creation' type='username' placeholder='choose a username'/>".
						"<input class='new-email-input' data-return-keypress='confirm-account-creation' type='email' placeholder='your email'/>".
						"<input class='new-password-input' data-return-keypress='confirm-account-creation' type='password' placeholder='choose password'/>".
						"<input class='confirm-new-password-input' data-return-keypress='confirm-account-creation' type='password' placeholder='confirm password'/>".
						"<div class='confirm-account-creation'>create my account</div>".
					"</div>".
				"</div>"
			]);
		}
	}

?>