<?php
/******************************************************
 # getgeocoderdata.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

    require_once("../config.php");
    header("content-type: application/json");

    $result = array();

    if (isset($_POST['params'])) {
        $requests = $_POST['params'];
    } else {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    try {
        $db = new PDO("sqlite:../".$default['cache_db']);
        $result['geocoder_data'] = array();
        foreach ($requests as $request) {
            // Length of $split_request determines level (1 -> country, 2 -> region, 3 -> city)
            $split_request = explode(";", $request);
            $place = $split_request[count($split_request) - 1]; // Last element in array is actual place name
    
            if ($place === "-" || stripos($place, "NKNOWN")) {
                $lat = 0;
                $lng = 0;
            } else {
                $query = "SELECT latitude, longitude FROM geocoder_cache WHERE location = :location";
                $stmnt = $db->prepare($query);
                $stmnt->bindParam(":location", $request);
                $stmnt->execute();
                $query_result = $stmnt->fetch(PDO::FETCH_ASSOC);
        
                if ($query_result) { // Country name was found in DB
                    $lat = $query_result['latitude'];
                    $lng = $query_result['longitude'];
                } else {
                    $lat = -1;
                    $lng = -1;
                }
            }
            array_push($result['geocoder_data'], array('request' => $request, 'lat' => $lat, 'lng' => $lng));
        }
        unset($request);
    } catch(PDOException $e) {
        $result['status'] = 1;
        $result['status_message'] = "A PHP PDO driver has occurred";
        echo json_encode($result);
        die();
    }

    $result['status'] = 0;
    echo json_encode($result);
    die();

?>
