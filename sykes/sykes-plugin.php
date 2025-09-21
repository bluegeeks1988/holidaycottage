<?php

/*
Plugin Name: Sykes Plugin
Description: A bespoke plugin developed by www.everge.co.uk designed to retrieve Sykes listings.
Version: 1.0
Author: Everge Studio
*/

// Include the Helpers class file
require_once plugin_dir_path(__FILE__) . 'Classes.php';

/**
 * Class representing the Sykes Plugin functionality.
 */
class SykesPlugin
{
    /**
     * Constructor for the SykesPlugin class.
     * Registers actions on plugin activation.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('add_meta_boxes', [$this, 'registerSyncMetaBox']);
        add_action('admin_post_sync_property', [$this, 'handleSyncProperty']);
        add_filter('cron_schedules', [$this, 'custom_cron_intervals']);
        add_action('admin_notices', [$this, 'displaySyncSuccessMessage']);
        add_action('save_ids_cron', [$this, 'save_ids_cron']);
        add_action('save_property_information_cron', [$this, 'save_property_information_cron']);
        add_action('save_property_availability_cron', [$this, 'save_property_availability_cron']);  // Add this line

        // Schedule save_ids_cron job
        $save_ids_interval = get_option('save_ids_interval', 1);
        if (!wp_next_scheduled('save_ids_cron')) {
            wp_schedule_event(time(), 'every_' . $save_ids_interval . '_minute', 'save_ids_cron');
        }

        // Schedule save_property_information_cron job
        $save_property_information_interval = get_option('save_property_information_interval', 1);
        if (!wp_next_scheduled('save_property_information_cron')) {
            wp_schedule_event(time(), 'every_' . $save_property_information_interval . '_minute', 'save_property_information_cron');
        }

        // Schedule save_property_availability_cron job
        $save_property_availability_interval = get_option('save_property_availability_interval', 1);
        if (!wp_next_scheduled('save_property_availability_cron')) {
            wp_schedule_event(time(), 'every_' . $save_property_availability_interval . '_minute', 'save_property_availability_cron');
        }

        // enable this and $_GET url values when testing
        // $this->save_property_information_cron();
        // $this->save_property_availability_cron();
    }

    /**
     * Register the sync meta box.
     */
    public function registerSyncMetaBox()
    {
        add_meta_box(
            'sykes_sync_meta_box',         // ID of the meta box
            'Sync Property',               // Title of the meta box
            [$this, 'renderSyncMetaBox'],  // Callback to render the meta box content
            'properties',                  // Post type where the meta box will appear
            'side',                        // Context (side for the right-hand side)
            'high'                         // Priority
        );
    }

