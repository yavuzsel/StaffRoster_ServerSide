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
	
	$query = (isset($_REQUEST["colleague"]) && strlen($_REQUEST["colleague"]))?$_REQUEST["colleague"]:((isset($_REQUEST["where"]) && strlen($_REQUEST["where"]))?((isset(json_decode($_REQUEST["where"])->colleague) && strlen(json_decode($_REQUEST["where"])->colleague))?json_decode($_REQUEST["where"])->colleague:""):"");
	
	if(strlen($query) == 0){
		echo "error! no proper query parameter received...";
		die;
	}

	$ds=ldap_connect($ldap_url);
	if ($ds) { 
		$r=ldap_bind($ds);     // this is an "anonymous" bind, typically
							   // read-only access

		$sr=ldap_search($ds, $ldap_dn, $ldap_query_head.$query.$ldap_query_tail);  

		$info = ldap_get_entries($ds, $sr);


		//echo "<pre>"; var_dump($info); echo "</pre><br /><br />";
		//$result_array = array();
		$count = $info["count"];
		// should be only one, discuss if sending uid to client is safe???
		for($i=0; $i<$count; $i++) {
			//echo "<pre>"; var_dump($infoitem); echo "</pre><br /><br />";
			$colleague_query = $info[$i]["manager"][0];
		}
		$colleague_query_fields = explode(",", $colleague_query);
		$manager_filter = $colleague_query;
		$manager_dn = substr($colleague_query, strpos($colleague_query, ",")+1);
		// get manager here

		$sr=ldap_search($ds, $manager_dn, $ldap_mngr_filter_query_head.$manager_filter.")");  

		$info = ldap_get_entries($ds, $sr);
		
		$count = $info["count"];

		$result_array = array();
		for($i=0; $i<$count; $i++) {
			//echo "<pre>"; var_dump($infoitem); echo "</pre><br /><br />";
			array_push($result_array, array(
				"cn" => $info[$i]["cn"][0],
				"rhatlocation" => $info[$i]["rhatlocation"][0],
				"mail" => $info[$i]["mail"][0]
			));
		}

		/*echo "<pre>"; var_dump($result_array); echo "</pre>";
		die;*/
		
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
