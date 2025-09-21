<?php
@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '300' );

add_filter( 'do_rocket_lazyload_iframes', '__return_false' );

/**
 * Filters out specific terms based on their slugs.
 *
 * This function takes an array of term objects and removes those
 * with slugs matching any in the predefined list of excluded slugs.
 *
 * @param array $terms An array of term objects to be filtered. Each term object should have a 'slug' property.
 * 
 * @return array An array of term objects with the excluded slugs removed.
 */
function show_terms($terms) {
    $allowed_slugs = array('log-fire', 'hot-tub', 'near-beach', 'luxury', 'swimming-pool');

    // Filter the terms to keep only the ones that match the allowed slugs
    $filtered_terms = array_filter($terms, function($term) use ($allowed_slugs) {
        return in_array($term->slug, $allowed_slugs);  // Keep only terms with allowed slugs
    });

    // Return the filtered array with only the allowed terms
    return $filtered_terms;
}


/**
 * Get properties by latitude and longitude within a given distance, with pagination support.
 *
 * @param float $latitude  The latitude of the location.
 * @param float $longitude The longitude of the location.
 * @param int $distance    The distance in kilometers (default is 20 km).
 * @param int $posts_per_page Number of posts to display per page.
 * @param int $paged The current page number.
 *
 * @return WP_Query The WP_Query object containing the paginated results.
 */
function get_properties_by_location($latitude, $longitude, $distance = 20, $posts_per_page = 10, $paged = 1) {
    global $wpdb;

    // Haversine formula to calculate distance between two points
    $lat_range = $distance / 111; // Latitude degree range per km
    $lon_range = $distance / (111 * cos(deg2rad($latitude))); // Longitude degree range

    $min_lat = $latitude - $lat_range;
    $max_lat = $latitude + $lat_range;
    $min_lon = $longitude - $lon_range;
    $max_lon = $longitude + $lon_range;

    // Get post IDs of properties within the location range
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id 
         FROM lat_lng 
         WHERE latitude BETWEEN %f AND %f 
         AND longitude BETWEEN %f AND %f",
         $min_lat, $max_lat, $min_lon, $max_lon
    ));

    if (!empty($post_ids)) {
        $query_args = [
            'post_type'      => 'properties',
            'post__in'       => $post_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => $posts_per_page, // Pagination
            'paged'          => $paged,          // Current page for pagination
        ];

        return new WP_Query($query_args);
    }

    return new WP_Query(['post__in' => [0]]); // Return empty query if no results found
}

function update_all_post_dates_to_current() {
    // Fetch all posts
    $args = array(
        'post_type'      => 'properties', // or 'page' or your custom post type
        'posts_per_page' => -1,     // Get all posts
        'post_status'    => 'publish'
    );

    $all_posts = get_posts($args);

    foreach ($all_posts as $post) {
        // Get the current date and time
        $current_date = current_time('mysql');
        $current_date_gmt = current_time('mysql', 1);

        // Update the post date
        wp_update_post(array(
            'ID'           => $post->ID,
            'post_date'    => $current_date,
            'post_date_gmt'=> $current_date_gmt
        ));
    }
}

// add_action('init', 'update_all_post_dates_to_current');


// function assign_regions_based_on_coordinates() {
//     global $wpdb;

//     // Get all rows from the lat_lng table
//     $results = $wpdb->get_results("SELECT post_id, latitude, longitude FROM lat_lng");

//     // Define the bounding boxes for each region
//     $regions = [
//         'Scotland' => ['north' => 60.85, 'south' => 54.64, 'west' => -8.65, 'east' => -0.82],
//         'Yorkshire And The Humber' => ['north' => 54.61, 'south' => 52.95, 'west' => -2.56, 'east' => -0.25],
//         'West Midlands' => ['north' => 53.25, 'south' => 51.79, 'west' => -3.02, 'east' => -1.23],
//         'Wales' => ['north' => 53.43, 'south' => 51.30, 'west' => -5.56, 'east' => -2.65],
//         'South West' => ['north' => 52.13, 'south' => 49.96, 'west' => -5.71, 'east' => -1.36],
//         'South East' => ['north' => 52.18, 'south' => 50.57, 'west' => -1.92, 'east' => 1.45],
//         'Northern Ireland' => ['north' => 55.32, 'south' => 54.06, 'west' => -8.17, 'east' => -5.43],
//         'North West' => ['north' => 55.12, 'south' => 52.98, 'west' => -3.63, 'east' => -1.96],
//         'North East' => ['north' => 55.81, 'south' => 53.83, 'west' => -2.45, 'east' => -0.81],
//         'Greater London' => ['north' => 51.70, 'south' => 51.28, 'west' => -0.51, 'east' => 0.33],
//         'Eastern England' => ['north' => 53.65, 'south' => 51.72, 'west' => -0.61, 'east' => 1.77],
//         'East Midlands' => ['north' => 53.57, 'south' => 52.11, 'west' => -1.78, 'east' => -0.42],
//         'London' => ['north' => 51.686, 'south' => 51.261, 'west' => -0.510, 'east' => 0.334]
//     ];

