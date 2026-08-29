<?php
/**
 * Aaroh Care theme bootstrap.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AAROH_THEME_VERSION', '1.0.3');

require_once get_template_directory() . '/inc/fields.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/cpts.php';
require_once get_template_directory() . '/inc/seed.php';
require_once get_template_directory() . '/inc/appointments.php';
require_once get_template_directory() . '/inc/email.php';
require_once get_template_directory() . '/inc/smtp.php';

add_action('after_setup_theme', 'aaroh_setup');
function aaroh_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 96,
        'width'       => 96,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
}

add_action('wp_enqueue_scripts', 'aaroh_enqueue_assets');
function aaroh_enqueue_assets()
{
    wp_enqueue_style(
        'aaroh-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        '5.3.3'
    );
    wp_enqueue_style(
        'aaroh-theme',
        get_template_directory_uri() . '/assets/css/styles.css',
        ['bootstrap'],
        AAROH_THEME_VERSION
    );
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.3',
        true
    );
    wp_enqueue_script(
        'aaroh-app',
        get_template_directory_uri() . '/assets/js/app.js',
        ['bootstrap'],
        AAROH_THEME_VERSION,
        true
    );
    wp_localize_script('aaroh-app', 'aarohAppointment', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('aaroh_appointment'),
    ]);
}

add_action('wp_enqueue_scripts', 'aaroh_dequeue_wp_chrome', 100);
function aaroh_dequeue_wp_chrome()
{
    if (is_admin()) {
        return;
    }
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}

add_filter('style_loader_tag', 'aaroh_font_preconnect_integrity', 10, 2);
function aaroh_font_preconnect_integrity($html, $handle)
{
    if ('bootstrap' === $handle && false !== strpos($html, 'bootstrap.min.css')) {
        return str_replace(
            "media='all'",
            "media='all' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous'",
            $html
        );
    }
    return $html;
}

add_filter('script_loader_tag', 'aaroh_bootstrap_script_integrity', 10, 3);
function aaroh_bootstrap_script_integrity($tag, $handle, $src)
{
    if ('bootstrap' === $handle) {
        return '<script src="' . esc_url($src) . '" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>' . "\n";
    }
    return $tag;
}

add_action('wp_head', 'aaroh_font_preconnect', 1);
function aaroh_font_preconnect()
{
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}

add_action('wp_head', 'aaroh_head_meta', 5);
function aaroh_head_meta()
{
    if (!is_front_page()) {
        return;
    }

    $desc = aaroh_get('seo_description');
    $og   = aaroh_asset('img/og-clinic-care.svg');

    echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    echo '<meta name="keywords" content="' . esc_attr(aaroh_get('seo_keywords')) . '">' . "\n";
    echo '<meta name="robots" content="index, follow">' . "\n";
    echo '<meta name="author" content="' . esc_attr(aaroh_get('seo_author')) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(aaroh_get('og_title')) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr(aaroh_get('og_description')) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($og) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr(aaroh_get('twitter_title')) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr(aaroh_get('twitter_description')) . '">' . "\n";

    $services = [];
    $query    = aaroh_query_ordered('aaroh_service');
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $services[] = [
                '@type' => 'MedicalTherapy',
                'name'  => get_the_title(),
            ];
        }
        wp_reset_postdata();
    }

    $schema = [
        '@context'          => 'https://schema.org',
        '@type'             => 'MedicalClinic',
        'name'              => aaroh_get('jsonld_name'),
        'description'       => aaroh_get('jsonld_description'),
        'medicalSpecialty'  => aaroh_get('jsonld_specialty'),
        'image'             => $og,
        'openingHours'      => aaroh_get('jsonld_hours'),
        'availableChannel'  => [
            '@type'      => 'ServiceChannel',
            'serviceUrl' => '#appointment',
        ],
        'employee'          => [
            '@type'            => 'Physician',
            'name'             => aaroh_get('physician_name'),
            'honorificSuffix'  => aaroh_get('physician_suffix'),
        ],
        'availableService'  => $services,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

add_action('wp', 'aaroh_disable_wpautop_on_front');
function aaroh_disable_wpautop_on_front()
{
    if (is_front_page()) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_excerpt', 'wpautop');
    }
}

add_filter('document_title_parts', 'aaroh_document_title');
function aaroh_document_title($parts)
{
    if (is_front_page()) {
        $parts['title'] = aaroh_get('seo_title');
        unset($parts['site']);
        unset($parts['tagline']);
    }
    return $parts;
}

add_filter('document_title_separator', 'aaroh_title_separator');
function aaroh_title_separator()
{
    return '|';
}

add_action('after_switch_theme', 'aaroh_seed_content');

add_action('init', 'aaroh_maybe_seed_articles_page');
function aaroh_maybe_seed_articles_page()
{
    if (get_option('aaroh_articles_page_seeded')) {
        return;
    }
    aaroh_seed_articles_page();
    update_option('aaroh_articles_page_seeded', 1);
}
