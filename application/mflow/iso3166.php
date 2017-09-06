<?php
    /*******************************
     # iso3166.php [Mflow]
     # Author: masuwonchon@gmail.com
     #
     *******************************/   
    
    require_once("lib/MaxMind/geoip.inc");
    
    $geoIP = new GeoIP();
    
    /**
     * Checks whether the specified country code is valid (case-insensitive).
     * Parameters:
     *      countryCode - country code.
     * Return:
     *      True (boolean), in case it was found. Otherwise, false (boolean).
     */ 
    function is_valid_country_code ($countryCode) {
        global $geoIP;
        
        $countryNumber = -1;
        foreach ($geoIP->GEOIP_COUNTRY_CODE_TO_NUMBER as $code => $number) {
            if (strcasecmp($code, $countryCode) === 0) {
                $countryNumber = $number;
                break;
            }
        }
        unset($code, $number);
        
        // If country code was not found or country code was empty, $countryNumber === -1 || $countryNumber === 0
        return !($countryNumber === -1 || $countryNumber === 0);
    }
    
    /**
     * Checks whether the specified country name is valid (case-insensitive).
     * Parameters:
     *      countryName - country name.
     * Return:
     *      True (boolean), in case it was found. Otherwise, false (boolean).
     */ 
    function is_valid_country_name ($countryName) {
        global $geoIP;
        
        foreach ($geoIP->GEOIP_COUNTRY_NAMES as $name) {
            if (strcasecmp($name, $countryName) === 0) {    
                return true;
                break;
            }
        }
        unset($name);
        
        return false;
    }   
    
    /**
     * Returns the name of the country, corresponding to the specified country code.
     * Parameters:
     *      countryCode - country code.
     * Return:
     *      Country name in case it was found. Otherwise, false (boolean).
     */ 
    function get_country_name_from_code ($countryCode) {
        global $geoIP;
        
        $countryNumber = -1;
        foreach ($geoIP->GEOIP_COUNTRY_CODE_TO_NUMBER as $code => $number) {
            if (strcasecmp($code, $countryCode) === 0) {
                $countryNumber = $number;
                break;
            }
        }
        unset($code, $number);
        
        // Country code was not found
        if ($countryNumber === -1) return false;
        
        return $geoIP->GEOIP_COUNTRY_NAMES[$countryNumber];
    }

?>
