<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class_custom_tables
 *
 * @author Ravi
 */
class class_custom_tables {
    protected $brentVersion;
    public function __construct() {
        $this->brentVersion = get_option('brentVersion');
        $this->init();
    }

    public function init() {
        $this->wp_users_tasks_table();
        $this->wp_users_contact_table();
        $this->wp_pathway_plans_table();
        $this->wp_pathway_visits_table();
    }

    public function wp_users_tasks_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'users_tasks';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `pp_id` int(11) DEFAULT NULL,
            `user_id` int(11) DEFAULT NULL,
            `assignee_id` int(11) DEFAULT NULL,
            `due_date` date DEFAULT NULL,
            `completed` enum('1','0') NOT NULL DEFAULT '0',
            `description` text DEFAULT NULL,
            `type` enum('task','event') DEFAULT NULL,
            `section` varchar(255) DEFAULT NULL,
            `date_completed` date DEFAULT NULL,
            `completed_by` int(11) DEFAULT NULL,
            `badge` varchar(255) DEFAULT NULL,
          PRIMARY KEY  (id) 
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        if($this->brentVersion < 1.3){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}users_tasks` ADD  COLUMN IF NOT EXISTS `filename` VARCHAR(127) NULL DEFAULT NULL AFTER `section`");
            update_option('brentVersion', 1.3);
        }
        if($this->brentVersion < 1.8){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}users_tasks` ADD  COLUMN IF NOT EXISTS `file_name` VARCHAR(127) NULL DEFAULT NULL AFTER `filename`");
            update_option('brentVersion', 1.8);
        }
    }
    public function wp_users_contact_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'users_contact';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `contact_id` int(11) DEFAULT NULL,
                `sw_id` int(11) DEFAULT NULL,
                `yp_id` int(11) DEFAULT NULL,
          PRIMARY KEY  (id) 
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        if($this->brentVersion < 1.4){
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY IF NOT EXISTS `unique` (`contact_id`,`sw_id`,`yp_id`)");
            update_option('brentVersion',1.4);
        }
    }

    public function wp_pathway_plans_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'pathway_plans';
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL COMMENT 'YP user',
            `created_by` int(11) DEFAULT NULL COMMENT 'SW or PA user',
            `created_date` date DEFAULT NULL,
            `due_date` date DEFAULT NULL,
            `first_name` varchar(255) DEFAULT NULL,
            `last_name` varchar(255) DEFAULT NULL,
            `dob` date DEFAULT NULL,
            `gender` enum('m','f') DEFAULT NULL,
            `gender_birth` enum('yes','no') DEFAULT NULL,
            `disabled` enum('yes','no') DEFAULT NULL,
            `communication_needs` text DEFAULT NULL,
            `legal_status` varchar(255) DEFAULT NULL,
            `leaving_care_status` varchar(255) DEFAULT NULL,
            `Immigration_status` varchar(255) DEFAULT NULL,
            `who_has_got_my_birth_certificate` varchar(50) DEFAULT NULL,
            `birth_certificate` varchar(511) DEFAULT NULL,
            `birth_certificate_filename` varchar(511) DEFAULT NULL,
            `who_has_got_my_passport` varchar(50) DEFAULT NULL,
            `passport` varchar(511) DEFAULT NULL,
            `passport_filename` varchar(511) DEFAULT NULL,
            `ni_number` varchar(50) DEFAULT NULL,
            `overall_plan_feeling` enum('1','2','3','4','5') DEFAULT NULL,
            `overall_care_plan` text DEFAULT NULL,
            `attempts` text DEFAULT NULL,
            `family_relationship` text DEFAULT NULL,
            `workers_assessment` text DEFAULT NULL,
            `contact_arrangements` text DEFAULT NULL,
            `date_of_visiting` date DEFAULT NULL,
            `seen_alone` enum('yes','no') DEFAULT NULL,
            `comments` text DEFAULT NULL,
            `outside_statutory` text DEFAULT NULL,
            `visits` int(11) DEFAULT NULL,
            `education_feeling` enum('1','2','3','4','5') DEFAULT NULL,
            `education_working_well` varchar(255) DEFAULT NULL,
            `education_worried_about` varchar(255) DEFAULT NULL,
            `education_current_establishment` varchar(255) DEFAULT NULL,
            `education_address` varchar(100) DEFAULT NULL,
            `education_phone` varchar(20) DEFAULT NULL,
            `education_support_contact` varchar(50) DEFAULT NULL,
            `education_date` date DEFAULT NULL,
            `education_responsible_la` varchar(50) DEFAULT NULL,
            `education_next_steps` varchar(255) DEFAULT NULL,
            `education_long_term_goals` varchar(255) DEFAULT NULL,
            `education_contingency` varchar(255) DEFAULT NULL,
            `managing_feeling` enum('1','2','3','4','5') DEFAULT NULL,
            `managing_working_well` varchar(255) DEFAULT NULL,
            `managing_worried_about` varchar(255) DEFAULT NULL,
            `managing_next_steps` varchar(255) DEFAULT NULL,
            `managing_long_term_goals` varchar(255) DEFAULT NULL,
            `managing_contingency` varchar(255) DEFAULT NULL,
            `health_feeling` enum('1','2','3','4','5') DEFAULT NULL,
            `health_working_well` varchar(255) DEFAULT NULL,
            `health_worried_about` varchar(255) DEFAULT NULL,
            `health_next_steps` varchar(255) DEFAULT NULL,
            `health_long_term_goals` varchar(255) DEFAULT NULL,
            `health_contingency` varchar(255) DEFAULT NULL,
            `money_feeling` enum('1','2','3','4','5') DEFAULT NULL,
            `money_working_well` varchar(255) DEFAULT NULL,
            `money_worried_about` varchar(255) DEFAULT NULL,
            `money_next_steps` varchar(255) DEFAULT NULL,
            `money_long_term_goals` varchar(255) DEFAULT NULL,
            `money_contingency` varchar(255) DEFAULT NULL,
            `signed_by_sw` enum('yes','no') DEFAULT NULL,
            `date_signed_by_sw` date DEFAULT NULL,
            `signed_by_yp` enum('yes','no') DEFAULT NULL,
            `date_signed_by_yp` date DEFAULT NULL,
            `started` enum('yes','no') NOT NULL DEFAULT 'no',
            `past` enum('yes','no') NOT NULL DEFAULT 'no',
            `cancelled` enum('yes','no') NOT NULL DEFAULT 'no',
            `deleted` enum('yes','no') NOT NULL DEFAULT 'no',
            `expired_date` date DEFAULT NULL,
            PRIMARY KEY  (id)
          ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        if($this->brentVersion < 1){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` ADD  COLUMN IF NOT EXISTS `health_allergies` VARCHAR(255) NULL DEFAULT NULL AFTER `money_contingency`, ADD COLUMN IF NOT EXISTS `mental_health` VARCHAR(255) NULL DEFAULT NULL AFTER `health_allergies`");
            update_option('brentVersion', 1);
        }
        if($this->brentVersion < 1.1){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` CHANGE `created_date` `created_date` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP");
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` CHANGE `signed_by_sw` `signed_by_sw` ENUM('yes','no') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'no'");
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` CHANGE `signed_by_yp` `signed_by_yp` ENUM('yes','no') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'no'");
            update_option('brentVersion', 1.1);
        }
        if($this->brentVersion < 1.2){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` ADD  COLUMN IF NOT EXISTS `started` enum('yes','no') NOT NULL DEFAULT 'no' AFTER `date_signed_by_yp`");
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` ADD  COLUMN IF NOT EXISTS `past` enum('yes','no') NOT NULL DEFAULT 'no' AFTER `started`");
            update_option('brentVersion', 1.2);
        }
        if($this->brentVersion < 1.5){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` ADD  COLUMN IF NOT EXISTS `cancelled` enum('yes','no') NOT NULL DEFAULT 'no'");
            update_option('brentVersion', 1.5);
        }
        if($this->brentVersion < 1.6){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` ADD  COLUMN IF NOT EXISTS `deleted` enum('yes','no') NOT NULL DEFAULT 'no'");
            update_option('brentVersion', 1.6);
        }
        if($this->brentVersion < 1.7){
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}pathway_plans` ADD  COLUMN IF NOT EXISTS `expired_date` date NULL DEFAULT NULL ");
            update_option('brentVersion', 1.7);
        }
    }

    public function wp_pathway_visits_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'pathway_visits';
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `pathway_id` int(11) DEFAULT NULL,
            `visit` varchar(100) DEFAULT NULL,
            `member_name` varchar(30) DEFAULT NULL,
            `professionals_name` varchar(30) DEFAULT NULL,
            `date` date DEFAULT NULL,
            `update_visit` varchar(255) DEFAULT NULL,
            PRIMARY KEY (id)
          ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

}

$customTblObj = new class_custom_tables();