//     // Loop through each row
//     foreach ($results as $row) {
//         $post_id = $row->post_id;
//         $latitude = $row->latitude;
//         $longitude = $row->longitude;

//         // Determine the region for each latitude and longitude
//         $region_name = null;

//         foreach ($regions as $region => $bounds) {
//             if ($latitude <= $bounds['north'] && $latitude >= $bounds['south'] &&
//                 $longitude >= $bounds['west'] && $longitude <= $bounds['east']) {
//                 $region_name = $region;
//                 break; // Stop the loop if we found the matching region
//             }
//         }

//         // If a region is found, save it to the taxonomy
//         if ($region_name) {
//             $term_id = insert_region_term($region_name);

//             var_dump($term_id);

//             // Associate the term with the post
//             if ($term_id) {
//                 wp_set_post_terms($post_id, [$term_id], 'regions', false);
//             }
//         }
//     }
// }

// function insert_region_term($term_name) {
//     global $wpdb;

//     // Check if the term already exists
//     $existing_term = $wpdb->get_row($wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE name = %s", $term_name));

//     if ($existing_term) {
//         return $existing_term->term_id; // Return existing term ID if found
//     }

//     // Sanitize the slug for the term
//     $term_slug = sanitize_title($term_name);

//     // Insert the term
//     $wpdb->insert(
//         $wpdb->terms,
//         [
//             'name' => $term_name,
//             'slug' => $term_slug,
//             'term_group' => 0
//         ]
//     );

//     // Get the term ID after insertion
//     $term_id = $wpdb->insert_id;

//     if ($term_id) {
//         // Insert into term_taxonomy
//         $wpdb->insert(
//             $wpdb->term_taxonomy,
//             [
//                 'term_id' => $term_id,
//                 'taxonomy' => 'regions',
//                 'parent' => 0,
//                 'count' => 0 // Initialize count as 0
//             ]
//         );
//     }

//     return $term_id;
// }

// // Check if the 'saveregions' parameter is present in the URL
// function run_assign_regions_on_query() {
//     if (isset($_GET['saveregions'])) {
//         assign_regions_based_on_coordinates();
//         echo "Regions have been assigned based on coordinates.";
//         exit; // Ensure no further processing occurs
//     }
// }

// // Hook the function to the 'template_redirect' action to check the URL parameter
// add_action('template_redirect', 'run_assign_regions_on_query');

