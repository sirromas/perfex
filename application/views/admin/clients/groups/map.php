<?php
if (isset($client)) {
    if ($google_api_key !== '') {
        if ($client->longitude == '' && $client->latitude == '') {
            echo _l('customer_map_notice');
        } else {
            echo '<div id="map" class="customer_map"></div>';
        }
    } else {
        echo _l('setup_google_api_key_customer_map');
    }
}
