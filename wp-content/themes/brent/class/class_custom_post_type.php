<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class_custom_post_type
 *
 * @author Ravi
 */
class class_custom_post_type {

    public function __construct() {
        $this->init();
    }

    public function init() {
        add_action('init', array($this, 'wpapp_badge_post_type_fn'));
        add_action('init', array($this, 'wpapp_service_post_type_fn'));
        add_action('init', array($this, 'wpapp_contact_post_type_fn'));
        add_action('init', array($this, 'wpapp_report_errors_post_type_fn'));
    }

    public function wpapp_badge_post_type_fn() {
        $labels = array(
            'name' => _x('Badges', 'Post type general name', 'brent'),
            'singular_name' => _x('Badge', 'Post type singular name', 'brent'),
            'menu_name' => _x('Badges', 'Admin Menu text', 'brent'),
            'name_admin_bar' => _x('Badge', 'Add New on Toolbar', 'brent'),
            'add_new' => __('Add New', 'brent'),
            'add_new_item' => __('Add New Badge', 'brent'),
            'new_item' => __('New Badge', 'brent'),
            'edit_item' => __('Edit Badge', 'brent'),
            'view_item' => __('View Badge', 'brent'),
            'all_items' => __('All Badges', 'brent'),
            'search_items' => __('Search Badges', 'brent'),
            'parent_item_colon' => __('Parent Badges:', 'brent'),
            'not_found' => __('No badges found.', 'brent'),
            'not_found_in_trash' => __('No badges found in Trash.', 'brent'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'badge'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'supports' => array('title', 'thumbnail', 'author'),
        );

        register_post_type('badge', $args);
    }
    public function wpapp_service_post_type_fn() {
        $labels = array(
            'name' => _x('Services', 'Post type general name', 'brent'),
            'singular_name' => _x('Service', 'Post type singular name', 'brent'),
            'menu_name' => _x('Services', 'Admin Menu text', 'brent'),
            'name_admin_bar' => _x('Service', 'Add New on Toolbar', 'brent'),
            'add_new' => __('Add New', 'brent'),
            'add_new_item' => __('Add New Service', 'brent'),
            'new_item' => __('New Service', 'brent'),
            'edit_item' => __('Edit Service', 'brent'),
            'view_item' => __('View Service', 'brent'),
            'all_items' => __('All Services', 'brent'),
            'search_items' => __('Search Services', 'brent'),
            'parent_item_colon' => __('Parent Services:', 'brent'),
            'not_found' => __('No services found.', 'brent'),
            'not_found_in_trash' => __('No services found in Trash.', 'brent'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'service'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'supports' => array('title', 'thumbnail', 'author'),
        );
        
        register_post_type('service', $args);
    }
    public function wpapp_contact_post_type_fn() {
        $labels = array(
            'name' => _x('Contacts', 'Post type general name', 'brent'),
            'singular_name' => _x('Contact', 'Post type singular name', 'brent'),
            'menu_name' => _x('Contacts', 'Admin Menu text', 'brent'),
            'name_admin_bar' => _x('Contact', 'Add New on Toolbar', 'brent'),
            'add_new' => __('Add New', 'brent'),
            'add_new_item' => __('Add New Contact', 'brent'),
            'new_item' => __('New Contact', 'brent'),
            'edit_item' => __('Edit Contact', 'brent'),
            'view_item' => __('View Contact', 'brent'),
            'all_items' => __('All Contacts', 'brent'),
            'search_items' => __('Search Contacts', 'brent'),
            'parent_item_colon' => __('Parent Contacts:', 'brent'),
            'not_found' => __('No contacts found.', 'brent'),
            'not_found_in_trash' => __('No contact found in Trash.', 'brent'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'contact'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'supports' => array('title', 'thumbnail', 'author'),
        );

        register_post_type('contact', $args);
    }
    public function wpapp_report_errors_post_type_fn() {
        $labels = array(
            'name' => _x('Report error', 'Post type general name', 'brent'),
            'singular_name' => _x('Report error', 'Post type singular name', 'brent'),
            'menu_name' => _x('Report errors', 'Admin Menu text', 'brent'),
            'name_admin_bar' => _x('Report error', 'Add New on Toolbar', 'brent'),
            'add_new' => __('Add New', 'brent'),
            'add_new_item' => __('Add New Report error', 'brent'),
            'new_item' => __('New Report error', 'brent'),
            'edit_item' => __('Edit Report error', 'brent'),
            'view_item' => __('View Report error', 'brent'),
            'all_items' => __('All Report errors', 'brent'),
            'search_items' => __('Search Report errors', 'brent'),
            'parent_item_colon' => __('Parent Report errors:', 'brent'),
            'not_found' => __('No Report errors found.', 'brent'),
            'not_found_in_trash' => __('No Report error found in Trash.', 'brent'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'report_error'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
//            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'supports' => array('title', 'editor', 'author'),
        );

        register_post_type('report_error', $args);
    }

}

$cptObj = new class_custom_post_type();
