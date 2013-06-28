<?php
    require_once("constants.php");
    if(isset($_REQUEST['uid']) && strlen($_REQUEST['uid']) > 0){
	$uid = $_REQUEST['uid'];
        $path_to_img = $img_dir . $uid;
        if(!file_exists($path_to_img)) {
            $url_to_docspace = $docspace_users_endpoint . $uid;
            $user_response = CallAPI("GET", $url_to_docspace);
            // curl seems hiding the credentials if load fails? simplexml_load doesn't for sure. :-/
            $xml_response = simplexml_load_string($user_response);
            
            $img_url = $docspace_images_endpoint . $xml_response->return->ID;
            
            save_image($img_url, $path_to_img);
        }
        $url_to_img = $base_url . $path_to_img;
        
         // set the status
        header('HTTP/1.1 200 OK');
        // set the content type
        header('content-type: application/json');
        echo json_encode(array("img_url" => $url_to_img));
        //echo $xml_response->return->ID;
        //echo htmlspecialchars($user_response);
    } else {
        // set the status
        header('HTTP/1.1 404 Not Found');
        // set the content type
        header('content-type: application/json');
        echo json_encode("User not set!");
    }
    
    function save_image($img, $fullpath){
        global $usr, $pwd;
        $ch = curl_init ($img);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $usr.":".$pwd);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $rawdata = curl_exec($ch);
        curl_close ($ch);
        if(file_exists($fullpath)){
                unlink($fullpath);
        }
        $fp = fopen($fullpath,'x');
        $xml_data = simplexml_load_string($rawdata);
        /*echo base64_decode($xml_data->return);
        die;*/
        fwrite($fp, base64_decode($xml_data->return));
        fclose($fp); 
    }

    // Method: POST, PUT, GET etc
    // Data: array("param" => "value") ==> index.php?param=value
    function CallAPI($method, $url, $data = false)
    {
        global $usr, $pwd;
        try {
            $curl = curl_init();
        
            switch ($method)
            {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);
        
                    if ($data)
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_PUT, 1);
                    break;
                default:
                    if ($data)
                        $url = sprintf("%s?%s", $url, http_build_query($data));
            }
            // Authentication:
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $usr.":".$pwd);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
            $content = curl_exec($curl);
            if (FALSE === $content)
                throw new Exception(curl_error($curl), curl_errno($curl));
        
            // ...process $content now
            return $content;
        } catch(Exception $e) {
            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);
        
        }
    }

?>