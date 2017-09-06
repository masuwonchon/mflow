<?php
/******************************************************
 # geterrors.php [GFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

/*
0: "PHP PDO driver for SQLite3 is not installed.";
1: "Could not find database file.";
2: "The database file is not readable.";
3: "The database file is not writable.";
4: "Could not find the geolocation database (MaxMind).";
5: "The geolocation database (MaxMind) is not readable.";
6: "Could not find the geolocation database (IP2Location).";
7: "The geolocation database (IP2Location) is not readable.";
*/

    require_once("../config.php");
    header("content-type: application/json");

    $result = array();
    $result['error_codes'] = array();
    
    // Check PDO SQLite3 driver availability
    if (!in_array("sqlite", PDO::getAvailableDrivers())) {
        array_push($result['error_codes'], 0);
    }
    
    // Check database exists
    if (!file_exists('../'.$default['cache_db'])) {
        array_push($result['error_codes'], 1);
    }
    
    // Check database readable
    if (!is_readable('../'.$default['cache_db'])) {
        array_push($result['error_codes'], 2);
    }
    
    // Check database writable
    if (!is_writable('../'.$default['cache_db'])) {
        array_push($result['error_codes'], 3);
    }
    
    // Check geolocation database available
    if ($config['geolocation_db'] == 'MaxMind') {
        $MaxMind_path = $config['maxmind_path'];
        
        // Check for absolute or relative path
        if (substr($MaxMind_path, 0, 1) != "/") {
            $MaxMind_path = "../".$MaxMind_path;
        }
        
        if (@file_exists($MaxMind_path)) {
            if (!is_readable($MaxMind_path)) {
                array_push($result['error_codes'], 5);
            }
        } else {
            array_push($result['error_codes'], 4);
        }
    }

    $result['status'] = 0;
    echo json_encode($result);
    die();

?>
