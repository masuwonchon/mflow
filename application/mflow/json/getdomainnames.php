<?php
/******************************************************
 # getdomainnames.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

    require_once("../config.php");
    header("content-type: application/json");

    if (!isset($_POST['params'])) {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $inter_request_time = 250000; // 250ms
    $hostnames = array();
    
    foreach ($_POST['params'] as $request) {
        $hostname = gethostbyaddr($request);
	if ($config['debug'] == True) {
	    error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: hostnames: $hostname\n", 3, $config['log_file']);
	}
        array_push($hostnames, array("address" => $request, "hostname" => $hostname));
        usleep($inter_request_time);
    }
    unset($request);
    
    $result['hostnames'] = $hostnames;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>
