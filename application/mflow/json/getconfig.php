<?php
/******************************************************
 # getconfig.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

    require_once("../config.php");
    header("content-type: application/json");

    $result = array();

    if (!isset($config)) {
        $result['status'] = 1;
        $result['status_message'] = "Could not find configuration file (config.php)";
        echo json_encode($result);
        die();   
    }

    $result['config'] = $config;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>
