<?php
/******************************************************
 # getextensions.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

    require_once("../extensions.php");
    header("content-type: application/json");

    $result = array();

    if (!isset($extensions)) {
        $result['status'] = 1;
        $result['status_message'] = "Could not find extensions file (extensions.php)";
        echo json_encode($result);
        die();   
    }

    $result['extensions'] = $extensions;
    $result['status'] = 0;
    echo json_encode($result);
    die();

?>
