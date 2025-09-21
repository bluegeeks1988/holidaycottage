<?php

/**
 * Class SykesAPI
 * 
 * A class for interacting with the Sykes API.
 */
class SykesAPI {

    /**
     * The URL of the Sykes API.
     * @var string
     */
    private $apiUrl;

    /**
     * The API key.
     * @var string
     */
    private $apiKey;

    /**
     * The API password.
     * @var string
     */
    private $apiPassword;   

    /**
     * SykesAPI constructor.
     *
     * @param string $apiUrl The URL of the Sykes API.
     * @param string $apiKey The API key.
     * @param string $apiPassword The API password.
     */
    public function __construct($apiUrl, $apiKey, $apiPassword) {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->apiPassword = $apiPassword;
    }

    /**
     * Connect to the Sykes API.
     *
     * @return bool Whether the connection is successful or not.
     */
    public function connect($method = '') {

    	$ch = curl_init();
    	$headers = array(
    		'Accept: application/json',
    		'Content-Type: application/json',
    		'key: ' . $this->apiKey,
    		'password: ' . $this->apiPassword
    	);
    	
    	curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $method);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); 
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $exc = curl_exec($ch);
    	
    	return json_decode($exc);
    }

    /**
     * Checks the status of the connection to the Sykes API.
     *
     * This method checks if the provided connection object's status code is 200,
     * indicating a successful connection to the Sykes API.
     *
     * @param object $connection The connection object to the Sykes API.
     * @return void
     */
    public function checkConnection($connection) {
        if ($connection->code === 200) {
            echo 'Connection to the Sykes API is successful.';
        } else {
            echo 'Failed to connect to the Sykes API.';
        }
    }

    /**
     * Insert listing IDs from the Sykes API.
     *
     * @return array Response from the API containing listing IDs.
     */
    public function insertListingsIDs($data) {
        global $wpdb;

        foreach ($data->payload as $key => $value) {
        
            if(!$value->property_id) {
                return false;
            }

            // Check if the post with the given ID already exists
            $post_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE ID = %d",
                    $value->property_id
                )
            );

            // Insert the post only if it doesn't already exist
            if (!$post_exists) {
                $wpdb->insert(
                    $wpdb->posts,
                    array(
                        'ID' => $value->property_id,
                        'post_title'    => 'Property: ' . $value->property_id,
                        'post_name' => $value->property_id, // This sets the slug based on the property ID
                        'post_type'     => 'properties',
                        'post_status' => 'draft'
                    )
                );
            }
        }
    }
    
/**
 * Clean up properties that no longer exist in the Sykes API.
 * This method moves properties to trash if they're not found in the current API response.
 *
 * @param object $apiData The current data from the Sykes API containing active properties.
 * @param int|null $test_property_id Optional: Test on a specific property ID only.
 * @param bool $force_when_no_api_data Optional: Force cleanup even when no API data found (use carefully).
 * @return array Summary of cleanup operations performed.
 */
public function cleanupInactiveProperties($apiData, $test_property_id = null, $force_when_no_api_data = false) {
    global $wpdb;
    
    // Initialize counters for reporting
    $trashed_count = 0;
    $errors = [];
    $active_property_ids = [];
    
    // Extract active property IDs from API data
    if (isset($apiData->payload) && is_array($apiData->payload)) {
        foreach ($apiData->payload as $property) {
            if (isset($property->property_id)) {
                $active_property_ids[] = intval($property->property_id);
            }
        }
    }
    
    // If no active properties found in API, check if we should proceed
    if (empty($active_property_ids)) {
        // In test mode with a specific property ID, we can proceed even with no API data
        // This allows testing properties we know are no longer in the API
        if ($test_property_id !== null && $force_when_no_api_data) {
            // Continue with empty active_property_ids array for testing
            $active_property_ids = [];
        } else {
            return [
                'success' => false,
                'message' => 'No active properties found in API response. Cleanup aborted to prevent accidental data loss. Use force_when_no_api_data=true if intentional.',
                'trashed_count' => 0,
                'test_mode' => $test_property_id !== null,
                'errors' => ['No valid property data in API response']
            ];
        }
    }
    
    // Get properties to check - either specific test property or all existing properties
    if ($test_property_id !== null) {
        // Test mode: check only the specified property
        $existing_properties = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts} 
                 WHERE post_type = 'properties' 
                 AND post_status IN ('publish', 'draft')
                 AND ID = %d",
                $test_property_id
            )
        );
        
        if (empty($existing_properties)) {
            return [
                'success' => false,
                'message' => "Test property ID {$test_property_id} not found in WordPress or already trashed.",
                'trashed_count' => 0,
                'test_mode' => true,
                'errors' => ["Property ID {$test_property_id} not found"]
            ];
        }
    } else {
        // Normal mode: get all published/draft properties from WordPress
        $existing_properties = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'properties' 
             AND post_status IN ('publish', 'draft')"
        );
    }
    
    // Check each existing property against the active API list
    foreach ($existing_properties as $property) {
        $property_id = intval($property->ID);
        
        // If property ID is not in the active API list, move to trash
        if (!in_array($property_id, $active_property_ids)) {
            $result = wp_trash_post($property_id);
            
            if ($result) {
                $trashed_count++;
                
                // Enhanced logging for test mode
                $log_message = $test_property_id ? 
                    "Sykes API Cleanup [TEST MODE]: Moved property ID {$property_id} ('{$property->post_title}') to trash - not found in API" :
                    "Sykes API Cleanup: Moved property ID {$property_id} ('{$property->post_title}') to trash - not found in API";
                
                error_log($log_message);
            } else {
                $errors[] = "Failed to trash property ID: {$property_id}";
            }
        }
    }
    
    return [
        'success' => true,
        'message' => $test_property_id ? "Test completed on property ID {$test_property_id}. {$trashed_count} property moved to trash." : "Cleanup completed. {$trashed_count} properties moved to trash.",
        'trashed_count' => $trashed_count,
        'active_properties_count' => count($active_property_ids),
        'test_mode' => $test_property_id !== null,
        'tested_property_id' => $test_property_id,
        'errors' => $errors
    ];
}

