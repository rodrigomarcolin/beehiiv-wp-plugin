<?php
/*
Plugin Name: beehiiv API Integration
Plugin URI: https://example.com/
Description: A beehiiv WordPress plugin for integrating with an API.
Version: 1.0.0
Author: Your Name
Author URI: https://example.com/
*/

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die( 'We\'re sorry, but you can not directly access this file.' );
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
        'description'           => __( 'Post da BeeHiiv', 'text_domain' ),
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
        'Configurações de Integração com a BeeHiiv',
        'Integração à BeeHiiv via API',
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
        <h1>Configurações da Integração da Beehiiv via API</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('beehiiv-api-integration-settings');
            do_settings_sections('beehiiv-api-integration-settings');
            submit_button('Salvar');
            ?>
        </form>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="integrar_beehiiv">
            <input type="submit" value="Rodar Integração">
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

// Render the API key section
function beehiiv_api_integration_render_api_key_section() {
    echo '<p>Insira a chave da API abaixo.</p>';
}

function beehiiv_api_integration_render_publication_id_section() {
    echo '<p>Insira o ID da Publication abaixo.</p>';
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

// Fetch data from the API
function beehiiv_api_integration_fetch_data() {
    $api_key = get_option('beehiiv-api-integration-api-key');
    $publication_id = get_option('beehiiv-api-integration-publication-id');

    $url = 'https://api.beehiiv.com/v2/publications/' . $publication_id . '/posts?limit=100';

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

    return $data;
}

// Process the data and create or update beehiiv posts
function beehiiv_api_integration_process_data() {
    $data = beehiiv_api_integration_fetch_data();

    if (!$data) {
        return;
    }

    foreach ($data as $item) {
        $existing_post_id = beehiiv_api_integration_find_existing_post($item['id']);

        if ( ! $existing_post_id ) {
            beehiiv_api_integration_create_post($item);
        } 
    }
}

// Find an existing post by its external ID
function beehiiv_api_integration_find_existing_post($external_id) {
    $query = new WP_Query(array(
        'post_type' => 'beehiiv_post',
        'meta_key' => 'external_id',
        'meta_value' => $external_id
    ));

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
        'post_content' => $data['subtitle'],
        'post_status' => 'publish',
        'post_type' => 'beehiiv_post'
    );

    $post_id = wp_insert_post($post_data);

    update_post_meta($post_id, 'external_id', $data['id']);
}