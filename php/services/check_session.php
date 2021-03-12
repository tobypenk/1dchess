<?php
	
	include_once "start_session.php";
	include_once "../libraries/db_functions.php";
	include_once "../templates/account.php";
	
	
	
	if (isset($_SESSION["user_id"])) {
		
		// user is already logged in
		
		open_connection();
		$user = get_session_user();
		$user_games = get_session_user_games();
		close_connection();
		
		$games_html = user_games_html($user_games);
		$finished_games_html = $games_html["finished_games_html"];
		$active_games_html = $games_html["active_games_html"];
		
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
	} else {
		// user is not logged in or has no account
		
		if (!isset($prevent_echo)) {
			echo json_encode([
				"status"=>"no-user",
				"error"=>null,
				"user"=>null,
				"html"=>login_fields_html()
			]);
		}
	}
	
	
	
?>