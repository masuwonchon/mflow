/******************************
 # events.js [MFLOW]
 # Author: masuwonchon@gmail.com
 #
 *******************************/


$(document).ready(function() {

    $('#error_dialog').dialog({
        autoOpen:   false,
        zIndex:     4000
    });
    $('#warning_dialog').dialog({
        autoOpen:   false,
        zIndex:     3000
    });
    $('#info_dialog').dialog({
        autoOpen:   false,
        zIndex:     2000
    });
    $('#loading_dialog').dialog({
        autoOpen:   false,
        zIndex:     1000
    });
    
    $(document).bind('loading', function () {
        show_loading_message();
    });
    $(document).trigger('loading');
    
    // Retrieve constants
    $.ajax({
        url: 'json/getdefault.php',
        success: function(data) {
            if (data.status == 0) { // Success
                constants = data.constants;
                $(document).trigger('constants_loaded');
            } else {
                show_error(813, data.status_message);
            }
        }
    });

    $(document).bind('config_loaded', function () {
        init_panel();
        $(document).trigger('phase_1_loaded');
    });

    // Retrieve config
    $.ajax({
        url: 'json/getconfig.php',
        success: function(data) {
            if (data.status == 0) { // Success
                config = data.config;
                $(document).trigger('config_loaded');
            } else {
                show_error(801, data.status_message);
            }
        }
    });

 
    $(document).bind('constants_loaded', function () {
        if (config != undefined && constants != undefined && extensions != undefined) {
            $(document).trigger('phase_1_loaded');
        }
    });

    // Fired when all configurations have been loaded (config, constants and active_extensions)
    $(document).bind('phase_1_loaded', function () {
        $(document).trigger('load_session_data');
    });
    
    $(document).bind('load_session_data', function (event, data) {
        show_loading_message('Loading session data');
        
        if (data == undefined) {
            data = {};
        }
        
        // Retrieve session data
        $.ajax({
            url: 'json/getsessiondata.php',
            data: { 
                params: data 
            },
            success: function(data) {
                if (data.status == 0) { // Success
                    $(document).trigger('session_data_loaded', data.session_data);
                } else {
                    show_error(803, data.status_message);
                }
            }
        });
    });
    
    $(document).bind('session_data_loaded', function (event, data) {
        session_data = data;

        // If 'map' is undefined, MFLOW has just been started
        if (map == undefined) {
            init_map();
            configure_panel();
        } else {
            update_panel();
        }
        
        $(document).trigger('load_flow_data');
    });
    
    $(document).bind('session_data_changed', function (event, session_params) {
        $.ajax({
            url: 'json/setsessiondata.php',
            data: { 
                params: session_params 
            },
            success: function(data) {
                if (data.status == 0) { // Success
                    // Update 'local' session data as well (after server-side verification)
                    $.each(data.session_data, function(key, value) {
                        session_data[key] = value;
                    });
                } else {
                    show_error(807, data.status_message);

                    if (session_params.hasOwnProperty('date1') || session_params.hasOwnProperty('hours1') || session_params.hasOwnProperty('minutes1')
                            || session_params.hasOwnProperty('date2') || session_params.hasOwnProperty('hours2') || session_params.hasOwnProperty('minutes2')) {
                        update_panel();
                    }
                }
            }
        });
    });
    
    $(document).bind('load_flow_data', function () {
        show_loading_message('Loading flow data');
        
        // Flow data
        var selected_nfsen_sources = [];
        $("#nfsensources option:selected").each(function() {
            selected_nfsen_sources.push($(this).val());
        });
        
        $.ajax({
            url: 'json/getflowdata.php',
            data: {
                params: {
                    'date1': session_data['date1'],
                    'date2': session_data['date2'], 
                    'hours1': session_data['hours1'],
                    'hours2': session_data['hours2'], 
                    'minutes1': session_data['minutes1'],
                    'minutes2': session_data['minutes2'],
                    'flow_record_count': session_data['flow_record_count'],
                    'nfsen_filter': session_data['flow_filter'],
                    'nfsen_option': session_data['nfsen_option'],
                    'nfsen_profile': session_data['nfsen_profile'],
                    'nfsen_profile_type': session_data['nfsen_profile_type'],
                    'nfsen_selected_sources': session_data['nfsen_selected_sources'],
                    'nfsen_stat_order': session_data['nfsen_stat_order'],
                    'aggregation_fields': session_data['aggregation_fields'],
                }
            },
            success: function(data) {
                if (data.status == 0 || (data.status == 1 && data.status_message == "No flow records in result set")) { // Success
                    $(document).trigger('flow_data_loaded', data);
                } else {
                    show_error(804, data.status_message);
                    $(document).trigger('loaded');
                }
            }
        });
    });
    
    $(document).bind('flow_data_loaded', function (event, data) {
        if (data.flow_data == undefined || data.flow_data.length == 0) {
            flow_data = [];
            $(document).trigger('loaded');
        } else {
            flow_data = data.flow_data;
            $(document).trigger('load_geolocation_data');
        }
    });
    
    $(document).bind('load_geolocation_data', function () {
        show_loading_message('Loading geolocation data');
                
        // Collect all IP addresses
        geolocation_request = [];
        $.each(flow_data, function(key, value) {
            if ($.inArray(value.ip_src, geolocation_request) == -1) {
                geolocation_request.push(value.ip_src);
            }
            if ($.inArray(value.ip_dst, geolocation_request) == -1) {
                geolocation_request.push(value.ip_dst);
            }
        });
        
        // Geolocation data
        $.ajax({
            url: 'json/getgeolocationdata.php',
            data: {
                params: geolocation_request
            },
            success: function(data) {
                if (data.status == 0) { // Success
                    $(document).trigger('geolocation_data_loaded', data);
                } else {
                    show_error(805, data.status_message);
                    $(document).trigger('loaded');
                }
            }
        });
    });
    
    $(document).bind('geolocation_data_loaded', function (event, data) {
        geolocation_data = data.geolocation_data;
        $.each(flow_data, function(flow_index, flow_item) {
            // Source address
            $.each(geolocation_data, function(geolocation_index, geolocation_item) {
                if (flow_item.ip_src == geolocation_item.address) {
                    flow_item.src_continent = geolocation_item.continent;
                    flow_item.src_country = geolocation_item.country;
                    flow_item.src_region = geolocation_item.region;
                    flow_item.src_city = geolocation_item.city;
                    return false;
                }
			
            });
                                        
            // Destination address
            $.each(geolocation_data, function(geolocation_index, geolocation_item) {
                if (flow_item.ip_dst == geolocation_item.address) {
                    flow_item.dst_continent = geolocation_item.continent;
                    flow_item.dst_country = geolocation_item.country;
                    flow_item.dst_region = geolocation_item.region;
                    flow_item.dst_city = geolocation_item.city;
                    return false;
                }

            });


// undefined
	    if (flow_item.dst_country == undefined)
		    flow_item.dst_country = "(UNKNOWN)";

	    if (flow_item.dst_region == undefined)
		    flow_item.dst_region = "(UNKNOWN)";

	    if (flow_item.dst_city == undefined)
		    flow_item.dst_city = "(UNKNOWN)";

	    if (flow_item.src_country == undefined)
		    flow_item.src_country = "(UNKNOWN)";

	    if (flow_item.src_region == undefined)
		    flow_item.src_region = "(UNKNOWN)";

	    if (flow_item.src_city == undefined)
		    flow_item.src_city = "(UNKNOWN)";
// end of undefined

        });
        
        // Check whether geo filter should be applied
        if (session_data['geo_filter'] === "") {
            $(document).trigger('prepare_reverse_geocode');
        } else {
            show_loading_message('Applying geo filter');
            
            /* Creates new 'flow_data' object that contains only geolocation information. Network
             * information has been removed to avoid transmitting superfluous data to server.
             */
            var flow_data_wo_network = [];
            $.each(flow_data, function (flow_index, flow_item) {
                var flow_record = {
                    'src_country':  flow_item.src_country,
                    'src_region':  flow_item.src_region,
                    'src_city':  flow_item.src_city,
                    'dst_country':  flow_item.dst_country,
                    'dst_region':  flow_item.dst_region,
                    'dst_city':  flow_item.dst_city
                };
                flow_data_wo_network.push(flow_record);
            });
            
            $.ajax({
                url: 'json/setgeofilter.php',
                data: {
                    params: {
                        'geo_filter': session_data['geo_filter'],
                        'flow_record_count': session_data['flow_record_count'],
                        'flow_data': flow_data_wo_network
                    }
                },
                success: function(data) {
                    if (data.status == 0) { // Success
                        var removed_records = data.removed_record_indices;
                        
                        $.each(removed_records, function(removal_index, record_index) {
                            // Since records are removed from an array, the positions of all remaining elements are shifted
                            var actual_removal_index = record_index - removal_index;
                            flow_data.splice(actual_removal_index, 1);
                        });
                        
                        $(document).trigger('prepare_reverse_geocode');
                    } else {
                        show_error(814, data.status_message);
                        $(document).trigger('loaded');
                    }
                }
            });
        }
    });
    
    $(document).bind('prepare_reverse_geocode', function () {
        reverse_geocoder_request = [];
        
        $.each(flow_data, function () {
/*
            if (is_extension_active('OTX')) {
                var lat = parseFloat(this.loc_lat_int + "." + this.loc_lat_dec);
                var lng = parseFloat(this.loc_lng_int + "." + this.loc_lng_dec);
                var latlng = new google.maps.LatLng(lat, lng);
                
                // Check whether latlng is already listed for reverse geocoding
                var found_index = -1;
                $.each(reverse_geocoder_request, function (item_index, item) {
                    if (item.equals(latlng)) {
                        found_index = item_index;
                        return false;
                    }
                });
                
                if (found_index == -1) {
                    reverse_geocoder_request.push(latlng);
                }
            }
*/
        });
        
        if (reverse_geocoder_request.length > 0) {
            /* We don't check whether we're allowed to (reverse) geocode (because)
             * of limits imposed by Google), since nothing will be shown if
             * we don't apply reverse geocoding.
             */
            if (geocoder_data_client == undefined) {
                geocoder_data_client = {};
                geocoder_data_client.requests_success = 0;
                geocoder_data_client.requests_blocked = 0;
                geocoder_data_client.requests_error = 0;
                geocoder_data_client.requests_skipped = 0;
            }
            if (geocoder_data_client.reverse_geocoder_data == undefined) {
                geocoder_data_client.reverse_geocoder_data = [];
            }
            
            geocoder_data_client.reverse_request_count = reverse_geocoder_request.length;
            $(document).trigger('reverse_geocode');
        } else {
            $(document).trigger('load_geocoder_data');
        }
    });
    
    $(document).bind('reverse_geocode', function () {
        if (reverse_geocoder_request.length > 0) {
            var item = reverse_geocoder_request.shift();
            setTimeout(function () {
                geocoder.geocode({ 'latLng': item }, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        geocoder_data_client.requests_success++;
                        
                        var result = {};
                        result['request'] = item;
                
                        $.each(results[1].address_components, function () {
                            // Find country information
                            if (jQuery.inArray('country', this.types) != -1) {
                                result['country'] = this.long_name.toUpperCase();
                            }
                    
                            // Find region information
                            if (jQuery.inArray('administrative_area_level_1', this.types) != -1) {
                                result['region'] = this.long_name.toUpperCase();
                            }
                    
                            // Find city information
                            if (jQuery.inArray('administrative_area_level_2', this.types) != -1) {
                                result['city'] = this.long_name.toUpperCase();
                            }
                        });
                        
                        geocoder_data_client.reverse_geocoder_data.push(result);
                    } else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                        geocoder_data_client.requests_blocked++;
                
                        // Add current request another time to geocoder_request_client for a retry
                        reverse_geocoder_request.push(item);
                    } else {
                        geocoder_data_client.requests_error++;
                    }
                    
                    // If ready, start remaining processing
                    if (geocoder_data_client.reverse_request_count == geocoder_data_client.requests_success + geocoder_data_client.requests_error) {
                        var reverse_geocoder_data = [];
                        $.each(geocoder_data_client.reverse_geocoder_data, function (index, item) {
                            reverse_geocoder_data.push(item);
                        });
                
                        $.each(flow_data, function(flow_index, flow_item) {
                            // Reverse geocoded locations
                            $.each(reverse_geocoder_data, function(geocoder_index, geocoder_item) {
/*
                                if (is_extension_active('OTX')) {
                                    var lat = parseFloat(flow_item.loc_lat_int + "." + flow_item.loc_lat_dec);
                                    var lng = parseFloat(flow_item.loc_lng_int + "." + flow_item.loc_lng_dec);
                                    var latlng = new google.maps.LatLng(lat, lng);
            
                                    if (geocoder_item.request.equals(latlng)) {
                                        // Mobile flow exporter is source of flow
                                        if (flow_item.port_src == Math.max(flow_item.port_src, flow_item.port_dst)) {
                                            flow_item.src_country = geocoder_item.country;
                                            flow_item.src_region = geocoder_item.region;
                                            flow_item.src_city = geocoder_item.city;
                                            flow_item.src_host_lat = lat;
                                            flow_item.src_host_lng = lng;
                                        } else { // Mobile flow exporter is destination of flow
                                            flow_item.dst_country = geocoder_item.country;
                                            flow_item.dst_region = geocoder_item.region;
                                            flow_item.dst_city = geocoder_item.city;
                                            flow_item.dst_host_lat = lat;
                                            flow_item.dst_host_lng = lng;
                                        }
                                    }
                                }
*/
                            });
                        });
                
                        $(document).trigger('load_geocoder_data');
                    }
                });
            }, constants['default_geocoder_request_interval']);
        }
    });
    
    $(document).bind('load_geocoder_data', function () {
        if (session_data['use_db']) {
            show_loading_message('Loading geocoder data');
            
            // Collect all location names
            geocoder_request = [];
            $.each(geolocation_data, function(key, value) {
                var continent_str = (value.continent != "(UNKNOWN)") ? ";" + value.continent : "";
                if ($.inArray(value.country + continent_str, geocoder_request) == -1
                        && value.country != "(UNKNOWN)") {
                    geocoder_request.push(value.country + continent_str);
                }
                if ($.inArray(value.country + ";" + value.region + continent_str, geocoder_request) == -1
                        && value.country != "(UNKNOWN)"
                        && value.region != "(UNKNOWN)") {
                    geocoder_request.push(value.country + ";" + value.region + continent_str);
                }
                if ($.inArray(value.country + ";" + value.region + ";" + value.city + continent_str, geocoder_request) == -1
                        && value.country != "(UNKNOWN)"
                        && value.region != "(UNKNOWN)"
                        && value.city != "(UNKNOWN)") {
                    geocoder_request.push(value.country + ";" + value.region + ";" + value.city + continent_str);
                }
                if ($.inArray(value.country + ";" + value.city + continent_str, geocoder_request) == -1
                        && value.country != "(UNKNOWN)"
                        && value.region == "(UNKNOWN)"
                        && value.city != "(UNKNOWN)") {
                    geocoder_request.push(value.country + ";" + value.city + continent_str);
                }
            });
            
            // Skip further processing here if there are no locations to retrieve geocoder data for
            if (geocoder_request.length == 0) {
                $(document).trigger('prepare_geocoding');
                return false;
            }
            
            // Geocoder data
            $.ajax({
                url: 'json/getgeocoderdata.php',
                data: {
                    params: geocoder_request
                },
                success: function(data) {
                    if (data.status == 0) { // Success
                        // Add retrieved geocoder data to flow data
                        $.each(flow_data, function(flow_index, flow_item) {
                            // Source IP address
                            $.each(data.geocoder_data, function(geocoder_index, geocoder_item) {
                                // If item is not found in geocoder database, don't add (invalid) coordinates to flow_data
                                if (geocoder_item.lat == -1 && geocoder_item.lng == -1) {
                                    return true;
                                }
                                
                                // Source
                                var src_continent_str = (flow_item.src_continent != "(UNKNOWN)") ? ";" + flow_item.src_continent : "";
                                if (flow_item.src_country + src_continent_str == geocoder_item.request) {
                                    flow_item.src_country_lat = parseFloat(geocoder_item.lat);
                                    flow_item.src_country_lng = parseFloat(geocoder_item.lng);
                                }
                                if (flow_item.src_country + ";" + flow_item.src_region + src_continent_str == geocoder_item.request) {
                                    flow_item.src_region_lat = parseFloat(geocoder_item.lat);
                                    flow_item.src_region_lng = parseFloat(geocoder_item.lng);
                                }
                                if (flow_item.src_country + ";" + flow_item.src_region + ";" + flow_item.src_city + src_continent_str == geocoder_item.request) {
                                    flow_item.src_city_lat = parseFloat(geocoder_item.lat);
                                    flow_item.src_city_lng = parseFloat(geocoder_item.lng);
                                }
                                if (flow_item.src_country + ";" + flow_item.src_city + src_continent_str == geocoder_item.request) {
                                    flow_item.src_region_lat = parseFloat(geocoder_item.lat);
                                    flow_item.src_region_lng = parseFloat(geocoder_item.lng);
                                    flow_item.src_city_lat = parseFloat(geocoder_item.lat);
                                    flow_item.src_city_lng = parseFloat(geocoder_item.lng);
                                }
                                
                                // Destination
                                var dst_continent_str = (flow_item.dst_continent != "(UNKNOWN)") ? ";" + flow_item.dst_continent : "";
                                if (flow_item.dst_country + dst_continent_str == geocoder_item.request) {
                                    flow_item.dst_country_lat = parseFloat(geocoder_item.lat);
                                    flow_item.dst_country_lng = parseFloat(geocoder_item.lng);
                                }
                                if (flow_item.dst_country + ";" + flow_item.dst_region + dst_continent_str == geocoder_item.request) {
                                    flow_item.dst_region_lat = parseFloat(geocoder_item.lat);
                                    flow_item.dst_region_lng = parseFloat(geocoder_item.lng);
                                }
                                if (flow_item.dst_country + ";" + flow_item.dst_region + ";" + flow_item.dst_city + dst_continent_str == geocoder_item.request) {
                                    flow_item.dst_city_lat = parseFloat(geocoder_item.lat);
                                    flow_item.dst_city_lng = parseFloat(geocoder_item.lng);
                                }
                                if (flow_item.dst_country + ";" + flow_item.dst_city + dst_continent_str == geocoder_item.request) {
                                    flow_item.dst_region_lat = parseFloat(geocoder_item.lat);
                                    flow_item.dst_region_lng = parseFloat(geocoder_item.lng);
                                    flow_item.dst_city_lat = parseFloat(geocoder_item.lat);
                                    flow_item.dst_city_lng = parseFloat(geocoder_item.lng);
                                }
                            });
                        });
                        $(document).trigger('geocoder_data_loaded');
                    } else {
                        show_error(806, data.status_message);
                        $(document).trigger('loaded');
                    }
                }
            });
        } else {
            $(document).trigger('prepare_geocoding');
        }
    });
    
    $(document).bind('geocoder_data_loaded', function () {
        $(document).trigger('prepare_geocoding');
    });
    
    $(document).bind('prepare_geocoding', function () {
        show_loading_message('Geocoding');
        
        geocoder_data_server = undefined;
        geocoder_data_client = undefined;
                
        // Collect all place names that have to be geocoded
        var geocoder_request = [];

        $.each(flow_data, function () {
            /* Coordinates are undefined or '-1' if not found in geolocation database */
            
            /* Source */
            var src_continent_str = (this.src_continent != "(UNKNOWN)") ? ";" + this.src_continent : "";
            if ((this.src_country_lat == -1 && this.src_country_lng == -1
                    || this.src_country_lat == undefined && this.src_country_lng == undefined)
                    && this.src_country != "(UNKNOWN)"
                    && $.inArray(this.src_country + src_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.src_country + src_continent_str);
            }
            if ((this.src_region_lat == -1 && this.src_region_lng == -1
                    || this.src_region_lat == undefined && this.src_region_lng == undefined)
                    && this.src_country != "(UNKNOWN)"
                    && this.src_region != "(UNKNOWN)"
                    && $.inArray(this.src_country + ";" + this.src_region + src_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.src_country + ";" + this.src_region + src_continent_str);
            }
            if ((this.src_city_lat == -1 && this.src_city_lng == -1
                    || this.src_city_lat == undefined && this.src_city_lng == undefined)
                    && this.src_country != "(UNKNOWN)"
                    && this.src_region != "(UNKNOWN)"
                    && this.src_city != "(UNKNOWN)"
                    && $.inArray(this.src_country + ";" + this.src_region + ";" + this.src_city + src_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.src_country + ";" + this.src_region + ";" + this.src_city + src_continent_str);
            }
            if ((this.src_city_lat == -1 && this.src_city_lng == -1
                    || this.src_city_lat == undefined && this.src_city_lng == undefined)
                    && this.src_country != "(UNKNOWN)"
                    && this.src_region == "(UNKNOWN)"
                    && this.src_city != "(UNKNOWN)"
                    && $.inArray(this.src_country + ";" + this.src_city + src_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.src_country + ";" + this.src_city + src_continent_str);
            }
                    
            /* Destination */
            var dst_continent_str = (this.dst_continent) ? ";" + this.dst_continent : "";
            if ((this.dst_country_lat == -1 && this.dst_country_lng == -1
                    || this.dst_country_lat == undefined && this.dst_country_lng == undefined)
                    && this.dst_country != "(UNKNOWN)"
                    && $.inArray(this.dst_country + dst_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.dst_country + dst_continent_str);
            }
            if ((this.dst_region_lat == -1 && this.dst_region_lng == -1
                    || this.dst_region_lat == undefined && this.dst_region_lng == undefined)
                    && this.dst_country != "(UNKNOWN)"
                    && this.dst_region != "(UNKNOWN)"
                    && $.inArray(this.dst_country + ";" + this.dst_region + dst_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.dst_country + ";" + this.dst_region + dst_continent_str);
            }
            if ((this.dst_city_lat == -1 && this.dst_city_lng == -1
                    || this.dst_city_lat == undefined && this.dst_city_lng == undefined)
                    && this.dst_country != "(UNKNOWN)"
                    && this.dst_region != "(UNKNOWN)"
                    && this.dst_city != "(UNKNOWN)"
                    && $.inArray(this.dst_country + ";" + this.dst_region + ";" + this.dst_city + dst_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.dst_country + ";" + this.dst_region + ";" + this.dst_city + dst_continent_str);
            }
            if ((this.dst_city_lat == -1 && this.dst_city_lng == -1
                    || this.dst_city_lat == undefined && this.dst_city_lng == undefined)
                    && this.dst_country != "(UNKNOWN)"
                    && this.dst_region == "(UNKNOWN)"
                    && this.dst_city != "(UNKNOWN)"
                    && $.inArray(this.dst_country + ";" + this.dst_city + dst_continent_str, geocoder_request) == -1) {
                geocoder_request.push(this.dst_country + ";" + this.dst_city + dst_continent_str);
            }

        });
        
        // If CURL is supported, balance geocoding process between client and server
        geocoder_request_client = [];
        var allowed_requests_client = 2400;
        var allowed_requests_server = 2400;
        if (session_data['use_db']) {
            allowed_requests_client -=
                    - session_data['geocoder_history']['client'].requests_success
                    - session_data['geocoder_history']['client'].requests_blocked
                    - session_data['geocoder_history']['client'].requests_error
                    - session_data['geocoder_history']['client'].requests_skipped;
        }
        
        if (session_data['curl_loaded']) {
            if (session_data['use_db']) {
                allowed_requests_server -=
                        - session_data['geocoder_history']['server'].requests_success
                        - session_data['geocoder_history']['server'].requests_blocked
                        - session_data['geocoder_history']['server'].requests_error
                        - session_data['geocoder_history']['server'].requests_skipped;
            }
            
            if (Math.ceil(geocoder_request.length / 2) > allowed_requests_client) { // More requests than allowed for client
                geocoder_request_client = geocoder_request.slice(0, allowed_requests_client);
            } else {
                geocoder_request_client = geocoder_request.slice(0, Math.ceil(geocoder_request.length / 2));
            }
            
            if (geocoder_request.length - geocoder_request_client.length > allowed_requests_server) { // More requests than allowed for server
                geocoder_request_server = geocoder_request.slice(geocoder_request_client.length, geocoder_request_client.length + allowed_requests_server);
            } else if (geocoder_request.length == 1 && geocoder_request_client.length == 1) { // Only one place to geocode and it is done already by client
                geocoder_request_server = [];
            } else {
                geocoder_request_server = geocoder_request.slice(geocoder_request.length - geocoder_request_client.length); // select all remaining elements
            }
            
            // Server
            if (geocoder_data_server == undefined) {
                geocoder_data_server = {};
                geocoder_data_server.requests_success = 0;
                geocoder_data_server.requests_blocked = 0;
                geocoder_data_server.requests_error = 0;
                geocoder_data_server.requests_skipped = 0;
            }
            if (geocoder_data_server.geocoder_data == undefined) {
                geocoder_data_server.geocoder_data = [];
            }
            
            geocoder_data_server.request_count = geocoder_request_server.length; // Number of requests to make (required to check whether all requests have been completed)
           
            if (geocoder_request_server.length == 0) {
                var data = {};
                data.geocoder_data = [];
                data.requests_success = 0;
                data.requests_blocked = 0;
                data.requests_error = 0;
                data.requests_skipped = 0;
                $(document).trigger('geocoding_server_done', data);
            } else {
                $.ajax({
                    url: 'json/getgeocode.php',
                    data: {
                        params: geocoder_request_server
                    },
                    success: function(data) {
                        if (data.status == 0) { // Success
                            $(document).trigger('geocoding_server_done', data);
                        } else {
                            show_error(808, data.status_message);
                            $(document).trigger('loaded');
                        }
                    }
                });
            }
        } else {
            if (geocoder_request.length > allowed_requests_client) {
                geocoder_request.slice(0, allowed_requests_client);
            } else {
                geocoder_request_client = geocoder_request;
            }
        }
        
        // Client
        if (geocoder_data_client == undefined) {
            geocoder_data_client = {};
            geocoder_data_client.requests_success = 0;
            geocoder_data_client.requests_blocked = 0;
            geocoder_data_client.requests_error = 0;
            geocoder_data_client.requests_skipped = 0;
        }
        if (geocoder_data_client.geocoder_data == undefined) {
            geocoder_data_client.geocoder_data = [];
        }
        
        geocoder_data_client.request_count = geocoder_request_client.length; // Number of requests to make (required to check whether all requests have been
        if (geocoder_request_client.length == 0) {
            $(document).trigger('geocoding_client_done');
        } else {
            $(document).trigger('geocode');
        }
    });
    
    $(document).bind('geocode', function () {
        if (geocoder_request_client.length > 0) {
            var item = geocoder_request_client.shift();
            setTimeout(function () {
                geocoder.geocode({ 'address': item }, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        geocoder_data_client.requests_success++;
                        
                        var result = {};
                        result['request'] = item;
                        result['lat'] = results[0].geometry.location.lat();
                        result['lng'] = results[0].geometry.location.lng();
                        
                        geocoder_data_client.geocoder_data.push(result);
                    } else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                        geocoder_data_client.requests_blocked++;
                        
                        // Add current request another time to geocoder_request_client for a retry
                        geocoder_request_client.push(item);
                    } else {
                        geocoder_data_client.requests_error++;
                    }
                    
                    // If ready, start remaining processing
                    if (geocoder_data_client.request_count == geocoder_data_client.requests_success + geocoder_data_client.requests_error) {
                        $(document).trigger('geocoding_client_done');
                    } else { // Else, continue geocoding
                        $(document).trigger('geocode');
                    }
                });
            }, constants['default_geocoder_request_interval']);
        }
    });
    
    $(document).bind('geocoding_server_done', function (event, data) {
        geocoder_data_server.geocoder_data = data.geocoder_data;
        geocoder_data_server.requests_success = data.requests_success;
        geocoder_data_server.requests_blocked = data.requests_blocked;
        geocoder_data_server.requests_error = data.requests_error;
        geocoder_data_server.requests_skipped = data.requests_skipped;
        
        if (geocoder_data_client != undefined && geocoder_data_client.request_count == geocoder_data_client.requests_success + geocoder_data_client.requests_error) {
            $(document).trigger('geocoding_done');
        }
    });
    
    $(document).bind('geocoding_client_done', function (event) {
        if (session_data['curl_loaded'] && geocoder_data_server.request_count == geocoder_data_server.requests_success + geocoder_data_server.requests_error) {
            $(document).trigger('geocoding_done');
        }
    });
    
    $(document).bind('geocoding_done', function () {
        show_loading_message('Processing geocoder data');
        
        // Merge successful client and server geocoder data
        var geocoder_data = [];
       
        if (geocoder_data_client.geocoder_data != undefined) {
            $.each(geocoder_data_client.geocoder_data, function (index, item) {
                if (item.lat != undefined && item.lng != undefined && item.lat !== null && item.lng !== null) {
                    geocoder_data.push(item);
                }
            });
        }
        if (geocoder_data_server.geocoder_data != undefined) {
            $.each(geocoder_data_server.geocoder_data, function (index, item) {
                if (item.lat != undefined && item.lng != undefined && item.lat !== null && item.lng !== null) {
                    geocoder_data.push(item);
                }
            });
        }
        
        // Add retrieved geocoder data to flow data
        $.each(flow_data, function(flow_index, flow_item) {        
            // Source IP address
            var src_continent_str = (flow_item.src_continent != "(UNKNOWN)") ? ";" + flow_item.src_continent : "";
            $.each(geocoder_data, function(geocoder_index, geocoder_item) {
                if (flow_item.src_country + src_continent_str == geocoder_item.request) {
                    flow_item.src_country_lat = geocoder_item.lat;
                    flow_item.src_country_lng = geocoder_item.lng;
                }
                if (flow_item.src_country + ";" + flow_item.src_region + src_continent_str == geocoder_item.request) {
                    flow_item.src_region_lat = geocoder_item.lat;
                    flow_item.src_region_lng = geocoder_item.lng;
                }
                if (flow_item.src_country + ";" + flow_item.src_region + ";" + flow_item.src_city + src_continent_str == geocoder_item.request) {
                    flow_item.src_city_lat = geocoder_item.lat;
                    flow_item.src_city_lng = geocoder_item.lng;
                }
                if (flow_item.src_country + ";" + flow_item.src_city + src_continent_str == geocoder_item.request) {
                    flow_item.src_region_lat = geocoder_item.lat;
                    flow_item.src_region_lng = geocoder_item.lng;
                    flow_item.src_city_lat = geocoder_item.lat;
                    flow_item.src_city_lng = geocoder_item.lng;
                }
            });
            
            // Destination IP address
            var dst_continent_str = (flow_item.dst_continent != "(UNKNOWN)") ? ";" + flow_item.dst_continent : "";
            $.each(geocoder_data, function(geocoder_index, geocoder_item) {
                if (flow_item.dst_country + dst_continent_str == geocoder_item.request) {
                    flow_item.dst_country_lat = geocoder_item.lat;
                    flow_item.dst_country_lng = geocoder_item.lng;
                }
                if (flow_item.dst_country + ";" + flow_item.dst_region + dst_continent_str == geocoder_item.request) {
                    flow_item.dst_region_lat = geocoder_item.lat;
                    flow_item.dst_region_lng = geocoder_item.lng;
                }
                if (flow_item.dst_country + ";" + flow_item.dst_region + ";" + flow_item.dst_city + dst_continent_str == geocoder_item.request) {
                    flow_item.dst_city_lat = geocoder_item.lat;
                    flow_item.dst_city_lng = geocoder_item.lng;
                }
                if (flow_item.dst_country + ";" + flow_item.dst_city + dst_continent_str == geocoder_item.request) {
                    flow_item.dst_region_lat = geocoder_item.lat;
                    flow_item.dst_region_lng = geocoder_item.lng;
                    flow_item.dst_city_lat = geocoder_item.lat;
                    flow_item.dst_city_lng = geocoder_item.lng;
                }
            });
        });
       
        if (session_data['use_db']) {
            show_loading_message('Storing geocoder data');
                
            var geocoder_history = {};
            geocoder_history['client'] = {};
            geocoder_history['client'].requests_success = geocoder_data_client.requests_success
                    + parseInt(session_data['geocoder_history']['client'].requests_success, 10);
            geocoder_history['client'].requests_blocked = geocoder_data_client.requests_blocked
                    + parseInt(session_data['geocoder_history']['client'].requests_blocked, 10);
            geocoder_history['client'].requests_error = geocoder_data_client.requests_error
                    + parseInt(session_data['geocoder_history']['client'].requests_error, 10);
            geocoder_history['client'].requests_skipped = geocoder_data_client.requests_skipped
                    + parseInt(session_data['geocoder_history']['client'].requests_skipped, 10);
                
            // Only send geocoder history for server-based geocoding if it has been used
            if (geocoder_data_server != undefined) {
                geocoder_history['server'] = {};
                geocoder_history['server'].requests_success = geocoder_data_server.requests_success
                        + parseInt(session_data['geocoder_history']['server'].requests_success, 10);
                geocoder_history['server'].requests_blocked = geocoder_data_server.requests_blocked
                        + parseInt(session_data['geocoder_history']['server'].requests_blocked, 10);
                geocoder_history['server'].requests_error = geocoder_data_server.requests_error
                        + parseInt(session_data['geocoder_history']['server'].requests_error, 10);
                geocoder_history['server'].requests_skipped = geocoder_data_server.requests_skipped
                        + parseInt(session_data['geocoder_history']['server'].requests_skipped, 10);
            }
                
            $(document).trigger('session_data_changed', { 'geocoder_history': geocoder_history } );
                    
            if (geocoder_data.length !== 0) {
                $.ajax({
                    url: 'json/setgeocoderdata.php',
                    data: {
                        params: geocoder_data
                    },
                    success: function(data) {
                        if (!data.status == 0) { // Error
                            show_error(809, data.status_message);
                            $(document).trigger('loaded');
                        }
                    }
                });
            }
        }
	$(document).trigger('otx_loaded');
    });

    $(document).bind('otx_loaded', function () {
        $.ajax({
            url: 'json/getotxdata.php',
            success: function(data) {
                if (data.status == 0) { // Success
                    $(document).trigger('process_map_elements', data);
                } else {
                    show_error(811, data.status_message);
                }
            }
        });
    });
 
    $(document).bind('process_map_elements', function (event, data) {
	cnc_lists = data.cnc_lists;

        complement_location_information();

        remove_map_overlays();

        init_lines();

        init_markers();

        add_map_overlays(get_SM_zoom_level(map.getZoom()));

        init_legend();

        $(document).trigger('loaded');
    });
    
    $(document).bind('loaded', function () {
        if ($('input[type=submit]').prop('disabled') != undefined) {
            $('input[type=submit]').removeAttr('disabled');
        }
        if ($('#loading_dialog').dialog('isOpen')) {
            $('#loading_dialog').dialog('close');
            clearInterval(loading_message_timeout_handle);
        }
        if (config['show_warnings']) {
            if ($.browser.msie && parseInt($.browser.version, 10) < 10) { // Old IE is used (< v10)
                var cookie_value = get_cookie_value('MFLOW', 'msie_old');
                if (cookie_value == undefined || cookie_value == 0) {
                    show_warning(1);
                    update_cookie_value('MFLOW', 'msie_old', 1);
                }
            }
            
            /* Only show the warnings on no data if no error message is currently show (as)
             * the error message is likely to be related to the fact that no data could be
             * shown.
             */
            if (!$('#error_dialog').dialog('isOpen') && (flow_data == undefined || flow_data.length == 0)) { // No flow records left after filtering
                if (session_data['flow_display_filter'] === "" && session_data['geo_filter'] === "") {
                    show_warning(2);
                } else {
                    show_warning(3);
                }
            }
        }
    });
});
