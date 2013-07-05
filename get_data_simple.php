<?php
	// this is get data, because i was planning to implement all api's here. but to try pipes, i decided not to.
	// this is get simple data because i was planning to implement different response types and output formats here, but to use aerogear conroller in the near future and fully switch to jboss, i kept this implementation as simple as possible
	
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
	
	$query = (isset($_REQUEST["query"]) && strlen($_REQUEST["query"]))?$_REQUEST["query"]:((isset($_REQUEST["where"]) && strlen($_REQUEST["where"]))?((isset(json_decode($_REQUEST["where"])->query) && strlen(json_decode($_REQUEST["where"])->query))?json_decode($_REQUEST["where"])->query:""):"");
	
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


		/*echo "<pre>"; var_dump($info); echo "</pre><br /><br />";
		die;*/
		$result_array = array();
		$count = $info["count"];
		for($i=0; $i<$count; $i++) {
			//echo "<pre>"; var_dump($infoitem); echo "</pre><br /><br />";
			$employee_as_dict = array(
				"uid" => $info[$i]["uid"][0],
				"cn" => $info[$i]["cn"][0],
				"rhatlocation" => $info[$i]["rhatlocation"][0],
				"mail" => $info[$i]["mail"][0],
				"title" => $info[$i]["title"][0],
				"telephonenumber" => $info[$i]["telephonenumber"][0]
			);
			if(array_key_exists("mobile",$info[$i]))
				$employee_as_dict["mobile"] = $info[$i]["mobile"][0];
			array_push($result_array, $employee_as_dict);
		}
		
		/*$result_array = array(
			"cn" => $info[0]["cn"][0],
			"rhatlocation" => $info[0]["rhatlocation"][0],
			"mail" => $info[0]["mail"][0]
		);*/
		
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