    /**
     * Render the sync meta box content.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderSyncMetaBox($post)
    {
        // Generate the URL for the sync action
        $sync_url = add_query_arg(
            array(
                'action' => 'sync_property',
                'post_id' => $post->ID,
                'sync_property_nonce_field' => wp_create_nonce('sync_property_nonce')
            ),
            admin_url('admin-post.php')
        );
        
        // Render the sync button using an anchor tag with the generated URL
        echo '<a href="' . esc_url($sync_url) . '" id="sykes-sync-button" class="button button-primary">Sync</a>';
    }

    /**
     * Handle the form submission for syncing property.
     */
    public function handleSyncProperty()
    {
        // Get the post ID from the form submission
        $post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;

        // Check if the post ID is valid
        if ($post_id <= 0) {
            wp_die('Invalid post ID');
        }

        // Assuming SykesAPI is a class you've defined elsewhere
        $api = new SykesAPI($_ENV['API_URL'], $_ENV['API_KEY'], $_ENV['API_PASSWORD']);

        // Connect to the API
        $connection = $api->connect('property/' . $post_id);

        // Assign Taxonomy Terms
        $this->assignTaxonomyTerms($connection, $post_id, $api->taxonomyFields());

        // Map property details
        $api->mapPropertyDetails($connection);

        // Save property images
        $api->savePropertyImagesToGallery($connection);

        // Save property prices by duration
        $connection = $api->connect('pricing/' . $post_id . '?duration=' . $duration);
        $api->savePropertyPrices($connection, $post_id);

        // Save property availability
        $connection = $api->connect('property/' . $post_id . '/availability');
        $api->savePropertyAvailability($connection, $post_id);

        // Set the success message as a URL parameter
        $redirect_url = add_query_arg(array('sync_success' => '1'), admin_url('post.php?action=edit&post=' . $post_id));

        // Redirect back to the edit page after processing the form
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Display a success message after property sync.
     */
    public function displaySyncSuccessMessage()
    {
        if (isset($_GET['sync_success']) && $_GET['sync_success'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Property synced successfully!</p></div>';
        }
    }

    /**
     * Adds a menu page for the plugin.
     *
     * @return void
     */
    public function addMenuPage()
    {
        add_menu_page(
            'Sykes Plugin',            // Page title
            'Sykes Plugin',            // Menu title
            'manage_options',          // Capability required
            'sykes-plugin-settings',   // Menu slug
            [$this, 'renderSettingsPage'] // Callback function to render the page
        );
    }

    /**
     * Renders the settings page content.
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        if (isset($_POST['sykes_form_submitted']) && $_POST['sykes_form_submitted'] === '1') {
            // Sanitize and save the input values
            $save_ids_interval = isset($_POST['save_ids_interval']) ? intval($_POST['save_ids_interval']) : 60;
            $save_property_information_interval = isset($_POST['save_property_information_interval']) ? intval($_POST['save_property_information_interval']) : 60;
    
            // Update options in the database
            update_option('save_ids_interval', $save_ids_interval);
            update_option('save_property_information_interval', $save_property_information_interval);
    
            // Remove existing cron jobs if any
            $this->removeExistingCronJobs();

            // Schedule cron jobs with the saved intervals
            if (!wp_next_scheduled('save_ids_cron')) {
                $interval = 'every_' . $save_ids_interval . '_minutes';
                wp_schedule_event(time(), $interval, 'save_ids_cron');
            }
            
            if (!wp_next_scheduled('save_property_information_cron')) {
                $interval = 'every_' . $save_property_information_interval . '_minutes';
                wp_schedule_event(time(), $interval, 'save_property_information_cron');
            }
    
            // Display a success message
            Classes::get_html('components/alert.php', [
                'type' => 'success',
                'message' => 'Settings saved successfully!'
            ]);
        }
    
        Classes::get_html('main/index.php');
    }

    // Add custom interval for every minute
    function custom_cron_intervals($schedules) {
        $save_ids_interval = get_option('save_ids_interval', 1);
        $save_property_information_interval = get_option('save_property_information_interval', 1);
        $save_property_availability_interval = get_option('save_property_availability_interval', 1);
    
        // Add custom intervals for cron jobs
        $schedules['every_' . $save_ids_interval . '_minute'] = [
            'interval' => $save_ids_interval * 60,
            'display'  => __('Every ' . $save_ids_interval . ' Minute')
        ];
    
        $schedules['every_' . $save_property_information_interval . '_minute'] = [
            'interval' => $save_property_information_interval * 60,
            'display'  => __('Every ' . $save_property_information_interval . ' Minute')
        ];
    
        $schedules['every_' . $save_property_availability_interval . '_minute'] = [
            'interval' => $save_property_availability_interval * 60,
            'display'  => __('Every ' . $save_property_availability_interval . ' Minute')
        ];
    
        return $schedules;
    }

    /**
     * Remove existing cron jobs.
     *
     * @return void
     */
    public function removeExistingCronJobs()
    {
        $cron_hooks = ['save_ids_cron', 'save_property_information_cron'];

        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Saves listing IDs to WordPress.
     *
     * This method checks if the 'sykes' parameter is present in the GET request URL
     * and if its value is 'save_ids'. If both conditions are met, it connects to the
     * Sykes API using the provided credentials and inserts the listing IDs into WordPress.
     *
     * @return void
     */
    public function save_ids_cron()
    {
        // if (isset($_GET['sykes']) && $_GET['sykes'] === 'save_ids') {
            $logFile = __DIR__ . '/cron_log.txt'; // Adjust the path as necessary
            $logMessage = "Saving Property IDs: " . date('Y-m-d H:i:s') . PHP_EOL;
            
            try {
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            } catch (Exception $e) {
                error_log("Failed to write to log file: " . $e->getMessage());
            }

            // Assuming SykesAPI is a class you've defined elsewhere
            $api = new SykesAPI($_ENV['API_URL'], $_ENV['API_KEY'], $_ENV['API_PASSWORD']);
            
            // Connect to the API
            $connection = $api->connect('property');
        
            // Insert listing IDs
            $api->insertListingsIDs($connection);
        // }
    }

    /**
     * Runs a cron job to save property information.
     *
     * This method checks if the 'sykes' parameter is present in the GET request URL
     * and if its value is 'save_property_information'. If both conditions are met,
     * it retrieves draft properties from the WordPress database and connects to the
     * Sykes API to map property details. After mapping, it updates the status of
     * retrieved properties to 'publish' in the WordPress database.
     *
     * @return array|null An array of updated property IDs if the cron job runs successfully; otherwise, null.
     */
    public function save_property_information_cron()
    {
        global $wpdb;

        $logFile = __DIR__ . '/cron_log.txt'; // Adjust the path as necessary
        $logMessage = "Saving Property Information: " . date('Y-m-d H:i:s') . PHP_EOL;
        
        try {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Failed to write to log file: " . $e->getMessage());
        }

        // if (isset($_GET['sykes']) && $_GET['sykes'] === 'save_prop_info') {
            // Retrieve draft properties
            $query = "SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'properties' AND post_status = 'draft' LIMIT 30";
            $properties = $wpdb->get_results($query);

            // Initialize an empty array to store the IDs
            $property_ids = [];

            // Loop through each specified post
            foreach ($properties as $property) {
                // Get the post ID and name
                $duration = 3;
                $post_id = $property->ID;
                $post_name = $property->post_name;

                // Assuming SykesAPI is a class you've defined elsewhere
                $api = new SykesAPI($_ENV['API_URL'], $_ENV['API_KEY'], $_ENV['API_PASSWORD']);

                // Connect to the API
                $connection = $api->connect('property/' . $post_id);

                // Assign Taxonomy Terms
                $this->assignTaxonomyTerms($connection, $post_id, $api->taxonomyFields());

                // Map property details
                $api->mapPropertyDetails($connection);

                // Save property images
                $api->savePropertyImagesToGallery($connection);

                // Save property prices by duration
                $connection = $api->connect('pricing/' . $post_id . '?duration=' . $duration);
                $api->savePropertyPrices($connection, $post_id);

                // Save property availability
                $connection = $api->connect('property/' . $post_id . '/availability');
                $api->savePropertyAvailability($connection, $post_id);

                $sykes_property_name = get_field('sykes_property_name', $post_id);
                $sykes_brochure_location = get_field('sykes_brochure_location', $post_id);

                $title = $sykes_property_name . ', ' . $sykes_brochure_location;
                $slug = sanitize_title_with_dashes($sykes_property_name . '-' . $sykes_brochure_location);

                // Update post title and slug
                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_title' => $title,
                        'post_name' => $slug,
                        'post_status' => 'publish'
                    ],
                    ['ID' => $post_id]
                );

                $this->save_lat_lng($post_id);

                // Store the post ID
                $property_ids[] = $post_id;
            }
                
            // Return the updated property IDs
            return $property_ids;
        // }
    }

    public function save_property_availability_cron()
    {
        global $wpdb;

        $logFile = __DIR__ . '/cron_log.txt';
        $logMessage = "Starting Property Availability Update: " . date('Y-m-d H:i:s') . PHP_EOL;

        try {
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Failed to write to log file: " . $e->getMessage());
        }

        // Allow manual trigger or automatic execution via cron
        // if (isset($_GET['sykes']) && $_GET['sykes'] === 'save_availability_info' || defined('DOING_CRON') && DOING_CRON) {

            // Retrieve 20 properties that have not been updated recently
            $query = "
                SELECT p.ID, p.post_name 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm 
                    ON p.ID = pm.post_id AND pm.meta_key = 'awin_merchant_id'
                LEFT JOIN {$wpdb->postmeta} updated_meta 
                    ON p.ID = updated_meta.post_id AND updated_meta.meta_key = 'availability_last_updated'
                WHERE p.post_type = 'properties'
                AND p.post_status = 'publish'
                AND (pm.meta_value IS NULL OR pm.meta_value = '')
                AND (updated_meta.meta_value IS NULL OR updated_meta.meta_value < NOW() - INTERVAL 20 DAY)
                LIMIT 20
            ";

            $properties = $wpdb->get_results($query);
            $property_ids = [];

            // Process each property
            foreach ($properties as $property) {
                $post_id = $property->ID;
                $api = new SykesAPI($_ENV['API_URL'], $_ENV['API_KEY'], $_ENV['API_PASSWORD']);

                try {
                    // Save property availability
                    $connection = $api->connect('property/' . $post_id . '/availability');
                    $api->savePropertyAvailability($connection, $post_id);

                    // Update post status to 'publish' and set last updated timestamp
                    $wpdb->update(
                        $wpdb->posts,
                        ['post_status' => 'publish'],
                        ['ID' => $post_id]
                    );

                    // Update or add the 'availability_last_updated' meta field
                    update_post_meta($post_id, 'availability_last_updated', current_time('mysql'));

                    $property_ids[] = $post_id;

                } catch (Exception $e) {
                    file_put_contents($logFile, "Failed to update availability for property ID: {$post_id}. Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                    error_log("Failed to update availability for property ID: {$post_id}. Error: " . $e->getMessage());
                }
            }

            // Final log entry
            file_put_contents($logFile, "Completed Availability Update for Properties: " . implode(', ', $property_ids) . PHP_EOL, FILE_APPEND);

            // For manual runs, output to the screen
            // if (isset($_GET['sykes']) && $_GET['sykes'] === 'save_availability_info') {
            //     var_dump($property_ids);
            // }

            return $property_ids;
        // }
    }


    /**
     * Saves the latitude and longitude for a given post ID to the lat_lng table.
     *
     * This function retrieves the latitude and longitude custom fields for the given post ID.
     * If both values are available, it checks if an entry for the post ID already exists in
     * the lat_lng table. If it does, it updates the existing entry; otherwise, it inserts a new entry.
     * If either the latitude or longitude is not available, it logs an error message.
     *
     * @param int $post_id The ID of the post to save latitude and longitude for.
     *
     * @global wpdb $wpdb WordPress database access object.
     */
    function save_lat_lng($post_id) {
        global $wpdb; // Ensure $wpdb is accessible

        // Retrieve latitude and longitude values
        $lat = get_field('sykes_property_latitude', $post_id);
        $long = get_field('sykes_property_longitude', $post_id);

        if ($lat !== false && $long !== false) {
            // Check if an entry for the post_id already exists
            $existing_entry = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM lat_lng WHERE post_id = %d",
                $post_id
            ));

            if ($existing_entry) {
                // Update existing entry
                $wpdb->update(
                    'lat_lng',
                    [
                        'latitude' => $lat,
                        'longitude' => $long
                    ],
                    ['post_id' => $post_id]
                );
            } else {
                // Insert new entry
                $wpdb->insert(
                    'lat_lng',
                    [
                        'latitude' => $lat,
                        'longitude' => $long,
                        'post_id' => $post_id
                    ]
                );
            }
        } else {
            // Handle case where latitude or longitude is not available
            error_log("Latitude or Longitude not available for post ID: $post_id");
        }
    }

