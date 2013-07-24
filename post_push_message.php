<?php
    require_once("constants.php");
    
    if(isset($_REQUEST['message']) && strlen($_REQUEST['message']) > 0) {
        $message = $_REQUEST['message'];
    } else {
        $message = "Hello from StaffRoster!";
    }
    if(isset($_REQUEST['location']) && strlen($_REQUEST['location']) > 0) {
        // location is received. send message to everyone in that location
        $location = $_REQUEST['location'];
	$location = ldap_escape($location);
        
        // get uids given location
        $ds=ldap_connect($ldap_url);
	if ($ds) { 
            $r=ldap_bind($ds);     // this is an "anonymous" bind, typically
                                                       // read-only access
            $sr=ldap_search($ds, $ldap_dn, $location_query_head.$location.$location_query_tail);
            $info = ldap_get_entries($ds, $sr);
            $uids_array = array();
            $count = $info["count"];
            for($i=0; $i<$count; $i++) {
                array_push($uids_array, $info[$i]["uid"][0]);
            }
            ldap_close($ds);
        }
        // send messages to uids
        if(count($uids_array) > 0){
            // request only if there are employees to push the message
            $data = array("message" => array("alert" => $message, "badge" => 1), "alias" => $uids_array, "staging" => "production");
            push_data_to_url($data, $agpush_selected_url, "Location-based");
        } else {
	    // set the status
	    header('HTTP/1.1 200 OK');
	    // set the content type
	    header('content-type: application/json');
	    echo json_encode(array("response" => "Location based message, no receivers found!"));
	}
        
    } else {
        // no location is received. send to everbody? or do nothing?
        $data = array("alert" => $message, "badge" => 1, "staging" => "production");
        push_data_to_url($data, $agpush_broadcast_url, "Broadcast");
    }
    
    function push_data_to_url($data, $push_server_url, $type_message="") {
        global $msr_pushapplicationid, $msr_mastersecret;
        $data_string = json_encode($data);
        $ch = curl_init($push_server_url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $msr_pushapplicationid.":".$msr_mastersecret);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        // set the status
        header('HTTP/1.1 200 OK');
        // set the content type
        header('content-type: application/json');
        echo json_encode(array("response" => $type_message. " " . $result));
        curl_close ($ch);
    }
    
    // need to escape special chars in location. so following function helps here.
    
    /**
    * function ldap_escape
    * @author Chris Wright
    * @version 2.0
    * @param string $subject The subject string
    * @param bool $dn Treat subject as a DN if TRUE
    * @param string|array $ignore Set of characters to leave untouched
    * @return string The escaped string
    */
    function ldap_escape ($subject, $dn = FALSE, $ignore = NULL) {
    
	// The base array of characters to escape
	// Flip to keys for easy use of unset()
	$search = array_flip($dn ? array('\\', ',', '=', '+', '<', '>', ';', '"', '#') : array('\\', '*', '(', ')', "\x00"));
    
	// Process characters to ignore
	if (is_array($ignore)) {
	    $ignore = array_values($ignore);
	}
	for ($char = 0; isset($ignore[$char]); $char++) {
	    unset($search[$ignore[$char]]);
	}
    
	// Flip $search back to values and build $replace array
	$search = array_keys($search); 
	$replace = array();
	foreach ($search as $char) {
	    $replace[] = sprintf('\\%02x', ord($char));
	}
    
	// Do the main replacement
	$result = str_replace($search, $replace, $subject);
    
	// Encode leading/trailing spaces in DN values
	if ($dn) {
	    if ($result[0] == ' ') {
		$result = '\\20'.substr($result, 1);
	    }
	    if ($result[strlen($result) - 1] == ' ') {
		$result = substr($result, 0, -1).'\\20';
	    }
	}
    
	return $result;
    }
?>