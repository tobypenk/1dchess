<?php

	function open_connection() {
		
		/*
			creates a database connection and places it in the global namespace
			must be called before any database functions (queries, inserts, etc) are called
			
			parameters: none
			returns: none (places $conn in the global namespace)
		*/

		global $conn;

		include "credentials.php";

        $conn = new mysqli($host,$user,$pass,$dbname,$port);

        if (!$conn) {
	        die('Could not connect: ' . mysqli_error($conn));
	    }
	}

	function close_connection() {
		
		/*
			destroys the connection created by open_connection
			must be called to close any database script or the lingering connections will exceed the limit
			
			parameters: none
			returns: none (removes $conn from the global namespace)
		*/

		global $conn;

		mysqli_close($conn);

	}

	function execute_query($query_string) {
		
		/*
			executes a query and returns an array with the results
			
			parameters:
				query_string: a string with an executable query
				
			returns:
				result (array)
				
			note - open_connection() must be called; otherwise, this function will not find the right connection object
				in the global namespace and will create a nonworking one
		*/
		
		global $conn;
		
	    $q = mysqli_query($conn,$query_string);
		$result = array();

		while ($row = $q->fetch_assoc()) {
			$result[] = $row;
		}

        return $result;
	}

	function execute_insertion($query_string) {

		/*
			executes an insertion into the database
			
			parameters:
				query_string: a string with an executable insertion query
				
			returns:
				none
				
			note - open_connection() must be called; otherwise, this function will not find the right connection object
				in the global namespace and will create a nonworking one
		*/
		
        global $conn;

	    $q = mysqli_query($conn,$query_string);
	}
	
	function execute_removal($query_string) {
		
		/*
			executes a deletion query
			
			parameters:
				query_string: a string with an executable deletion query
				
			returns:
				result (array)
				
			note - open_connection() must be called; otherwise, this function will not find the right connection object
				in the global namespace and will create a nonworking one
				
			note - this is identical to execute_insertion(); a future version may refactor this to a single 
				execute_nonreturning_query function
		*/

        global $conn;

	    $q = mysqli_query($conn,$query_string);
	}

	function execute_insertion_and_return_insert_id($query_string,$table) {
		
		/*
			executes an insertion and returns the id of the inserted, blank row
			
			parameters:
				query_string: a string with an executable query
				table: the table into which the item is being inserted
				
			returns:
				last_insert_id (int as string) representing the last insert id
				
			note - open_connection() must be called; otherwise, this function will not find the right connection object
				in the global namespace and will create a nonworking one
		*/

		global $conn;

	    $q = mysqli_query($conn,$query_string);
	    $r = mysqli_query($conn,"SELECT max(id) last_insert_id FROM ".$table.";");

		$r_arr = array();

		while ($row = $r->fetch_assoc()) {
			$r_arr[] = $row;
		}

	    return $r_arr[0]["last_insert_id"];
	}

?>