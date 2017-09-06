<?php
/******************************************************
 # getgeolocationdata.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *****************************************************/

    require_once("../config.php");
    require_once("../util.php");
    require_once("../lib/MaxMind/geoipcity.inc");
    header("content-type: application/json");

    if ($config['debug'] == True) {
	error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: HELLO\n", 3, $config['log_file']);
	#   web :: http://freegeoip.net/json/183.110.18.223
    }

    if (isset($_POST['params'])) {
        $request = $_POST['params'];
    } else {
        $result['status'] = 1;
        $result['status_message'] = "No parameters provided";
        echo json_encode($result);
        die();
    }
    
    $result = array();
    $result['geolocation_data'] = array();
    
    if ($config['geolocation_db'] == "MaxMind") {
        $db = geoip_open("../".$config['maxmind_path'], GEOIP_STANDARD);
    }

    if ($config['debug'] == True) {
	$sizeofrequest=count($request);
	error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: request size:$sizeofrequest\n", 3, $config['log_file']);
    }


   
    foreach ($request as $address) {
        $country = "";
        $region = "";
        $city = "";


        
        foreach ($config['internal_domains'] as $key => $value) {
            $internal_domain_networks = explode(";", $key);
            
            /*
             * Check whether a NATed setup was used. If so, use the geolocation data provided
             * in the configuration file. Otherwise, use a geolocation service.
             */
            $internal_address = false;
            foreach ($internal_domain_networks as $subnet) {
                if (ip_address_in_net($address, $subnet)) {
                    $country = ($value['country'] === "") ? "(UNKNOWN)" : strtoupper($value['country']);
                    $region = ($value['region'] === "") ? "(UNKNOWN)" : strtoupper($value['region']);
                    $city = ($value['city'] === "") ? "(UNKNOWN)" : strtoupper($value['city']);
                    break;
                }
            }
            unset($subnet);
            
            // Since matching internal domain has been found, there's no need to iterate over other internal domains
            if ($country != "" || $region != "" || $city != "") break;
        }
        unset($key, $value);
        
        if ($country == "" || $region == "" || $city == "") {
	    if ($config['geolocation_db'] == "MaxMind") {
                if (strpos($address, ":") === false) { // IPv4 address
                    $data = geoip_record_by_addr($db, $address);

			
                } else { // IPv6 address
                    $data = geoip_record_by_addr_v6($db, $address);
                }
            
                $country = (!isset($data->country_name) || $data->country_name === "-") ? "(UNKNOWN)" : strtoupper($data->country_name);
                $region = (!isset($data->country_code)
                        || !isset($data->region)
                        || !array_key_exists($data->country_code, $GEOIP_REGION_NAME)
                        || !array_key_exists($data->region, $GEOIP_REGION_NAME[$data->country_code])
                        || $GEOIP_REGION_NAME[$data->country_code][$data->region] === "") ? "(UNKNOWN)" : strtoupper($GEOIP_REGION_NAME[$data->country_code][$data->region]);
                $city = (!isset($data->city) || $data->city === "-") ? "(UNKNOWN)" : strtoupper($data->city);
                $continent_code = (!isset($data->continent_code) || $data->continent_code === "-") ? "(UNKNOWN)" : strtoupper($data->continent_code);
            } else {
                $country = "(UNKNOWN)";
                $region = "(UNKNOWN)";
                $city = "(UNKNOWN)";
            }
        }

        $country = fix_comma_separated_name(utf8_encode($country));
        $region = fix_comma_separated_name(utf8_encode($region));
        $city = fix_comma_separated_name(utf8_encode($city));
        
        if (isset($continent_code) && $continent_code !== "(UNKNOWN)") {
            if ($continent_code === "Afrika") {
                $continent = "Afrika";
            } else if ($continent_code === "AS") {
                $continent = "Asia";
            } else if ($continent_code === "EU") {
                $continent = "Europe";
            } else if ($continent_code === "NA") {
                $continent = "North-America";
            } else if ($continent_code === "SA") {
                $continent = "South-America";
            } else if ($continent_code === "OC") {
                $continent = "Ocenania";
            }
            
            $continent = strtoupper($continent);
        } else {
            $continent = "(UNKNOWN)";
        }

        array_push($result['geolocation_data'], array('address' => $address, 'continent' => $continent, 'country' => $country, 'region' => $region, 'city' => $city));
        unset($continent_code, $continent);
    }
    unset($address);
    
    // Close database connections
    if ($config['geolocation_db'] == "MaxMind") {
        geoip_close($db);
    }
    unset($db);

    $result['status'] = 0;

    echo json_encode($result);

    if ($config['debug'] == True) {
    	error_log ("[DEBUG]::".__FILE__."::".__LINE__.":: BYE\n", 3, $config['log_file']);
    }

    die();

?>