function assign_regions_based_on_coordinates() {
    global $wpdb;

    // Get all rows from the lat_lng table
    $results = $wpdb->get_results("SELECT post_id, latitude, longitude FROM lat_lng");

    // Define the bounding boxes for each region
    $regions = [
        'Whitby' => ['north' => 54.500, 'south' => 54.470, 'west' => -0.640, 'east' => -0.580],
        'Salcombe' => ['north' => 50.250, 'south' => 50.230, 'west' => -3.800, 'east' => -3.760],
        'Dartmouth' => ['north' => 50.360, 'south' => 50.340, 'west' => -3.590, 'east' => -3.570],
        'Weymouth' => ['north' => 50.630, 'south' => 50.610, 'west' => -2.470, 'east' => -2.430],
        'Newquay' => ['north' => 50.430, 'south' => 50.410, 'west' => -5.090, 'east' => -5.060],
        'Keswick' => ['north' => 54.620, 'south' => 54.590, 'west' => -3.150, 'east' => -3.120],
        'Bowness-on-Windermere' => ['north' => 54.370, 'south' => 54.350, 'west' => -2.930, 'east' => -2.900],
        'Scarborough' => ['north' => 54.300, 'south' => 54.270, 'west' => -0.440, 'east' => -0.400],
        'Wells-next-the-Sea' => ['north' => 52.970, 'south' => 52.940, 'west' => 0.830, 'east' => 0.870],
        'Filey' => ['north' => 54.210, 'south' => 54.190, 'west' => -0.310, 'east' => -0.270],
        'Llandudno' => ['north' => 53.330, 'south' => 53.310, 'west' => -3.850, 'east' => -3.810],
        'Benllech' => ['north' => 53.330, 'south' => 53.310, 'west' => -4.230, 'east' => -4.200],
        'Rhosneigr' => ['north' => 53.240, 'south' => 53.220, 'west' => -4.530, 'east' => -4.500],
        'Conwy' => ['north' => 53.280, 'south' => 53.260, 'west' => -3.840, 'east' => -3.810],
        'Falmouth' => ['north' => 50.170, 'south' => 50.150, 'west' => -5.090, 'east' => -5.050],
        'Ambleside' => ['north' => 54.430, 'south' => 54.410, 'west' => -2.970, 'east' => -2.930],
        'Looe' => ['north' => 50.360, 'south' => 50.340, 'west' => -4.460, 'east' => -4.430],
        'St Agnes' => ['north' => 50.310, 'south' => 50.290, 'west' => -5.200, 'east' => -5.160],
        'Lanreath' => ['north' => 50.400, 'south' => 50.370, 'west' => -4.540, 'east' => -4.510],
        'Pwllheli' => ['north' => 52.900, 'south' => 52.870, 'west' => -4.420, 'east' => -4.390],
        'Torquay' => ['north' => 50.480, 'south' => 50.450, 'west' => -3.540, 'east' => -3.510],
        'Haworth' => ['north' => 53.830, 'south' => 53.810, 'west' => -1.960, 'east' => -1.930],
        'Bridlington' => ['north' => 54.110, 'south' => 54.090, 'west' => -0.210, 'east' => -0.170],
        'Crantock' => ['north' => 50.410, 'south' => 50.390, 'west' => -5.120, 'east' => -5.090],
        'Marazion' => ['north' => 50.130, 'south' => 50.110, 'west' => -5.480, 'east' => -5.450],
        'Beaumaris' => ['north' => 53.270, 'south' => 53.250, 'west' => -4.100, 'east' => -4.070]
    ];

    // Loop through each row
    foreach ($results as $row) {
        $post_id = $row->post_id;
        $latitude = $row->latitude;
        $longitude = $row->longitude;

        // Determine the region for each latitude and longitude
        $region_name = null;

        foreach ($regions as $region => $bounds) {
            if ($latitude <= $bounds['north'] && $latitude >= $bounds['south'] &&
                $longitude >= $bounds['west'] && $longitude <= $bounds['east']) {
                $region_name = $region;
                break; // Stop the loop if we found the matching region
            }
        }

        // If a region is found, save it to the taxonomy
        if ($region_name) {
            $term_id = insert_region_term($region_name);

            var_dump($term_id);

            // Associate the term with the post
            if ($term_id) {
                wp_set_post_terms($post_id, [$term_id], 'towns', false);
            }
        }
    }
}

