<?php
/******************************************************
 # getdefault.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

    require_once("../config.php");
    header("content-type: application/json");

    $result = array();

    if (!isset($default)) {
        $result['status'] = 1;
        $result['status_message'] = "Could not find default config";
        echo json_encode($result);
        die();   
    }

    $result['constants'] = $default;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>
