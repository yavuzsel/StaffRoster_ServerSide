<?php
	/*
	*	constants in constants.php -- fill/remove according to your ldap instance and schema
	*
	*	$ldap_url = "";
	*	$ldap_dn = "";
	*	$ldap_query_head = "";
	*	$ldap_query_tail = "";
	*	$ldap_mngr_query_head = "";
	*	$ldap_mngr_filter_query_head = "";
	*
	*/
	require_once("constants.php");
	
	$query = (isset($_REQUEST["manager"]) && strlen($_REQUEST["manager"]))?$_REQUEST["manager"]:((isset($_REQUEST["where"]) && strlen($_REQUEST["where"]))?((isset(json_decode($_REQUEST["where"])->manager) && strlen(json_decode($_REQUEST["where"])->manager))?json_decode($_REQUEST["where"])->manager:""):"");

	if(strlen($query) == 0){
		echo "error! no proper query parameter received...";
		die;
	}
		
	$ds=ldap_connect($ldap_url);
	if ($ds) { 
		$r=ldap_bind($ds);     // this is an "anonymous" bind, typically
							   // read-only access

		/*echo $ldap_query_head.$query.$ldap_query_tail;
		die;*/
		$sr=ldap_search($ds, $ldap_dn, $ldap_query_head.$query.$ldap_query_tail);  

		$info = ldap_get_entries($ds, $sr);


		/*echo "<pre>"; var_dump($info); echo "</pre><br /><br />";
		die;*/
		//$result_array = array();
		$count = $info["count"];
		// should be only one, discuss if sending uid to client is safe???
		for($i=0; $i<$count; $i++) {
			//echo "<pre>"; var_dump($infoitem); echo "</pre><br /><br />";
			$manager_query = $info[$i]["manager"][0];
		}
		$manager_query_fields = explode(",", $manager_query);
		$manager_filter = $manager_query_fields[0];
		$manager_dn = substr($manager_query, strpos($manager_query, ",")+1);
		// get manager here
		if(strlen($manager_filter) == 0) {
			ldap_close($ds);
			// set the status
			header('HTTP/1.1 404 Not Found');
			// set the content type
			header('Content-type: application/json');
			echo json_encode("Unable to find manager.");
			die;
		}
		
		$sr=ldap_search($ds, $manager_dn, $manager_filter);

		$info = ldap_get_entries($ds, $sr);
		
		$count = $info["count"];
		// should be only one, discuss if sending uid to client is safe???
		$result_array = array();
		for($i=0; $i<$count; $i++) {
			//echo "<pre>"; var_dump($infoitem); echo "</pre><br /><br />";
			array_push($result_array, array(
				"uid" => $info[$i]["uid"][0],
				"cn" => $info[$i]["cn"][0],
				"rhatlocation" => $info[$i]["rhatlocation"][0],
				"mail" => $info[$i]["mail"][0],
				"title" => $info[$i]["title"][0],
				"telephonenumber" => $info[$i]["telephonenumber"][0]
			));
		}
		
		// set the status
		header('HTTP/1.1 200 OK');
		// set the content type
		header('content-type: application/json');
		//echo json_encode($result_array);
		
		// need to revisit this part. mobile native app's should send this data too (as we need for html5)
		if(isset($_REQUEST["clienttype"]))
			echo $_REQUEST['callback'] . '('.json_encode($result_array).')';
		else
			echo json_encode($result_array);
	
		ldap_close($ds);

	} else {
		// set the status
		header('HTTP/1.1 404 Not Found');
		// set the content type
		header('Content-type: application/json');
		echo json_encode("Unable to connect to LDAP server.");
	}
?>