    /**
     * Assigns taxonomy terms based on filtered fields.
     *
     * @param object $connection The connection object containing property features and their corresponding keys.
     * @param int $post_id The ID of the post to which terms will be assigned.
     * @param array $fields The taxonomy fields to be assigned.
     * @return void
     */
    public function assignTaxonomyTerms($connection, $post_id, $fields)
    {
        $data = $connection->payload;

        foreach ($fields as $fieldName => $key) {
            // Split the key to navigate the $data object
            $parts = explode('->', $key);
            $value = $data;

            // Navigate through the object properties
            foreach ($parts as $part) {
                if (isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    $value = null;
                    break;
                }
            }

            // If value is null, skip this field
            if ($value === null) {
                continue;
            }

            // Extract the label from the field name for term creation
            $term_name = str_replace('_', ' ', $fieldName);
            // Capitalize each word in the term name
            $term_name = ucwords($term_name);
            $term_slug = sanitize_title($term_name);

            // Check if the term already exists
            $existing_term = $this->getExistingTerm($term_name);

            if (!$existing_term) {
                // If the term doesn't exist, insert it
                $term_id = $this->insertNewTerm($term_name, $term_slug);
            } else {
                // If the term exists, update it
                $term_id = $existing_term->term_id;
            }

            // If the field value is true (1), insert into term_relationships
            if ($value) {
                $this->insertTermRelationship($post_id, $term_id);
            }

            // Debug information
            echo '<pre>';
                var_dump($fieldName . ' => ' . $value . ' - ' . $post_id);
            echo '</pre>';
        }
    }

