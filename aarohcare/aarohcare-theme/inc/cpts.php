<?php
/**
 * Custom post types and admin meta.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'aaroh_register_cpts');
function aaroh_register_cpts()
{
    $shared = [
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'has_archive'         => false,
        'rewrite'             => false,
        'show_in_rest'        => false,
    ];

    register_post_type('aaroh_service', array_merge($shared, [
        'labels'        => aaroh_cpt_labels('Service', 'Services'),
        'menu_icon'     => 'dashicons-heart',
        'supports'      => ['title', 'editor', 'page-attributes'],
        'menu_position' => 21,
    ]));

    register_post_type('aaroh_benefit', array_merge($shared, [
        'labels'        => aaroh_cpt_labels('Benefit', 'Why Us'),
        'menu_icon'     => 'dashicons-star-filled',
        'supports'      => ['title', 'excerpt', 'page-attributes'],
        'menu_position' => 22,
    ]));

    register_post_type('aaroh_gallery', array_merge($shared, [
        'labels'        => aaroh_cpt_labels('Gallery item', 'Gallery'),
        'menu_icon'     => 'dashicons-format-gallery',
        'supports'      => ['title', 'excerpt', 'thumbnail', 'page-attributes'],
        'menu_position' => 23,
    ]));

    register_post_type('aaroh_faq', array_merge($shared, [
        'labels'        => aaroh_cpt_labels('FAQ', 'FAQ'),
        'menu_icon'     => 'dashicons-editor-help',
        'supports'      => ['title', 'editor', 'page-attributes'],
        'menu_position' => 24,
    ]));

    register_post_type('aaroh_appointment', array_merge($shared, [
        'labels'        => aaroh_cpt_labels('Appointment', 'Appointments'),
        'menu_icon'     => 'dashicons-calendar-alt',
        'supports'      => ['title'],
        'menu_position' => 25,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
    ]));
}

function aaroh_cpt_labels($singular, $plural)
{
    return [
        'name'               => $plural,
        'singular_name'      => $singular,
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New ' . $singular,
        'edit_item'          => 'Edit ' . $singular,
        'new_item'           => 'New ' . $singular,
        'view_item'          => 'View ' . $singular,
        'search_items'       => 'Search ' . $plural,
        'not_found'          => 'No items found',
        'not_found_in_trash' => 'No items found in Trash',
        'all_items'          => $plural,
        'menu_name'          => $plural,
    ];
}

add_action('add_meta_boxes', 'aaroh_add_meta_boxes');
function aaroh_add_meta_boxes()
{
    add_meta_box(
        'aaroh_service_chip',
        'Service chip',
        'aaroh_render_chip_metabox',
        'aaroh_service',
        'side'
    );
    add_meta_box(
        'aaroh_gallery_fallback',
        'Default theme image (if no featured image)',
        'aaroh_render_gallery_metabox',
        'aaroh_gallery',
        'side'
    );
    add_meta_box(
        'aaroh_appointment_details',
        'Appointment details',
        'aaroh_render_appointment_metabox',
        'aaroh_appointment',
        'normal'
    );
}

function aaroh_render_chip_metabox($post)
{
    wp_nonce_field('aaroh_save_service', 'aaroh_service_nonce');
    $chip = get_post_meta($post->ID, '_aaroh_chip', true);
    echo '<p><label for="aaroh_chip">Chip text</label></p>';
    echo '<input type="text" id="aaroh_chip" name="aaroh_chip" class="widefat" value="' . esc_attr($chip) . '">';
}

function aaroh_render_gallery_metabox($post)
{
    wp_nonce_field('aaroh_save_gallery', 'aaroh_gallery_nonce');
    $file = get_post_meta($post->ID, '_aaroh_image', true);
    $alt  = get_post_meta($post->ID, '_aaroh_image_alt', true);
    echo '<p><label for="aaroh_image">Asset path (e.g. img/service-panchakarma.svg)</label></p>';
    echo '<input type="text" id="aaroh_image" name="aaroh_image" class="widefat" value="' . esc_attr($file) . '">';
    echo '<p><label for="aaroh_image_alt">Image alt text</label></p>';
    echo '<input type="text" id="aaroh_image_alt" name="aaroh_image_alt" class="widefat" value="' . esc_attr($alt) . '">';
}

function aaroh_render_appointment_metabox($post)
{
    $fields = [
        '_aaroh_phone'  => 'Phone',
        '_aaroh_email'  => 'Email',
        '_aaroh_issue'  => 'Health issue',
        '_aaroh_date'   => 'Preferred date',
        '_aaroh_time'   => 'Preferred time',
        '_aaroh_status' => 'Status',
    ];
    echo '<table class="form-table">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr><th>' . esc_html($label) . '</th><td>' . nl2br(esc_html($value)) . '</td></tr>';
    }
    echo '</table>';
}

add_action('save_post_aaroh_service', 'aaroh_save_service_meta');
function aaroh_save_service_meta($post_id)
{
    if (!isset($_POST['aaroh_service_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aaroh_service_nonce'])), 'aaroh_save_service')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['aaroh_chip'])) {
        update_post_meta($post_id, '_aaroh_chip', sanitize_text_field(wp_unslash($_POST['aaroh_chip'])));
    }
}

add_action('save_post_aaroh_gallery', 'aaroh_save_gallery_meta');
function aaroh_save_gallery_meta($post_id)
{
    if (!isset($_POST['aaroh_gallery_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aaroh_gallery_nonce'])), 'aaroh_save_gallery')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['aaroh_image'])) {
        update_post_meta($post_id, '_aaroh_image', sanitize_text_field(wp_unslash($_POST['aaroh_image'])));
    }
    if (isset($_POST['aaroh_image_alt'])) {
        update_post_meta($post_id, '_aaroh_image_alt', sanitize_text_field(wp_unslash($_POST['aaroh_image_alt'])));
    }
}

add_filter('manage_aaroh_service_posts_columns', 'aaroh_service_columns');
function aaroh_service_columns($columns)
{
    $columns['aaroh_chip']  = 'Chip';
    $columns['menu_order']  = 'Order';
    return $columns;
}

add_action('manage_aaroh_service_posts_custom_column', 'aaroh_service_column_content', 10, 2);
function aaroh_service_column_content($column, $post_id)
{
    if ('aaroh_chip' === $column) {
        echo esc_html(get_post_meta($post_id, '_aaroh_chip', true));
    }
    if ('menu_order' === $column) {
        echo (int) get_post_field('menu_order', $post_id);
    }
}

add_filter('manage_aaroh_appointment_posts_columns', 'aaroh_appointment_columns');
function aaroh_appointment_columns($columns)
{
    return [
        'cb'          => $columns['cb'],
        'title'       => 'Patient',
        'aaroh_email' => 'Email',
        'aaroh_phone' => 'Phone',
        'aaroh_date'  => 'Date',
        'aaroh_time'  => 'Time',
        'date'        => 'Submitted',
    ];
}

add_action('manage_aaroh_appointment_posts_custom_column', 'aaroh_appointment_column_content', 10, 2);
function aaroh_appointment_column_content($column, $post_id)
{
    $map = [
        'aaroh_email' => '_aaroh_email',
        'aaroh_phone' => '_aaroh_phone',
        'aaroh_date'  => '_aaroh_date',
        'aaroh_time'  => '_aaroh_time',
    ];
    if (isset($map[$column])) {
        echo esc_html(get_post_meta($post_id, $map[$column], true));
    }
}

add_filter('private_title_format', 'aaroh_appointment_private_title', 10, 2);
function aaroh_appointment_private_title($format, $post)
{
    if ($post && 'aaroh_appointment' === $post->post_type) {
        return '%s';
    }
    return $format;
}

add_filter('manage_edit-aaroh_service_sortable_columns', 'aaroh_order_sortable');
add_filter('manage_edit-aaroh_benefit_sortable_columns', 'aaroh_order_sortable');
add_filter('manage_edit-aaroh_gallery_sortable_columns', 'aaroh_order_sortable');
add_filter('manage_edit-aaroh_faq_sortable_columns', 'aaroh_order_sortable');
function aaroh_order_sortable($columns)
{
    $columns['menu_order'] = 'menu_order';
    return $columns;
}
