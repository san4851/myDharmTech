<?php
/**
 * Seed homepage CPT content from the approved template.
 *
 * @package AarohCare
 */

if (!defined('ABSPATH')) {
    exit;
}

function aaroh_seed_content()
{
    aaroh_seed_services();
    aaroh_seed_benefits();
    aaroh_seed_gallery();
    aaroh_seed_faqs();
    aaroh_seed_articles_page();
}

function aaroh_seed_articles_page()
{
    $page = get_page_by_path('articles');
    if ($page instanceof WP_Post) {
        update_post_meta($page->ID, '_wp_page_template', 'page-templates/article-home-page.php');
        return;
    }

    $page_id = aaroh_insert_seed_post([
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'Articles',
        'post_name'    => 'articles',
        'post_content' => '',
    ]);

    if (!is_wp_error($page_id) && $page_id) {
        update_post_meta($page_id, '_wp_page_template', 'page-templates/article-home-page.php');
    }
}

function aaroh_cpt_is_empty($post_type)
{
    $q = new WP_Query([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);
    return !$q->have_posts();
}

function aaroh_insert_seed_post($args)
{
    return wp_insert_post(wp_slash($args), true);
}

function aaroh_seed_services()
{
    if (!aaroh_cpt_is_empty('aaroh_service')) {
        return;
    }

    $items = [
        ['Hormonal Disorders', 'Consultation support for hormonal imbalance with individualized case-based care.', 'Hormonal health'],
        ['Diabetes', 'Holistic support for better long-term balance, lifestyle care, and overall wellbeing.', 'Metabolic care'],
        ['Obesity', 'Care guided by constitutional understanding, habits, and lifestyle-related health patterns.', 'Weight wellness'],
        ['Thyroid Disorders', 'Personalized homoeopathic guidance for thyroid-related concerns and associated symptoms.', 'Thyroid care'],
        ['PCOS / PCOD', 'Compassionate care focused on cycle health, hormonal balance, and overall wellbeing.', 'Cycle support'],
        ["Women's Health", 'Individualized support for a wide range of women\'s health concerns with a holistic lens.', 'Holistic care'],
        ['Skin & Hair Concerns', 'Consultations for skin and hair concerns with attention to root patterns and overall health.', 'Skin and hair'],
        ['Allergies & Respiratory Disorders', 'Support for recurring allergies, respiratory sensitivity, and breathing-related discomfort.', 'Respiratory care'],
        ['Digestive Disorders', 'Care for digestive imbalance with an individualized approach to symptoms and constitution.', 'Digestive health'],
        ['Migraine & Lifestyle Disorders', 'Consultation support for recurring headaches, stress-linked patterns, and lifestyle concerns.', 'Lifestyle support'],
        ['Acute & Chronic Illnesses', 'Care for both short-term and long-standing illnesses with a person-centered perspective.', 'Acute and chronic'],
        ['General Health & Wellness', 'Support for patients seeking balanced health, preventive care, and better day-to-day wellbeing.', 'Wellness care'],
    ];

    $order = 0;
    foreach ($items as $item) {
        $id = aaroh_insert_seed_post([
            'post_type'    => 'aaroh_service',
            'post_status'  => 'publish',
            'post_title'   => $item[0],
            'post_content' => $item[1],
            'menu_order'   => $order++,
        ]);
        if (!is_wp_error($id)) {
            update_post_meta($id, '_aaroh_chip', $item[2]);
        }
    }
}

function aaroh_seed_benefits()
{
    if (!aaroh_cpt_is_empty('aaroh_benefit')) {
        return;
    }

    $items = [
        ['Whole-person approach', 'Consultations are centered on understanding symptoms, lifestyle, and the individual behind the illness.'],
        ['Online by appointment', 'Patients can request online consultations in advance for a more organized and convenient experience.'],
        ['Compassionate care', 'The consultation style is calm, attentive, and designed to support steady healing with clarity and trust.'],
        ['Structured consultation hours', 'Appointments are available Monday to Saturday from 4 PM to 7 PM by prior booking.'],
    ];

    $order = 0;
    foreach ($items as $item) {
        aaroh_insert_seed_post([
            'post_type'    => 'aaroh_benefit',
            'post_status'  => 'publish',
            'post_title'   => $item[0],
            'post_excerpt' => $item[1],
            'menu_order'   => $order++,
        ]);
    }
}

function aaroh_seed_gallery()
{
    if (!aaroh_cpt_is_empty('aaroh_gallery')) {
        return;
    }

    $items = [
        [
            'Hormonal & Thyroid Support',
            'Thoughtful consultation for hormonal concerns, thyroid imbalance, and related symptoms.',
            'img/service-panchakarma.svg',
            'Chronic care consultation at Aaroh Care clinic',
        ],
        [
            "Women's Health & PCOS / PCOD",
            'Compassionate care for women\'s wellbeing with attention to cycle and hormonal health.',
            'img/service-abhyanga.svg',
            'Calm holistic consultation room at Aaroh Care',
        ],
        [
            'Digestive & Lifestyle Care',
            'Support for digestive imbalance, migraine patterns, and lifestyle-linked discomfort.',
            'img/service-digestive-care.svg',
            'Digestive and immunity care consultation service',
        ],
        [
            'Allergy & Respiratory Care',
            'Consultation support for recurring allergies, respiratory sensitivity, and seasonal discomfort.',
            'img/service-stress-relief.svg',
            'Stress relief and sleep support consultation service',
        ],
        [
            'Skin & Hair Concerns',
            'Individualized care for skin and hair concerns with a root-cause oriented approach.',
            'img/service-joint-care.svg',
            'Child health and recurring illness clinic service',
        ],
        [
            'General Health & Wellness',
            'Balanced support for acute and chronic illness care as well as everyday wellbeing.',
            'img/service-womens-wellness.svg',
            "Women's wellness consultation service at Aaroh Care",
        ],
    ];

    $order = 0;
    foreach ($items as $item) {
        $id = aaroh_insert_seed_post([
            'post_type'    => 'aaroh_gallery',
            'post_status'  => 'publish',
            'post_title'   => $item[0],
            'post_excerpt' => $item[1],
            'menu_order'   => $order++,
        ]);
        if (!is_wp_error($id)) {
            update_post_meta($id, '_aaroh_image', $item[2]);
            update_post_meta($id, '_aaroh_image_alt', $item[3]);
        }
    }
}

function aaroh_seed_faqs()
{
    if (!aaroh_cpt_is_empty('aaroh_faq')) {
        return;
    }

    $items = [
        [
            'What can patients book through this page?',
            'Patients can request online consultations for hormonal disorders, diabetes, obesity, thyroid disorders, PCOS/PCOD, women\'s health, skin and hair concerns, allergies, respiratory disorders, digestive disorders, migraine, lifestyle disorders, acute and chronic illnesses, and general wellness.',
        ],
        [
            'Is the appointment request immediately confirmed?',
            'Online consultations are available by prior appointment, Monday to Saturday, between 4 PM and 7 PM.',
        ],
        [
            'Why is this page SEO friendly?',
            'The clinic focuses on holistic, compassionate, and individualized homoeopathic care with the aim of understanding the whole person, not just the illness.',
        ],
    ];

    $order = 0;
    foreach ($items as $item) {
        aaroh_insert_seed_post([
            'post_type'    => 'aaroh_faq',
            'post_status'  => 'publish',
            'post_title'   => $item[0],
            'post_content' => $item[1],
            'menu_order'   => $order++,
        ]);
    }
}