function insert_region_term($term_name) {
    global $wpdb;

    // Check if the term already exists
    $existing_term = $wpdb->get_row($wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE name = %s", $term_name));

    if ($existing_term) {
        return $existing_term->term_id; // Return existing term ID if found
    }

    // Sanitize the slug for the term
    $term_slug = sanitize_title($term_name);

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

    if ($term_id) {
        // Insert into term_taxonomy
        $wpdb->insert(
            $wpdb->term_taxonomy,
            [
                'term_id' => $term_id,
                'taxonomy' => 'towns',
                'parent' => 0,
                'count' => 0 // Initialize count as 0
            ]
        );
    }

    return $term_id;
}

// Check if the 'saveregions' parameter is present in the URL
function run_assign_regions_on_query() {
    if (isset($_GET['savetowns'])) {
        assign_regions_based_on_coordinates();
        echo "Regions have been assigned based on coordinates.";
        exit; // Ensure no further processing occurs
    }
}

// Hook the function to the 'template_redirect' action to check the URL parameter
add_action('template_redirect', 'run_assign_regions_on_query');



// function delete_region_terms() {
//     global $wpdb;

//     // Define the regions to be deleted
//     $regions_to_delete = [
//         'Scotland',
//         'Yorkshire And The Humber',
//         'West Midlands',
//         'Wales',
//         'South West',
//         'South East',
//         'Northern Ireland',
//         'North West',
//         'North East',
//         'Greater London',
//         'Eastern England',
//         'East Midlands'
//     ];

//     foreach ($regions_to_delete as $region_name) {
//         // Get the term ID by name and taxonomy
//         $term = get_term_by('name', $region_name, 'regions');
        
//         if ($term) {
//             // Delete the term
//             wp_delete_term($term->term_id, 'regions');
//             echo "Deleted term: " . $region_name . "<br>";
//         } else {
//             echo "Term not found: " . $region_name . "<br>";
//         }
//     }
// }

// // Hook this function to the 'template_redirect' action to trigger via URL
// function run_delete_regions_on_query() {
//     if (isset($_GET['deleteregions'])) {
//         delete_region_terms();
//         echo "Regions have been deleted.";
//         exit; // Ensure no further processing occurs
//     }
// }

// // Add the function to the template_redirect hook
// add_action('template_redirect', 'run_delete_regions_on_query');


/**
 * Renames a specified string based on a mapping array.
 *
 * @param string $string The string to be checked and possibly renamed.
 * @param array $renameMap An associative array where keys are old values and values are new values.
 * @return string The potentially renamed string.
 */
function renameString($string, $renameMap) {
    // Check if the string matches any key in the rename map
    if (array_key_exists($string, $renameMap)) {
        // Return the corresponding new value
        return $renameMap[$string];
    }
    // Return the original value if no match is found
    return $string;
}

/**
 * Filters out specific terms based on their slugs.
 *
 * This function takes an array of term objects and removes those
 * with slugs matching any in the predefined list of excluded slugs.
 *
 * @param array $terms An array of term objects to be filtered. Each term object should have a 'slug' property.
 * 
 * @return array An array of term objects with the excluded slugs removed.
 */
function filter_out_terms($terms) {
    // Define the terms to exclude
    $exclude_slugs = array('near-beach', 'washing-machine', 'dishwasher', 'coastal', 'jacuzzi');
    
    // Filter the terms
    $filtered_terms = array_filter($terms, function($term) use ($exclude_slugs) {
        return !in_array($term->slug, $exclude_slugs);
    });

    return $filtered_terms;
}

/**
 * Register post type
 * 
 * @return void
 */
function register_post_types() 
{
	$theme = get_template();

    register_post_type( 'properties', array(
		'label'  				=> __( 'Properties' ),
		'description' 			=> __( '' ),
		'labels' 				=> array(
			"name" 					=> __( 'Properties', $theme ),
			"singular_name" 		=> __( 'Property', $theme ),
			"all_items"				=> __( 'All Properties', $theme ),
			"add_new_item" 			=> __( 'Add New Property', $theme ),
			"new_item" 				=> __( 'New Property', $theme ),
            'edit_item'             => __( 'Edit Property', $theme ),
			"search_items" 			=> __( 'Search Properties', $theme ),
		),
		'supports' 				=> array( 'title', 'revisions', ),
		'taxonomies'  			=> array( 'property_features' ), // filter
		'hierarchical'        	=> true,
		'public'        	   	=> true,
		'show_ui' 				=> true,
		'show_in_menu' 			=> true,
		'show_in_nav_menus' 	=> true,
		'show_in_admin_bar' 	=> true,
		'rewrite' 				=> array(
			'slug' 					=> 'properties',
			'with_front' 			=> false
		),
		'can_export' 			=> true,
		'has_archive'  			=> true,
		'exclude_from_search' 	=> false,
		'publicly_queryable' 	=> true,
		'capability_type' 		=> 'page',
		'menu_icon' 			=> 'dashicons-portfolio',
        'show_in_rest'          => true,
	));
}

add_action( 'init', 'register_post_types');

/**
 * Register Taxonomies
 * 
 * @return void
 */
function register_taxonomies() 
{
	$theme = get_template();

    register_taxonomy( 'property_features', array( 'properties' ), array(
        'hierarchical'          => true,
        'labels'                => array(
            'name'                  => _x( 'Features', $theme ),
            'singular_name'         => _x( 'Feature', $theme ),
            'search_items'          => __( 'Search Features' ),
            'all_items'             => __( 'All Features' ),
            'parent_item'           => __( 'Parent Feature' ),
            'parent_item_colon'     => __( 'Parent Feature:' ),
            'edit_item'             => __( 'Edit Feature' ),
            'update_item'           => __( 'Update Feature' ),
            'add_new_item'          => __( 'Add New Feature' ),
            'new_item_name'         => __( 'New Feature' ),
            'menu_name'             => __( 'Features' ),
        ),
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_generic_term_count',
        'has_archive'  			=> false,
    ));

    register_taxonomy( 'regions', array( 'properties' ), array(
        'hierarchical'          => true,
        'labels'                => array(
            'name'                  => _x( 'Regions', $theme ),
            'singular_name'         => _x( 'Region', $theme ),
            'search_items'          => __( 'Search Regions' ),
            'all_items'             => __( 'All Regions' ),
            'parent_item'           => __( 'Parent Region' ),
            'parent_item_colon'     => __( 'Parent Region:' ),
            'edit_item'             => __( 'Edit Region' ),
            'update_item'           => __( 'Update Region' ),
            'add_new_item'          => __( 'Add New Region' ),
            'new_item_name'         => __( 'New Region' ),
            'menu_name'             => __( 'Regions' ),
        ),
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_generic_term_count'
    ));

    register_taxonomy( 'towns', array( 'properties' ), array(
        'hierarchical'          => true,
        'labels'                => array(
            'name'                  => _x( 'Towns', $theme ),
            'singular_name'         => _x( 'Town', $theme ),
            'search_items'          => __( 'Search Towns' ),
            'all_items'             => __( 'All Towns' ),
            'parent_item'           => __( 'Parent Town' ),
            'parent_item_colon'     => __( 'Parent Town:' ),
            'edit_item'             => __( 'Edit Town' ),
            'update_item'           => __( 'Update Town' ),
            'add_new_item'          => __( 'Add New Town' ),
            'new_item_name'         => __( 'New Town' ),
            'menu_name'             => __( 'Towns' ),
        ),
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_generic_term_count'
    ));
}

add_action( 'init', 'register_taxonomies' );

/**
 * Enqueue Stylesheets.
 * 
 * @return void
 */
function enqueue_styles() 
{
    wp_enqueue_style( 'main', get_stylesheet_directory_uri() . '/media/dist/css/style.css' );
    wp_enqueue_style( 'pikaday-css', 'https://cdnjs.cloudflare.com/ajax/libs/pikaday/1.8.0/css/pikaday.min.css', [], '1.8.0' );
}

add_action('wp_enqueue_scripts', 'enqueue_styles');

/**
 * Enqueue Scripts.
 * 
 * @return void
 */
function enqueue_scripts() 
{
    // Enqueue Google Maps JavaScript API
    wp_enqueue_script(
        'google-maps',
        'https://maps.googleapis.com/maps/api/js?key=AIzaSyACvu7LbGJ1IH8B2Qy7bcC0cd6TMdTBO_c&libraries=places',
        [],
        null,
        true
    );

    wp_enqueue_script( 'greensock.bundle', get_stylesheet_directory_uri() . '/media/dist/js/greensock.min.js', [], null, true );
    wp_enqueue_script( 'vendor.bundle', get_stylesheet_directory_uri() . '/media/dist/js/vendor.min.js', [], null, true );
    wp_enqueue_script( 'main.bundle', get_stylesheet_directory_uri() . '/media/dist/js/global.min.js', [], null, true );

    // Enqueue Pikaday and Moment.js
    wp_enqueue_script('moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js', [], null, true);
    wp_enqueue_script('pikaday-js', 'https://cdnjs.cloudflare.com/ajax/libs/pikaday/1.8.0/pikaday.min.js', [], null, true);
}

add_action('wp_enqueue_scripts', 'enqueue_scripts');

/**
 * Get html.
 *
 * @param  string $path
 * @param  array $args
 * @param  boolean $load
 * 
 * @return string
 */
function get_html($path = '', $args = array(), $load = true) 
{
    locate_template($path, false, false);
    extract($args);

    if($load) {
        return require($path);
    }

    ob_start();
    require($path);
    $file = ob_get_contents(); 
    ob_end_clean();

    return $file;
}


/**
 * Get page id on taxonomy page.
 */
function page_id() 
{
    $object = get_queried_object();

	if(property_exists($object, 'term_id')) {
		return $object->taxonomy . '_' . $object->term_id;
	}

	if(property_exists($object, 'ID')) {
		return $object->ID;
	}

	return get_page_by_path( $object->rewrite['slug']);
}


/**
 * Get SVG.
 * 
 * @return void
 */
function get_svg( $sFilePath = NULL, $bReturn = false ) {
	$sFileContents = NULL;
	
	$sSvgPath = get_stylesheet_directory() .'/media/dist/images/'.$sFilePath.'.svg';
	
	if( is_readable( $sSvgPath ) ){
		$sFileContents = file_get_contents( $sSvgPath );
	}

    if( $bReturn ) {
		return $sFileContents;
	} else {
		echo $sFileContents;
	}
}


function get_svg_img($sFilePath = NULL, $alt = '', $class = '') {
    $sFileContents = NULL;
    
    $sSvgPath = get_stylesheet_directory() . '/media/dist/images/' . $sFilePath . '.svg';
    
    if (is_readable($sSvgPath)) {
        $sFileContents = file_get_contents($sSvgPath);
    }

    if (!empty($sFileContents)) {
        // Embed the SVG content into the img tag
        $encoded_svg = 'data:image/svg+xml,' . rawurlencode($sFileContents);
        
        // Build the img tag
        $img_tag = '<img src="' . $encoded_svg . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '">';
        
        return $img_tag;
    } else {
        // Return a fallback image or an empty string if SVG is not readable
        return '';
    }
}

/**
 * Get Image.
 * 
 * @return void
 */
function get_img($name = NULL) 
{
	$svg = get_stylesheet_directory_uri() . '/media/dist/images/' . $name;
	return $svg;
}

/**
 * ACF Options.
 * 
 * @return void
 */
function acf_options() 
{
    if( function_exists('acf_add_options_page') ) {

        $option_page = acf_add_options_page(array(
            'page_title'    => __('Sitewide Content'),
            'menu_title'    => __('Sitewide Content'),
            'menu_slug'     => 'sitewide-content',
            'capability'    => 'edit_posts',
            'redirect'      => false
        ));
    }
}

add_action('acf/init', 'acf_options');


/**
 * Remove comments from the admin menu.
 * 
 * @return void
 */
function remove_admin_menu() 
{
    remove_menu_page('edit-comments.php');
}

add_action('admin_init', 'remove_admin_menu');


/**
 * Remove links from menu bar.
 * 
 * @return void
 */
function admin_menu() 
{
    global $wp_admin_bar;
    
    $wp_admin_bar->remove_menu('comments');
}

add_action( 'wp_before_admin_bar_render', 'admin_menu' );


/**
 * Save ACF json in the theme folder.
 * 
 * @return string
 */ 
function save_acf_json($path) 
{
    return get_stylesheet_directory() . '/json';
}

add_filter('acf/settings/save_json', 'save_acf_json');


/**
 * Load ACF json from the theme folder.
 * 
 * @return array
 */ 
function load_acf_json($paths) 
{
    unset($paths[0]);
    
    $paths[] = get_stylesheet_directory() . '/json';

    return $paths;
}

add_filter('acf/settings/load_json', 'load_acf_json');

/**
 * Converts a multiline string of image URLs into an array of URLs.
 *
 * This function takes the content of an ACF field, which is expected to be a
 * multiline string where each line is a URL of an image. It splits the string
 * by new lines, validates each URL, and returns an array of valid URLs.
 *
 * @param string $acf_field_value The content of the ACF field containing the image URLs.
 * @return array An array of valid image URLs.
 */
function get_image_urls_array($acf_field_value) {
    // Split the textarea content by new lines to get each URL
    $image_urls = explode(PHP_EOL, trim($acf_field_value));
    
    // Filter out any empty lines or invalid URLs
    $image_urls = array_filter($image_urls, function($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    });

    return $image_urls;
}

/**
 * Retrieves and sanitizes search fields from the URL parameters.
 *
 * This function checks for 'destination', 'checkin', 'checkout', 'latitude', and 'longitude'
 * parameters in the URL, sanitizes them, and returns them in an associative array.
 *
 * @return array {
 *     An associative array of sanitized search fields.
 *
 *     @type string $destination The destination provided in the URL, or an empty string if not set.
 *     @type string $checkin     The check-in date provided in the URL, or an empty string if not set.
 *     @type string $checkout    The check-out date provided in the URL, or an empty string if not set.
 *     @type string $latitude    The latitude provided in the URL, or an empty string if not set.
 *     @type string $longitude   The longitude provided in the URL, or an empty string if not set.
 *     @type string $longitude   The longitude provided in the URL, or an empty string if not set.
 * }
 */
function prepopulate_search_fields() {
    $destination = isset($_GET['destination']) ? sanitize_text_field(urldecode($_GET['destination'])) : '';
    $checkin = isset($_GET['checkin']) ? sanitize_text_field($_GET['checkin']) : '';
    $checkout = isset($_GET['checkout']) ? sanitize_text_field($_GET['checkout']) : '';
    $latitude = isset($_GET['latitude']) ? sanitize_text_field($_GET['latitude']) : '';
    $longitude = isset($_GET['longitude']) ? sanitize_text_field($_GET['longitude']) : '';
    $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : '';
    $guests = isset($_GET['guests']) ? intval($_GET['guests']) : ''; // added by Aaron

    return array(
        'destination' => $destination,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'filter' => $filter,
        'guests' => $guests, // added by Aaron
    );
}

/**
 * Extend query variables to include latitude, longitude, check-in, and check-out dates.
 *
 * This function adds 'latitude', 'longitude', 'checkin', and 'checkout' to the list of
 * recognized query variables in WordPress. This allows these variables to be used in
 * the URL and accessed via WP_Query.
 *
 * @param array $vars An array of the current query variables.
 * @return array The modified array of query variables.
 */
function add_location_query_vars_filter($vars) {
    $vars[] = 'latitude';
    $vars[] = 'longitude';
    $vars[] = 'checkin';
    $vars[] = 'checkout';
    $vars[] = 'filter';
    return $vars;
}

// Hook the function to the 'query_vars' filter.
add_filter('query_vars', 'add_location_query_vars_filter');

/**
 * Parses an availability string and converts it to a JSON-encoded array.
 *
 * The input string should have dates and their corresponding availability status
 * separated by " - ", with each date-status pair on a new line.
 *
 * Example input:
 * 2024-05-20 - Not Available
 * 2024-05-21 - Available
 *
 * @param string $availabilityString The availability string containing date and status pairs.
 * @return string JSON-encoded string representing the availability data.
 */
function parseAvailability($availabilityString) {
    // Split the string into lines
    $lines = explode("\n", $availabilityString);
    $availability = [];

    // Process each line to extract date and status
    foreach ($lines as $line) {
        // Trim the line to remove any leading/trailing whitespace
        $line = trim($line);
        
        // Skip empty lines
        if (empty($line)) {
            continue;
        }
        
        // Split the line into date and status
        list($date, $status) = explode(' - ', $line, 2);
        
        // Add date and status to the availability array
        $availability[] = [
            'date' => $date,
            'status' => trim($status)
        ];
    }

    // Return the availability data as a JSON-encoded string
    return json_encode($availability);
}

/**
 * Checks if all dates between check-in and check-out are available.
 *
 * This function parses an availability string, checks each date between the given check-in
 * and check-out dates, and returns whether all those dates are marked as available.
 *
 * @param string $availability A string containing dates and their availability status.
 * @param string $checkin The check-in date in 'Y-m-d' format.
 * @param string $checkout The check-out date in 'Y-m-d' format.
 * @return bool True if all dates between check-in and check-out are available, false otherwise.
 */
function are_dates_available($availability, $checkin, $checkout) {
    $available_dates = array();

    // Parse the availability field
    $lines = explode("\n", $availability);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        list($date, $status) = explode(' - ', $line);
        if (strtolower($status) === 'available') {
            $available_dates[] = trim($date);
        }
    }

    // Convert check-in and check-out to DateTime objects
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);

    // Loop through each day from check-in to check-out
    $current_date = clone $checkin_date;
    while ($current_date <= $checkout_date) {
        if (!in_array($current_date->format('Y-m-d'), $available_dates)) {
            return false; // A date is not available
        }
        $current_date->modify('+1 day');
    }
    return true;
}

