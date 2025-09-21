<?php
/**
 * Class containing helper functions.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
$dotenv->load();

// Load the Sykes API
require_once('SykesAPI.php');

class Classes {

    /**
     * Get HTML content from a file.
     *
     * @param string $path Path to the HTML file relative to the plugin directory.
     * @param array $variables Variables to be passed to the HTML file.
     * @param string $views_dir Directory name where HTML files are located.
     * @param bool $load Whether to directly include the file or capture its output.
     * @return string HTML content.
     */
    public static function get_html($path = '', $variables = array(), $views_dir = 'views', $load = true) {
        // Directory path to the specified directory
        $dir = plugin_dir_path(__FILE__) . $views_dir . '/';

        locate_template($path, false, false);

        // Extract variables to be passed to the HTML file
        extract($variables);

        if ($load) {
            return require($dir . ltrim($path, '/')); // Ensure $path doesn't start with a slash
        }

        ob_start();
        require($dir . ltrim($path, '/')); // Ensure $path doesn't start with a slash
        $file = ob_get_contents(); 
        ob_end_clean();

        return $file;
    }
}