<?php
        // times are all UTC
        function LDAPtoUnix($ldap_ts) {
            $year = substr($ldap_ts,0,4);
            $month = substr($ldap_ts,4,2);
            $day = substr($ldap_ts,6,2);
            $hour = substr($ldap_ts,8,2);
            $minute = substr($ldap_ts,10,2);
            $second = substr($ldap_ts,12,2);

            return mktime($hour, $minute, $second, $month, $day, $year);
        }
        
        function UnixToLDAP($unix_ts) {
            $year = gmdate("Y", $unix_ts);
            $month = gmdate("m", $unix_ts);
            $day = gmdate("d", $unix_ts);
            $hour = gmdate("H", $unix_ts);
            $minute = gmdate("i", $unix_ts);
            $second = gmdate("s", $unix_ts);
            return $year.$month.$day.$hour.$minute.$second."Z";
        }
	
	$last_sync_date = (isset($_REQUEST["last_sync_date"]) && strlen($_REQUEST["last_sync_date"]))?$_REQUEST["last_sync_date"]:((isset($_REQUEST["where"]) && strlen($_REQUEST["where"]))?((isset(json_decode($_REQUEST["where"])->last_sync_date) && strlen(json_decode($_REQUEST["where"])->last_sync_date))?json_decode($_REQUEST["where"])->last_sync_date:""):"");
	
	if(strlen($last_sync_date) == 0){
		echo "error! no proper query parameter received...";
		die;
	}
	$response = "false";
        // TODO: check if client is already sync and respond accordingly...
        require_once("constants.php");
        $ds=ldap_connect($ldap_url);
	if ($ds) { 
		$r=ldap_bind($ds);     // this is an "anonymous" bind, typically
							   // read-only access
                                                           
                // TODO: this query takes too long (~6 secs on RH wired network). FIX IT!
                //$time_start = microtime(true);

                $sr=ldap_search($ds, $ldap_dn, $ldap_sync_modifytime_query_head . UnixToLDAP($last_sync_date) . ")");
                
                
                /*$sr=ldap_search($ds, $ldap_dn, $alternate_time_query);  

		$info = ldap_get_entries($ds, $sr);

		$count = $info["count"];
		for($i=0; $i<$count; $i++) {
                    if(LDAPtoUnix($info[$i]["modifytimestamp"][0]) > $last_sync_date) {
                        $response = "true";
                        break;
                    }
		}*/
                
                /*$time_end = microtime(true);
                $time = $time_end - $time_start;
                
                echo "Search in $time seconds\n";*/
		
                if(ldap_count_entries($ds, $sr) > 0) {
                    $response = "true";
                }
                
		ldap_close($ds);
	}
	$result_array = array();
        array_push($result_array, array(
                        "sync_required" => $response
                ));
		
        // set the status
        header('HTTP/1.1 200 OK');
        // set the content type
        header('content-type: application/json');
        
        // need to revisit this part. mobile native app's should send this data too (as we need for html5)
        if(isset($_REQUEST["clienttype"]))
                echo $_REQUEST['callback'] . '('.json_encode($result_array).')';
        else
                echo json_encode($result_array);
?>