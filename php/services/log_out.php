<?php
	
	session_start();
	foreach ($_SESSION as $k=>$v) {
		unset($_SESSION[$k]);
	}
	session_destroy();
	include "check_session.php";
?>