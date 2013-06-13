<?php
	
	$last_sync_date = (isset($_REQUEST["last_sync_date"]) && strlen($_REQUEST["last_sync_date"]))?$_REQUEST["last_sync_date"]:((isset($_REQUEST["where"]) && strlen($_REQUEST["where"]))?((isset(json_decode($_REQUEST["where"])->last_sync_date) && strlen(json_decode($_REQUEST["where"])->last_sync_date))?json_decode($_REQUEST["where"])->last_sync_date:""):"");
	
	if(strlen($last_sync_date) == 0){
		echo "error! no proper query parameter received...";
		die;
	}
	
        // TODO: check if client is already sync and respond accordingly...
        
	$result_array = array();
        array_push($result_array, array(
                        "sync_required" => "false"
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