/**
 * Filters the main query on the properties archive page to include location and date-based filtering.
 *
 * This function modifies the main query on the properties archive page to filter posts based on
 * latitude, longitude, and an optional distance. It also filters posts based on availability
 * between check-in and check-out dates if provided.
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
function location_based_pre_get_posts($query) {
    if ($query->is_main_query() && !is_admin() && (is_post_type_archive('properties') || is_tax('regions'))) {
        $latitude = get_query_var('latitude');
        $longitude = get_query_var('longitude');
        $distance = get_query_var('distance', 20);
        $taxonomy_terms = get_query_var('filter');

        $checkin = get_query_var('checkin');
        $checkout = get_query_var('checkout');

        // Check if latitude and longitude are provided and valid
        if ($latitude && $longitude && is_numeric($latitude) && is_numeric($longitude)) {
            add_filter('posts_clauses', function($clauses, $query) use ($latitude, $longitude, $distance) {
                global $wpdb;

                if ($query->is_main_query() && !is_admin() && is_post_type_archive('properties')) {
                    $lat_range = $distance / 111;
                    $lon_range = $distance / (111 * cos(deg2rad($latitude)));

                    $min_lat = $latitude - $lat_range;
                    $max_lat = $latitude + $lat_range;
                    $min_lon = $longitude - $lon_range;
                    $max_lon = $longitude + $lon_range;

                    $haversine = "(6371 * acos(cos(radians(%f)) * cos(radians(lat_lng.latitude)) * cos(radians(lat_lng.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(lat_lng.latitude))))";
                    $haversine = sprintf($haversine, $latitude, $longitude, $latitude);

                    $clauses['fields'] .= ", {$haversine} AS distance";
                    $clauses['join'] .= " INNER JOIN lat_lng ON ({$wpdb->posts}.ID = lat_lng.post_id)";
                    $clauses['orderby'] = 'distance ASC';

                    $clauses['where'] .= $wpdb->prepare(
                        " AND lat_lng.latitude BETWEEN %f AND %f AND lat_lng.longitude BETWEEN %f AND %f",
                        $min_lat, $max_lat, $min_lon, $max_lon
                    );
                }

                return $clauses;
            }, 10, 2);

            // Set meta query for latitude and longitude existence
            // Build meta query with lat/lng and guests
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'sykes_property_latitude',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => 'sykes_property_longitude',
                    'compare' => 'EXISTS',
                ),
            );
            
            // Add guests filter if set
            if (isset($_GET['guests']) && intval($_GET['guests']) > 0) {
                $guests = intval($_GET['guests']);
                $meta_query[] = array(
                    'key'     => 'sykes_capacity', // <-- correct ACF field
                    'value'   => $guests,
                    'type'    => 'NUMERIC',
                    'compare' => '>='
                );
            }


            $query->set('meta_query', $meta_query);


            // Set orderby and order
            $query->set('orderby', 'distance');
            $query->set('order', 'ASC');
        }

        // Taxonomy query for specified terms
        if (!empty($taxonomy_terms)) {
			$terms = array_map('trim', explode(',', $taxonomy_terms));
			$query->set('tax_query', array(
				array(
					'taxonomy' => 'property_features',
					'field'    => 'slug',
					'terms'    => $terms,
					'operator' => 'IN', // ✅ match ANY selected feature
				),
			));
		}

        // Date-based filtering
        if ($checkin && $checkout) {
            add_filter('the_posts', function($posts, $query) use ($checkin, $checkout) {
                if ($query->is_main_query() && !is_admin() && is_post_type_archive('properties')) {
                    $filtered_posts = array();
                    foreach ($posts as $post) {
                        $availability = get_field('available', $post->ID);
                        if ($availability && are_dates_available($availability, $checkin, $checkout)) {
                            $filtered_posts[] = $post;
                        }
                    }
                    return $filtered_posts;
                }
                return $posts;
            }, 10, 2);

            add_filter('found_posts', function($found_posts, $query) use ($checkin, $checkout) {
                if ($query->is_main_query() && !is_admin() && is_post_type_archive('properties')) {
                    global $wpdb;
                    $filtered_post_ids = $wpdb->get_col($query->request);
                    $filtered_posts_count = 0;
                    foreach ($filtered_post_ids as $post_id) {
                        $availability = get_field('available', $post_id);
                        if ($availability && are_dates_available($availability, $checkin, $checkout)) {
                            $filtered_posts_count++;
                        }
                    }
                    return $filtered_posts_count;
                }
                return $found_posts;
            }, 10, 2);
        }
    }
}

add_action('pre_get_posts', 'location_based_pre_get_posts');




/**
 * Returns the highest rating and its label from three different rating sources.
 *
 * @param int $id The ID of the property.
 *
 * @return array An associative array containing:
 *   - 'label': The label of the source with the highest rating.
 *   - 'rating': The highest rating value rounded to one decimal point.
 */