/**
 * Restore properties from trash (utility method for recovery).
 * Use this if you need to restore accidentally trashed properties.
 *
 * @param array $property_ids Array of property IDs to restore.
 * @return array Summary of restore operations.
 */
public function restoreProperties($property_ids = []) {
    $restored_count = 0;
    $errors = [];
    
    // If no specific IDs provided, get all trashed properties
    if (empty($property_ids)) {
        global $wpdb;
        $trashed_properties = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'properties' 
             AND post_status = 'trash'"
        );
        $property_ids = array_map('intval', $trashed_properties);
    }
    
    foreach ($property_ids as $property_id) {
        $result = wp_untrash_post($property_id);
        
        if ($result) {
            $restored_count++;
        } else {
            $errors[] = "Failed to restore property ID: {$property_id}";
        }
    }
    
    return [
        'success' => true,
        'message' => "Restore completed. {$restored_count} properties restored.",
        'restored_count' => $restored_count,
        'errors' => $errors
    ];
}

/**
 * Get cleanup statistics without performing any actions.
 * Use this to preview what would be cleaned up.
 *
 * @param object $apiData The current data from the Sykes API.
 * @param int|null $test_property_id Optional: Preview for a specific property ID only.
 * @return array Statistics about properties that would be affected.
 */
public function getCleanupPreview($apiData, $test_property_id = null) {
    global $wpdb;
    
    $active_property_ids = [];
    
    // Extract active property IDs from API data
    if (isset($apiData->payload) && is_array($apiData->payload)) {
        foreach ($apiData->payload as $property) {
            if (isset($property->property_id)) {
                $active_property_ids[] = intval($property->property_id);
            }
        }
    }
    
    // Get properties to check - either specific test property or all existing properties
    if ($test_property_id !== null) {
        // Test mode: check only the specified property
        $existing_properties = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts} 
                 WHERE post_type = 'properties' 
                 AND post_status IN ('publish', 'draft')
                 AND ID = %d",
                $test_property_id
            )
        );
    } else {
        // Normal mode: get all published/draft properties
        $existing_properties = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'properties' 
             AND post_status IN ('publish', 'draft')"
        );
    }
    
    $properties_to_trash = [];
    
    foreach ($existing_properties as $property) {
        $property_id = intval($property->ID);
        
        if (!in_array($property_id, $active_property_ids)) {
            $properties_to_trash[] = [
                'id' => $property_id,
                'title' => $property->post_title
            ];
        }
    }
    
    return [
        'total_existing_properties' => count($existing_properties),
        'active_in_api' => count($active_property_ids),
        'properties_to_trash' => $properties_to_trash,
        'trash_count' => count($properties_to_trash),
        'test_mode' => $test_property_id !== null,
        'tested_property_id' => $test_property_id
    ];
}

    /**
     * Retrieve a list of all fields.
     *
     * This method returns an associative array mapping ACF field names to their corresponding data keys.
     * It serves as a reference for the fields used in the property details.
     *
     * @return array An associative array containing ACF field names as keys and their corresponding data keys as values.
     */
    public function fields() {
        return array(
            // General
            'sykes_property_id' => 'property_id',
            'sykes_property_name' => 'name',
            'sykes_accommodation_details' => 'amenities_description',
            'sykes_long_description' => 'long_description',
            'sykes_short_description' => 'short_description',
            'sykes_amenities_description' => 'amenities_description',
            'sykes_property_rating' => 'sykes_rating',
            'sykes_property_trip_advisor_rating' => 'trip_advisor_rating',
            'sykes_property_tourist_board_rating' => 'tourist_board_rating',
            'sykes_property_link' => 'url',

            // Location
            'sykes_property_country' => 'property_country',
            'sykes_property_area' => 'area_name',
            'sykes_property_sub_area' => 'subarea_name',
            'sykes_property_post_code' => 'post_code',
            'sykes_property_longitude' => 'longitude',
            'sykes_property_latitude' => 'latitude',
            'sykes_property_region' => 'region_name',
            'sykes_brochure_location' => 'brochure_location',
            'sykes_property_town' => 'property_town',
            'sykes_property_address_line_1' => 'property_address_line_1',
            'sykes_property_address_line_2' => 'property_address_line_2',
            'sykes_property_address_line_3' => 'property_address_line_3',

            // Property Information
            'sykes_property_type' => 'propertyType->property_type',
            'sykes_capacity' => 'sleeps',
            'sykes_property_bedrooms' => 'bedrooms',
            'sykes_property_bathrooms' => 'bathrooms',
            'sykes_property_single_bed' => 'double_bedroom',
            'sykes_property_double_bed' => 'single_bedroom',
        );
    }

    /**
     * Retrieves an array of taxonomy fields used for filtering property features.
     *
     * @return array An associative array where keys represent property feature identifiers
     *               and values represent the corresponding feature properties.
     */
    public function taxonomyFields() {
        return array(
            'wifi' => 'wifi',
            'dishwasher' => 'propertyFeatures->ndishwasher',
            'garden' => 'propertyFeatures->hasgarden',
            'washing_machine' => 'propertyFeatures->nwashingmachine',
            'fishing' => 'propertyFeatures->hasfishing',
            'by_the_sea' => 'propertyFeatures->seaview',
            'near_a_pub' => 'propertyFeatures->nnearpubshop',
            'on_a_farm' => 'propertyFeatures->isfarm',
            'swimming_pool' => 'propertyFeatures->haspool',
            'smoking_permitted' => 'propertyFeatures->smoking',
            'allows_pets' => 'propertyFeatures->allowspets',
            'off_road_parking' => 'propertyFeatures->noffroadparking',
            'jacuzzi' => 'propertyFeatures->nspajacuzzi',
            'telephone' => 'propertyFeatures->ntelephone',
            'sauna' => 'propertyFeatures->hassauna',
            'great_view' => 'propertyFeatures->greatview',
            'tv' => 'propertyFeatures->nskytv',
            'coastal' => 'propertyFeatures->ncoastal',
            'hot_tub' => 'propertyFeatures->hashottub',
            'fire_log' => 'propertyFeatures->hasopenfire',
            'near_lake' => 'propertyFeatures->nnearlake',
            'luxury' => 'propertyFeatures->nluxury',
            'near_beach' => 'propertyFeatures->beach',
            'countryside' => 'propertyFeatures->countryside'
        );
    }

    /**
     * Save property details into the database.
     *
     * @param array $data Associative array of property details.
     * @return bool True if successful, false otherwise.
     */
    public function mapPropertyDetails($data) {

        // Check if property ID exists
        if (!$data->payload->property_id) {
            return 'No property ID found; unable to update property information.';
        }
    
        $data = $data->payload;
        $id = $data->property_id;
    
        // Array to map ACF field names to data keys
        $fields = $this->fields();
    
        // Iterate over the field map and update ACF fields
        foreach ($fields as $field => $key) {
            // If the field is nested under "features"
            if (is_array($key)) {
                foreach ($key as $nested_field => $nested_key) {
                    // Extract nested value
                    $field_value = $this->getNestedValue($data, $nested_key);
                    // Update ACF field with the extracted value
                    update_field($nested_field, $field_value, $id);
                }
            } else {
                // Extract nested value
                $field_value = $this->getNestedValue($data, $key);
                // Update ACF field with the extracted value
                update_field($field, $field_value, $id);
            }
        }
    
        return true;
    }

    /**
     * Save image URLs to the sykes_property_gallery ACF field.
     *
     * @param array $propertyImages An array containing property images data.
     * @param int $post_id The ID of the post to which the images will be assigned.
     */
    public function savePropertyImagesToGallery($data) {
        $data = $data->payload;
        $id = $data->property_id;
    
        // Initialize arrays to store image URLs
        $image_urls = array();
        $image_urls_resized = array();
    
        // Loop through each property image data
        foreach ($data->propertyImages as $image) {            
            // Concatenate image URLs with newline characters and store in arrays
            $image_urls[] = $image->image_url . "\n";
            $image_urls_resized[] = $image->image_resized_url . "\n";
        }
        
        // Concatenate all image URLs together
        $all_image_urls = implode('', $image_urls);
        $all_image_urls_resized = implode('', $image_urls_resized);
    
        // Save all image URLs in their respective fields
        update_field('sykes_property_images_large', $all_image_urls, $id);
        update_field('sykes_property_images_resized', $all_image_urls_resized, $id);
    }




    /**
     * Get nested value from data object.
     *
     * @param object $data The data object.
     * @param string $key The nested key.
     * @return mixed|null The value if found, null otherwise.
     */
    private function getNestedValue($data, $key) {
        // Extract nested keys
        $nested_keys = explode('->', $key);
        $value = $data;
        // Traverse nested keys to access the value
        foreach ($nested_keys as $nested_key) {
            if (isset($value->{$nested_key})) {
                $value = $value->{$nested_key};
            } else {
                // If any nested key is missing, return null
                return null;
            }
        }
        return $value;
    }

    /**
     * Save property availability data.
     *
     * @param object $data     The data containing availability.
     * @param int    $post_id  The ID of the post.
     */
    public function savePropertyAvailability($data, $post_id) {
        $payload = $data->payload;

        // Extract availability data
        $availability_data = $payload->availability;

        // Find the start date
        $start_date = null;
        foreach ($availability_data as $date => $available) {
            if (!$available) {
                $start_date = $date;
                break;
            }
        }

        // If start date is found, loop through availability until one year from start date
        if ($start_date) {
            $current_date = $start_date;
            $one_year_later = date('Y-m-d', strtotime('+1 year', strtotime($start_date)));
            $availability_entries = '';

            while ($current_date <= $one_year_later && isset($availability_data->$current_date)) {
                $availability_status = $availability_data->$current_date ? 'Available' : 'Not Available';
                $availability_entry = $current_date . ' - ' . $availability_status;
                $availability_entries .= $availability_entry . PHP_EOL;
                $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
            }

            update_field('available', $availability_entries, $post_id);
        }
    }

    /**
     * Save property prices and output lowest and highest prices with their total prices.
     *
     * @param object $data The data containing property prices.
     * @param int $post_id The ID of the post where ACF fields will be updated.
     */
    public function savePropertyPrices($data, $post_id) {
        $data = $data->payload;

        // Initialize arrays to store all the information
        $property_data = [];

        // Get today's date and one year ahead
        $today = new DateTime();
        $one_year_ahead = clone $today;
        $one_year_ahead->modify('+1 year');

        // Initialize variables to store lowest and highest prices and their associated entries
        $lowest_price = PHP_FLOAT_MAX; // Initialize to maximum float value
        $highest_price = 0; // Initialize to 0
        $lowest_price_entry = null;
        $highest_price_entry = null;

        // Iterate through each entry in the payload
        foreach ($data as $entry) {
            // Extract date information
            $date = new DateTime($entry->period_start);

            // Check if the date is within the desired range
            if ($date >= $today && $date <= $one_year_ahead) {
                // Extract price information
                $price = floatval($entry->price);

                // Update lowest price entry if necessary
                if ($price < $lowest_price || $lowest_price_entry === null) {
                    $lowest_price = $price;
                    $lowest_price_entry = [
                        'price' => $price,
                        'total_price' => floatval($entry->total_price),
                        'booking_fee' => floatval($entry->booking_fee),
                        'date' => $date
                    ];
                }

                // Update highest price entry if necessary
                if ($price > $highest_price) {
                    $highest_price = $price;
                    $highest_price_entry = [
                        'price' => $price,
                        'total_price' => floatval($entry->total_price),
                        'booking_fee' => floatval($entry->booking_fee),
                        'date' => $date
                    ];
                }

                // Store all information for the current entry
                $property_data[] = [
                    'price' => $price,
                    'total_price' => floatval($entry->total_price),
                    'booking_fee' => floatval($entry->booking_fee),
                    'date' => $date
                ];
            }
        }
        

        // Update ACF fields with lowest and highest prices if data is available
        if ($lowest_price_entry !== null && $highest_price_entry !== null) {
            // Update lowest price fields
            update_field('sykes_property_lowest_starting_date', $lowest_price_entry['date']->format('Y-m-d'), $post_id);
            update_field('sykes_property_lowest_price', $lowest_price, $post_id);
            update_field('sykes_property_lowest_total', $lowest_price_entry['total_price'], $post_id);
            update_field('sykes_property_lowest_booking_fee', $lowest_price_entry['booking_fee'], $post_id);

            // Update highest price fields
            update_field('sykes_property_highest_starting_date', $highest_price_entry['date']->format('Y-m-d'), $post_id);
            update_field('sykes_property_highest_price', $highest_price, $post_id);
            update_field('sykes_property_highest_total', $highest_price_entry['total_price'], $post_id);
            update_field('sykes_property_highest_booking_fee', $highest_price_entry['booking_fee'], $post_id);
        }
        

    }
}