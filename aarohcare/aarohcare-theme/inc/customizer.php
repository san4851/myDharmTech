<?php
/**
 * Theme Customizer.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('customize_register', 'aaroh_customize_register');
function aaroh_customize_register($wp_customize)
{
    $wp_customize->add_panel('aaroh_content', [
        'title'       => __('Aaroh Care Content', 'aarohcare'),
        'description' => __('Edit homepage copy. Lists (services, why us, gallery, FAQ) are edited from their own admin menus.', 'aarohcare'),
        'priority'    => 10,
    ]);

    $priority = 10;
    foreach (aaroh_customizer_sections() as $id => $title) {
        $wp_customize->add_section($id, [
            'title'    => $title,
            'panel'    => 'aaroh_content',
            'priority' => $priority,
        ]);
        $priority += 10;
    }

    foreach (aaroh_fields() as $id => $field) {
        $type = $field['type'];
        $sanitize = 'sanitize_text_field';
        if ('textarea' === $type) {
            $sanitize = 'sanitize_textarea_field';
        } elseif ('email' === $type) {
            $sanitize = 'sanitize_email';
        }

        $wp_customize->add_setting($id, [
            'default'           => $field['default'],
            'sanitize_callback' => $sanitize,
            'transport'         => 'refresh',
        ]);

        $control_type = $type;
        if ('email' === $type) {
            $control_type = 'email';
        }

        $wp_customize->add_control($id, [
            'label'   => $field['label'],
            'section' => $field['section'],
            'type'    => $control_type,
        ]);
    }
}