function highest_rating($id) {
    // Retrieve the ratings and round them to one decimal point
    $tripadvisor_rating = get_field('sykes_property_trip_advisor_rating', $id);
    $board_rating = get_field('sykes_property_tourist_board_rating', $id);
    $sykes_rating = get_field('sykes_property_rating', $id);

    // Ensure ratings are numeric before rounding
    $tripadvisor_rating = is_numeric($tripadvisor_rating) ? round(floatval($tripadvisor_rating), 1) : 0;
    $board_rating = is_numeric($board_rating) ? round(floatval($board_rating), 1) : 0;
    $sykes_rating = is_numeric($sykes_rating) ? round(floatval($sykes_rating), 1) : 0;

    // Create an associative array to store the ratings with their labels
    $ratings = [
        'Trip Advisor' => $tripadvisor_rating,
        'Board Rating' => $board_rating,
        'Sykes' => $sykes_rating
    ];

    // Find the highest rating
    $highest_label = '';
    $highest_value = 0;

    foreach ($ratings as $label => $value) {
        if ($value > $highest_value) {
            $highest_value = $value;
            $highest_label = $label;
        }
    }

    // Return the highest rating and its label
    return [
        'label' => $highest_label,
        'rating' => $highest_value
    ];
}


/**
 * Generates and echoes or returns a full affiliate link by appending a given path to the Sykes base URL.
 *
 * @param string $path The full URL path to be appended to the base URL.
 * @param bool $echo Whether to echo the link. Defaults to true.
 *
 * @return string|null The generated affiliate link if $echo is false, otherwise null.
 */