    /**
     * Gets an existing term from the database.
     *
     * @param string $term_name The name of the term to retrieve.
     * @return object|false The existing term object if found, otherwise false.
     */
    private function getExistingTerm($term_name)
    {
        global $wpdb;

        // Check if the term already exists
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->terms} WHERE name = %s",
                $term_name
            )
        );
    }

    /**
     * Inserts a new term into the database.
     *
     * @param string $term_name The name of the term to insert.
     * @param string $term_slug The slug of the term to insert.
     * @return int The ID of the inserted term.
     */
    private function insertNewTerm($term_name, $term_slug)
    {
        global $wpdb;

        // Insert the term
        $wpdb->insert(
            $wpdb->terms,
            [
                'name' => $term_name,
                'slug' => $term_slug,
                'term_group' => 0
            ]
        );

        // Get the term ID after insertion
        $term_id = $wpdb->insert_id;

        // Insert term_taxonomy
        $wpdb->insert(
            $wpdb->term_taxonomy,
            [
                'term_id' => $term_id,
                'taxonomy' => 'property_features',
                'parent' => 0,
                'count' => 1
            ]
        );

        return $term_id;
    }

    /**
     * Inserts a term relationship into the database.
     *
     * @param int $post_id The ID of the post to which the term will be related.
     * @param int $term_id The ID of the term to be related.
     * @return void
     */
    private function insertTermRelationship($post_id, $term_id)
    {
        global $wpdb;

        // Get the term_taxonomy_id
        $term_taxonomy_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d",
                $term_id
            )
        );

        // Attempt to insert into term_relationships
        $wpdb->insert(
            $wpdb->term_relationships,
            [
                'object_id' => $post_id,
                'term_taxonomy_id' => $term_taxonomy_id,
                'term_order' => 0
            ]
        );
    }
}

// Instantiate the class to activate the plugin functionality
new SykesPlugin();
