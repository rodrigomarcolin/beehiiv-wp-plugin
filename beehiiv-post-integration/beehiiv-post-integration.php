<?php
/*
Plugin Name: Beehiiv API Integration
Description: A WordPress plugin for integrating with Beehiiv API.
Version: 1.0.0
Author: Rodrigo Marcolin (with great help from CHAT GPT)
*/

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die( 'We\'re sorry, but you can not directly access this file.' );
}

if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}
/*
* Activate the plugin.
 */
// Register Custom Post Type
function beehiiv_setup_post_type() {

    $labels = array(
        'name'                  => _x( 'Beehiiv Posts', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Beehiiv Post', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Beehiiv Posts', 'text_domain' ),
        'name_admin_bar'        => __( 'Beehiiv Posts', 'text_domain' ),
    );

    $args = array(
        'label'                 => __( 'Beehiiv Post', 'text_domain' ),
        'description'           => __( 'BeeHiiv Post', 'text_domain' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', '' ),
        'taxonomies'            => array( 'category', 'post_tag' ),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-buddicons-replies',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
    );

    register_post_type( 'beehiiv_post', $args );
}

add_action( 'init', 'beehiiv_setup_post_type', 0 );
function beehiv_post_integration_activate() {
    // Trigger our function that registers the beehiiv post type plugin.
    beehiiv_setup_post_type();
    // Clear the permalinks after the post type has been registered.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'beehiv_post_integration_activate' );
register_activation_hook( __FILE__, 'beehiiv_integration_setup_settings_page' );
// Register the settings page
function beehiiv_integration_setup_settings_page() {
    add_options_page(
        'BeeHiiv Integration Settings',
        'BeeHiiv API Integration',
        'manage_options',
        'beehiiv-api-integration-settings',
        'beehiiv_api_integration_render_settings_page'
    );
}
add_action('admin_menu', 'beehiiv_integration_setup_settings_page');

// Render the settings' page
function beehiiv_api_integration_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>BeeHiiv API Integration Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('beehiiv-api-integration-settings');
            do_settings_sections('beehiiv-api-integration-settings');
            submit_button('Salvar');
            ?>
        </form>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="integrar_beehiiv">
            <input class="button button-primary" type="submit" value="Trigger Integration">
        </form>

    </div>
    <?php
}
add_action( 'admin_post_integrar_beehiiv', 'beehiiv_api_integration_process_data' );
// Register the API key setting
function beehiiv_api_integration_register_settings() {
    add_settings_section(
        'beehiiv-api-integration-api-key',
        'API Key',
        'beehiiv_api_integration_render_api_key_section',
        'beehiiv-api-integration-settings'
    );

    add_settings_field(
        'beehiiv-api-integration-api-key-field',
        'API Key',
        'beehiiv_api_integration_render_api_key_field',
        'beehiiv-api-integration-settings',
        'beehiiv-api-integration-api-key'
    );

    add_settings_section(
        'beehiiv-api-integration-publication-id',
        'Publication ID',
        'beehiiv_api_integration_render_publication_id_section',
        'beehiiv-api-integration-settings'
    );

    add_settings_field(
        'beehiiv-api-integration-publication-id-field',
        'Publication ID',
        'beehiiv_api_integration_render_publication_id_field',
        'beehiiv-api-integration-settings',
        'beehiiv-api-integration-publication-id'
    );


    register_setting(
        'beehiiv-api-integration-settings',
        'beehiiv-api-integration-api-key'
    );

    register_setting(
        'beehiiv-api-integration-settings',
        'beehiiv-api-integration-publication-id'
    );
}
add_action('admin_init', 'beehiiv_api_integration_register_settings');

function upload_image_from_url($image_url) {
    // Check if the image URL is valid
    if (filter_var($image_url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    // Get the file name and extension from the image URL
    $file_name = basename($image_url);
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

    // Generate a unique file name for the uploaded image
    $file_name_new = uniqid() . '.' . $file_extension;

    // Get the wp-content/uploads directory path
    $upload_dir = wp_upload_dir();

    // Define the file path and URL for the uploaded image
    $file_path = $upload_dir['path'] . '/' . $file_name_new;
    $file_url = $upload_dir['url'] . '/' . $file_name_new;

    // Download the image from the URL and save it to the file path
    $image_data = file_get_contents($image_url);
    file_put_contents($file_path, $image_data);

    // Create the attachment array for the uploaded image
    $attachment = array(
        'post_mime_type' => wp_check_filetype($file_name_new)['type'],
        'post_title' => sanitize_file_name($file_name_new),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    // Insert the attachment into the WordPress media library
    $attachment_id = wp_insert_attachment($attachment, $file_path);

    // Return the attachment ID
    return $attachment_id;
}

// Render the API key section
function beehiiv_api_integration_render_api_key_section() {
    echo '<p>Type API key below.</p>';
}

function beehiiv_api_integration_render_publication_id_section() {
    echo '<p>Type the Publication ID below.</p>';
}

// Render the API key field
function beehiiv_api_integration_render_api_key_field() {
    $api_key = get_option('beehiiv-api-integration-api-key');
    echo '<input type="text" name="beehiiv-api-integration-api-key" value="' . esc_attr($api_key) . '" />';
}

// Render the API key field
function beehiiv_api_integration_render_publication_id_field() {
    $publication_id = get_option('beehiiv-api-integration-publication-id');
    echo '<input type="text" name="beehiiv-api-integration-publication-id" value="' . esc_attr($publication_id) . '" />';
}

function beehiiv_api_integration_fetch_single_post_data($post_id) {
    $api_key = get_option('beehiiv-api-integration-api-key');
    $publication_id = get_option('beehiiv-api-integration-publication-id');

    $url = 'https://api.beehiiv.com/v2/publications/' . $publication_id . '/posts' . '/' . $post_id . '?expand=free_rss_content';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json'
    );

    $args = array(
        'headers' => $headers
    );

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);

    $data = json_decode($body, true);

    return $data['data'];
}


function add_custom_cron_interval( $schedules ) {
    $schedules['two_minutes'] = array(
        'interval' => 120,
        'display'  => __( 'Every Five Seconds' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'add_custom_cron_interval' );


// Schedule the event to run every 5 minutes
add_action('beehiiv_integration_cron_hook', 'beehiiv_api_integration_process_data');
wp_schedule_event(time(), 'two_minutes', 'beehiiv_integration_cron_hook');

// Fetch data from the API
function beehiiv_api_integration_fetch_data() {
    $api_key = get_option('beehiiv-api-integration-api-key');
    $publication_id = get_option('beehiiv-api-integration-publication-id');

    $url = 'https://api.beehiiv.com/v2/publications/' . $publication_id . '/posts?limit=100&status=confirmed';
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json'
    );

    $args = array(
        'headers' => $headers
    );

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);

    $data = json_decode($body, true);

    return $data['data'];
}

// Process the data and create or update beehiiv posts
function beehiiv_api_integration_process_data() {
    $data = beehiiv_api_integration_fetch_data();

    if (!$data) {
        return;
    }

    foreach ($data as $item) {
        $existing_post_id = beehiiv_api_integration_find_existing_post($item);

        if ( ! $existing_post_id ) {
            $post_dt = beehiiv_api_integration_fetch_single_post_data($item['id']);
            beehiiv_api_integration_create_post($post_dt);
        } 	
    }
}

// Find an existing post by its external ID
function beehiiv_api_integration_find_existing_post($item) {
    $query = new WP_Query(array(
        'post_type' => 'beehiiv_post',
        'meta_key' => 'external_id',
        'meta_value' => $item['id']
    ));
	
    if ($item['publish_date'] > time()) {
    	return true;
    }
    if ($query->have_posts()) {
        $post = $query->posts[0];
        return $post->ID;
    } else {
        return false;
    }
}


// Create a new post with the given data
function beehiiv_api_integration_create_post($data) {
    
    $post_data = array(
        'post_title' => $data['title'],
        'post_content' => $data['content']['free']['rss'],
        'post_excerpt' => $data['subtitle'],
        'post_date' => wp_date('Y-m-d H:i:s', $data['publish_date']),
        'post_status' => 'publish',
        'post_type' => 'beehiiv_post'
    );
    
    $post_id = wp_insert_post($post_data);
    set_post_thumbnail( $post_id , upload_image_from_url($data['thumbnail_url']) );
    update_post_meta($post_id, 'external_id', $data['id']);
}