function set_affiliate_link($path, $echo = true) {
    // Define the Sykes base URL
    $affiliate_base_url_1 = 'https://sykescottages.co.uk';  // First base URL

    // Hard-coded parameters for the Sykes URL
    $param = '?rfx=11156&inrfx=11156';

    // Combine the base URL with the path and append the parameters
    $full_link = $affiliate_base_url_1 . $path . $param;

    // Echo the link if required, otherwise return it
    if ($echo) {
        echo $full_link;
        return null;
    } else {
        return $full_link;
    }
}


/**
 * Add custom body class if 'notice' field is set in ACF options on the homepage.
 *
 * This function checks if the 'notice' field under the 'general' options in ACF
 * is set and adds a 'notice' class to the body tag on the homepage if it is.
 *
 * @param array $classes An array of body class names.
 * @return array Modified array of body class names.
 */
function add_notice_body_class( $classes ) {
    // Only apply this to the homepage
    if ( is_front_page()) {
        // Retrieve the 'misc' field from ACF options
        $misc = get_field('general', 'option')['misc'];

        // Check if the 'notice' field is set and not empty
        if ( ! empty( $misc['notice'] ) ) {
            // Add 'notice' class to the body classes array
            $classes[] = 'notice';
        }
    }

    return $classes;
}
add_filter( 'body_class', 'add_notice_body_class' );


function enqueue_flatpickr_assets() {
    // Flatpickr CSS
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.13');

    // Flatpickr JS
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.13', true);
}
add_action('wp_enqueue_scripts', 'enqueue_flatpickr_assets');