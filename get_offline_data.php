<?php
        function UnixToLDAP($unix_ts) {
            $year = gmdate("Y", $unix_ts);
            $month = gmdate("m", $unix_ts);
            $day = gmdate("d", $unix_ts);
            $hour = gmdate("H", $unix_ts);
            $minute = gmdate("i", $unix_ts);
            $second = gmdate("s", $unix_ts);
            return $year.$month.$day.$hour.$minute.$second."Z";
        }
        
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
        
        $last_sync_date = (isset($_REQUEST["last_sync_date"]) && strlen($_REQUEST["last_sync_date"]))?$_REQUEST["last_sync_date"]:((isset($_REQUEST["where"]) && strlen($_REQUEST["where"]))?((isset(json_decode($_REQUEST["where"])->last_sync_date) && strlen(json_decode($_REQUEST["where"])->last_sync_date))?json_decode($_REQUEST["where"])->last_sync_date:""):"");
	
	if(strlen($last_sync_date) == 0){
		echo "error! no proper query parameter received...";
		die;
	}
	
	$ds=ldap_connect($ldap_url);
	if ($ds) { 
		$r=ldap_bind($ds);     // this is an "anonymous" bind, typically
							   // read-only access

		$sr=ldap_search($ds, $ldap_dn, $ldap_modifytime_query_head. UnixToLDAP($last_sync_date) ."))");  

		$info = ldap_get_entries($ds, $sr);


		/*echo "<pre>"; var_dump($info); echo "</pre><br /><br />";
		die;*/
		$result_array = array();
		$count = $info["count"];
		for($i=0; $i<$count; $i++) {
			//echo "<pre>"; var_dump($infoitem); echo "</pre><br /><br />";
			array_push($result_array, array(
                                "id" => ($i+1),
                                "uid" => $info[$i]["uid"][0],
				"cn" => $info[$i]["cn"][0],
				"rhatlocation" => $info[$i]["rhatlocation"][0],
				"mail" => $info[$i]["mail"][0],
				"title" => $info[$i]["title"][0],
				"telephonenumber" => $info[$i]["telephonenumber"][0],
				"manager" => $info[$i]["manager"][0]
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
