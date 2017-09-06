<?php
/*******************************
 # extensions.php [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *******************************/
    
    // Available extensions
    $EX_LOC = new Extension('OTX', array(
        new ExtensionField('Geolocation algorithm', 'Algorithm',    '%loc_algorithm'),
        new ExtensionField('Timestamp',             'Timestamp',    '%loc_timestamp'),
        new ExtensionField('Latitude (int)',        'Lat. (int)',   '%loc_lat_int'),
        new ExtensionField('Latitude (dec)',        'Lat. (dec)',   '%loc_lat_dec'),
        new ExtensionField('Longitude (int)',       'Lng. (int)',   '%loc_lng_int'),
        new ExtensionField('Longitude (dec)',       'Lng. (dec)',   '%loc_lng_dec')// ,
    ));
    
    // Enabled extensions (comma-separated)
    $extensions = array();
    
    class Extension {
        var $name;
        var $fields; // array
        
        public function __construct ($name, $fields) {
            $this->name = $name;
            $this->fields = $fields;
        }
    }
    
    class ExtensionField {
        var $full_name;
        var $short_name;
        var $nfdump_short;
        
        public function __construct ($full_name, $short_name, $nfdump_short) {
            $this->full_name = $full_name;
            $this->short_name = $short_name;
            $this->nfdump_short = $nfdump_short;
        }
    }
    
    function is_extension_active ($name) {
        global $extensions;
        $result = false;
        
        foreach ($extensions as $extension) {
            if ($extension->name === $name) {
                $result = true;
                break;
            }
        }
        
        return $result;
    }
    
?>
