<?php
/**
 * Theme helpers.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

function aaroh_get($key)
{
    $fields  = aaroh_fields();
    $default = isset($fields[$key]['default']) ? $fields[$key]['default'] : '';
    return get_theme_mod($key, $default);
}

function aaroh_lines($key)
{
    $text  = aaroh_get($key);
    $lines = preg_split("/\r\n|\n|\r/", (string) $text);
    $out   = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function aaroh_asset($relative)
{
    return get_template_directory_uri() . '/assets/' . ltrim($relative, '/');
}

function aaroh_section_url($hash)
{
    $hash = ltrim($hash, '#');
    if (is_front_page()) {
        return '#' . $hash;
    }
    return home_url('/#' . $hash);
}

function aaroh_articles_url()
{
    $page = get_page_by_path('articles');
    if ($page instanceof WP_Post) {
        return get_permalink($page);
    }
    return home_url('/articles/');
}

function aaroh_is_articles_context()
{
    if (is_front_page()) {
        return false;
    }

    if (is_page_template('page-templates/article-home-page.php') || is_page('articles')) {
        return true;
    }

    if (is_singular('post') || is_home() || is_category() || is_tag() || is_date() || is_author()) {
        return true;
    }

    return false;
}

function aaroh_nav_link_class($item)
{
    $class = 'nav-link';
    if ('articles' === $item && aaroh_is_articles_context()) {
        $class .= ' active';
    }
    return $class;
}

function aaroh_logo_img($args = [])
{
    $defaults = [
        'class'  => '',
        'width'  => 56,
        'height' => 56,
        'alt'    => aaroh_get('logo_alt'),
    ];
    $args = wp_parse_args($args, $defaults);

    if (has_custom_logo()) {
        $logo_id = (int) get_theme_mod('custom_logo');
        echo wp_get_attachment_image($logo_id, 'full', false, [
            'class'  => $args['class'],
            'width'  => $args['width'],
            'height' => $args['height'],
            'alt'    => $args['alt'],
        ]);
        return;
    }

    printf(
        '<img src="%s" alt="%s"%s width="%d" height="%d">',
        esc_url(aaroh_asset('img/AarohCareLogo.jpeg')),
        esc_attr($args['alt']),
        $args['class'] ? ' class="' . esc_attr($args['class']) . '"' : '',
        (int) $args['width'],
        (int) $args['height']
    );
}

function aaroh_clinic_email()
{
    $smtp = aaroh_smtp_settings();
    if (!empty($smtp['notify_email'])) {
        return $smtp['notify_email'];
    }

    $email = aaroh_get('clinic_email');
    if ($email && is_email($email)) {
        return $email;
    }

    return get_option('admin_email');
}

function aaroh_appointment_success_message($name, $date, $time)
{
    $tpl = aaroh_get('appt_success');
    return strtr($tpl, [
        '{name}' => $name,
        '{date}' => $date,
        '{time}' => $time,
    ]);
}

function aaroh_query_ordered($post_type)
{
    return new WP_Query([
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => [
            'menu_order' => 'ASC',
            'date'       => 'ASC',
        ],
        'no_found_rows'  => true,
    ]);
}

function aaroh_gallery_image($post_id)
{
    if (has_post_thumbnail($post_id)) {
        echo get_the_post_thumbnail($post_id, 'large', [
            'class'   => 'img-fluid',
            'loading' => 'lazy',
            'width'   => 640,
            'height'  => 480,
        ]);
        return;
    }

    $file = get_post_meta($post_id, '_aaroh_image', true);
    if (!$file) {
        $file = 'img/service-panchakarma.svg';
    }

    $alt = get_post_meta($post_id, '_aaroh_image_alt', true);
    if (!$alt) {
        $alt = get_the_title($post_id);
    }

    printf(
        '<img src="%s" class="img-fluid" alt="%s" loading="lazy" width="640" height="480">',
        esc_url(aaroh_asset($file)),
        esc_attr($alt)
    );
}
