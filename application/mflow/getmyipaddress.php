<?php
/*******************************
 # getmyipaddress.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *******************************/
    
    require_once("./config.php");
    require_once("./util.php");
    require_once("./lib/MaxMind/geoipcity.inc");
    
    // Retrieve External IP address and location
    $ext_IP = (!getenv("SERVER_ADDR")) ? "127.0.0.1" : getenv("SERVER_ADDR");
    if ($ext_IP == "127.0.0.1") {
        $ext_IP_NAT = true;
    } else {
        $ext_IP_NAT = false;
        
        foreach ($config['internal_domains'] as $key => $value) {
            $internal_domain_nets = explode(";", $key);
            
            foreach($internal_domain_nets as $subnet) {
                if (ip_address_in_net($ext_IP, $subnet)) {
                    $ext_IP_NAT = true;
                    break;
                }
            }
            unset($subnet);
        }
        unset($key, $value);
    }
    
    if (extension_loaded('curl')) {
        if ($ext_IP_NAT) {
            $NAT_IP = $ext_IP;
            try {
                if (extension_loaded('curl')) {
                    for ($i = 0; $i < 4; $i++) {
                        $curlsession = curl_init();
                        curl_setopt($curlsession, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curlsession, CURLOPT_CONNECTTIMEOUT, 5);

			$MYIPADDRESS = "https://www.iplocation.net/find-ip-address";
			$MYIPADDRESS = "http://mijnip.antagonist.nl";

			curl_setopt($curlsession, CURLOPT_URL, $MYIPADDRESS);
                        $ext_IP = curl_exec($curlsession);

			print "ext_ip: $ext_IP<br>";
                    
			if (substr_count($ext_IP, ".") == 3) {
                            if ($ext_IP == $NAT_IP) {
                                $ext_IP_NAT = false;
                            }
                            break;
                        }
                    
                        curl_close($curlsession);
                    }
                }

                if (substr_count($ext_IP, ".") != 3) {
                    $ext_IP = $NAT_IP;
                    $ext_IP_error = "Unable to retrieve external IP address";
                }
            } catch (Exception $e) {}
        }
    } else {
        $result['status'] = 1;
        $result['status_message'] = "PHP cURL module is not installed";
        echo json_encode($result);
        die();
    }
    
    if ($config['geolocation_db'] == "MaxMind") {
        $GEO_database = geoip_open("./".$config['maxmind_path'], GEOIP_STANDARD);
        $data = geoip_record_by_addr($GEO_database, $ext_IP);
        
        if (isset($data->country_name)) {
            $ext_IP_country = strtoupper($data->country_name);
        }
        if (!isset($ext_IP_country) || $ext_IP_country == "") $ext_IP_country = "(UNKNOWN)";

        if (isset($data->country_code) && isset($data->region)
                && array_key_exists($data->country_code, $GEOIP_REGION_NAME)
                && array_key_exists($data->region, $GEOIP_REGION_NAME[$data->country_code])) {
            $ext_IP_region = strtoupper($GEOIP_REGION_NAME[$data->country_code][$data->region]);
        }
        if (!isset($ext_IP_region) || $ext_IP_region == "") $ext_IP_region = "(UNKNOWN)";

        if (isset($data->city)) {
            $ext_IP_city = strtoupper($data->city);
        }
        if (!isset($ext_IP_city) || $ext_IP_city == "") $ext_IP_city = "(UNKNOWN)";
    } else {
        $ext_IP_country = "(UNKNOWN)";
        $ext_IP_region = "(UNKNOWN)";
        $ext_IP_city = "(UNKNOWN)";
    }
    
    $ext_IP_country = fix_comma_separated_name(utf8_encode($ext_IP_country));
    $ext_IP_region = fix_comma_separated_name(utf8_encode($ext_IP_region));
    $ext_IP_city = fix_comma_separated_name(utf8_encode($ext_IP_city));
    
    // No geocoding needed if country is unknown
    if ($ext_IP_country != "(UNKNOWN)") {
        $geocode_place = $ext_IP_country;
        
        if ($ext_IP_region != "(UNKNOWN)") {
            $geocode_place .= ", ".$ext_IP_region;
        }
        if ($ext_IP_city != "(UNKNOWN)") {
            $geocode_place .= ", ".$ext_IP_city;
        }
        
        $lat_lng = geocode($geocode_place);
    }
    
    $location = $ext_IP_country.",".$ext_IP_region.",".$ext_IP_city;
    if (isset($lat_lng) && is_array($lat_lng)) {
        $location .= ",".$lat_lng[0].",".$lat_lng[1];
    } else {
        $location .= ",(UNKNOWN),(UNKNOWN)";
    }
    
    /**
     * Starts calls to the Google Maps API GeoCoder. It is derived from the 'geocode()'
     * method in [index.php].
     * Return:
     *      array(lat, lng) on success, or 'false' (bool) on failure
     */ 
    function geocode($place) {
        global $config;
        
        $requestURL = "http://maps.google.com/maps/api/geocode/xml?address=".urlencode($place)."&sensor=false";
        
        // Prefer cURL over the 'simplexml_load_file' command, for increased stability
        if (extension_loaded("curl")) {
            for ($i = 0; $i < 2; $i++) {
                $curlsession = curl_init();
                curl_setopt($curlsession, CURLOPT_URL, $requestURL);
                curl_setopt($curlsession, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curlsession, CURLOPT_CONNECTTIMEOUT, 5);
            
                $result = curl_exec($curlsession);
                $xml = simplexml_load_string($result);
                
                curl_close($curlsession);
                
                // Stop when successful
                if (isset($xml->result->geometry->location)) break;
            }
        } else {
            $xml = simplexml_load_file($requestURL);
        }
        
        if (isset($xml->status) && $xml->status == "OVER_QUERY_LIMIT") {
            time_nanosleep(0, 1000000000);
            geocode($place);
        } else if (isset($xml->result->geometry->location)) {
            $lat = $xml->result->geometry->location->lat;
            $lng = $xml->result->geometry->location->lng;
        }
        
        return (isset($xml->status) && $xml->status == "OK" && isset($lat) && isset($lng)) ? array($lat, $lng) : false;
    }

?>

<!DOCTYPE html>
<html>
    <head>
        <title>MFLOW / Retrieve Location</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    </head>
    <body>
        <h1>MFLOW / Retrieve Location</h1>
            
        <div id="setup_guidelines">Settings in config.php<br /><br /></div>      
        <div id="config_data" style="display:none;"><?php echo $location; ?></div>
        
        <script type="text/javascript">
            var NAT_IP = "<?php if (isset($NAT_IP)) { echo $NAT_IP; } ?>";
            var ext_IP = "<?php echo $ext_IP; ?>";
            var ext_IP_NAT = <?php if ($ext_IP_NAT === true) echo "1"; else echo "0"; ?>;
            var ext_IP_error = "<?php if (isset($ext_IP_error)) echo $ext_IP_error; ?>";
            var ext_IP_country = "<?php echo $ext_IP_country; ?>";
            var ext_IP_region = "<?php echo $ext_IP_region; ?>";
            var ext_IP_city = "<?php echo $ext_IP_city; ?>";
            var ext_IP_coordinates = "<?php if (isset($lat_lng) && is_array($lat_lng)) { echo $lat_lng[0].','.$lat_lng[1]; } ?>";
            var first_internal_domain = "<?php reset($config['internal_domains']); echo key($config['internal_domains']); ?>";

            // Setup guidelines
            if (ext_IP_coordinates != "") {
                document.getElementById("setup_guidelines").innerHTML += "$config['map_center']=\"" + ext_IP_coordinates + "\";<br /><br />";
            }
            
            if ((ext_IP_NAT && (NAT_IP == ext_IP)) || ext_IP_error != "") {
                document.getElementById("setup_guidelines").style.display = "none";
            } else if (ext_IP_country != "(UNKNOWN)") {
                var region = (ext_IP_region == "(UNKNOWN)") ? "" : ext_IP_region;
                var city = (ext_IP_city == "(UNKNOWN)") ? "" : ext_IP_city;
                document.getElementById("setup_guidelines").innerHTML += "$config['internal_domains'] = array( <br />\
                                <span style=\"padding-left: 50px;\">\"" + first_internal_domain + "\" => array(\"country\" => \"" + ext_IP_country + "\", \"region\" => \"" + region + "\", \"city\" => \"" + city + "\")</span><br /> \
                        );"
            }
        </script>
    </body>
</html>
