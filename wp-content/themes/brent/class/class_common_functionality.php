<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class_common_functionality
 *
 * @author Ravi
 */
class class_common_functionality {

    public function __construct() {
        $this->init();
    }

    public function init() {
        add_action('init', array($this, 'add_new_roles_for_site'));
        add_action('is_iu_import_page_before_table', array($this, 'remove_checkboxes_by_css'),10);
        add_action('is_iu_post_user_import', array($this, 'send_email_to_user'),10,1);
        add_action('is_iu_post_user_import', array($this, 'create_pp'),10,1);
        add_action('edit_user_created_user', array($this, 'send_email_to_user'),10,1);
        add_action('edit_user_created_user', array($this, 'create_pp'),10,1);
        add_action('template_redirect', array($this, 'stop_login_redirect'),10);
        add_filter('acf/fields/user/result', array($this, 'add_user_email_in_acf_user'), 10, 4);
        add_filter('is_iu_import_usermeta',array($this,'import_csv_usermeta'), 10, 2);
        add_filter('is_iu_import_userdata',array($this,'import_csv_userdata'), 10, 2);
    }
    
    public function stop_login_redirect(){
        $requ = untrailingslashit($_SERVER['REQUEST_URI']);
        if (site_url('login','relative') === $requ ){
          remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
        }
    }
    public function add_new_roles_for_site() {
        if (get_option('custom_roles_version') < 1) {
            add_role('young_person', 'Young Person', get_role('subscriber')->capabilities);
            add_role('social_worker', 'Social Worker', get_role('subscriber')->capabilities);
            add_role('personal_assistant', 'Personal Assistant', get_role('subscriber')->capabilities);
            update_option('custom_roles_version', 1);
        }
    }
    
    public function send_email_to_user($user_id) {
        global $wpdb;
        static $sendEmail = 1;
        $userData = get_user_by('id', $user_id);
        $userRole = $userData->roles[0];
        $userEmail = $userData->data->user_email;
        if($userRole == "subscriber"){
            $txt = "$userEmail is subscriber. \r\n";
            $txt .= "----------------------------------";
            $myfile = file_put_contents(trailingslashit(get_stylesheet_directory()).'/userlog.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
        }
        $swid = get_user_meta($user_id,'sw_pa_email',true);
        
        if(intval($swid) == 0 && $userRole == "young_person"){
                $txt = "social worker does not assigned to $userEmail \r\n";
                $txt .= "----------------------------------";
                $myfile = file_put_contents(trailingslashit(get_stylesheet_directory()).'/userlog.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
        }else{
        //--------------------------------------------------------------
        // send email to all user on import csv
        //--------------------------------------------------------------
        $userEmail = $userData->data->user_email;
        $strtotime = strtotime('now');
        $wpdb->update($wpdb->prefix.'users',
                array('user_status'=>$strtotime),
                array("id"=>$user_id),
                array('%s'),
                array('%d')
        );
        $hash = $userEmail.SALT.$strtotime;
        $hash = md5($hash);
        $user_login = $userData->user_login;
        $key = get_password_reset_key( $userData );
        
        $message = __('You are added to Brent, Please complete your profile:') . "\r\n\r\n";
        $message .= __('Please click below link') . "\r\n\r\n";
        $message .= get_site_url(null,'/register/'.$hash);
        $headers = "";
        $emailSent = wp_mail( $userEmail, "Complete your profile", $message, $headers );
        //--------------------------------------------------------------
        // end of code send email to all user on import csv
        //--------------------------------------------------------------
        }
    }

    public function create_pp($user_id) {
        global $wpdb;

        if (empty($user_id)) return;

        $userData = get_user_by('id', $user_id);
        $userRole = $userData->roles[0];
        if ($userRole == "young_person"){
            $data = $type = array();
            $data['user_id'] = $user_id;
            $type[] = '%d';
            $data['due_date'] = get_user_meta($data['user_id'], 'next_pp_due_date', true);
            
            if (empty($data['due_date']) || strtotime($data['due_date']) < time()) {
                $data['due_date'] = date('Y-m-d', strtotime('+6 weeks'));
            }
            $type[] = '%s';

            $socialWorkerIds = get_user_meta($data['user_id'], 'sw_pa_email', true);
            if (!empty($socialWorkerIds)) {
                $data['created_by'] = $socialWorkerIds[0];
            } else {
                $data['created_by'] = get_current_user_id();
            }
            
            $type[] = '%d';
            
            $data['created_date'] = date('Y-m-d');
            $type[] = '%s';
            
            $data['user_id'] = $user_id;
            $type[] = '%s';
            $data['first_name'] = get_user_meta($data['user_id'], 'first_name', true);
            $type[] = '%s';
            $data['last_name'] = get_user_meta($data['user_id'], 'last_name', true);
            $type[] = '%s';
            $data['dob'] = get_user_meta($data['user_id'], 'dob', true);
            $type[] = '%s';
            $data['gender'] = get_user_meta($data['user_id'], 'gender', true);
            $type[] = '%s';
            $data['gender_birth'] = get_user_meta($data['user_id'], 'gender_birth', true);
            $type[] = '%s';
            $data['signed_by_sw'] = 'no';
            $type[] = '%s';
            $data['signed_by_yp'] = 'no';
            $type[] = '%s';
            $data['started'] = 'no';
            $type[] = '%s';
            $data['past'] = 'no';
            $type[] = '%s';

            try {
                $table = $wpdb->prefix.'pathway_plans';
                $wpdb->insert( 
                    $table, 
                    $data, 
                    $type
                );
                $pathway_id = $wpdb->insert_id;
                if(intval($pathway_id) > 0){
                    return;
                }
            } catch (Exception $e) {
                return;
            }
        }
    }

    public function add_user_email_in_acf_user($result, $user, $field, $post_id) {
        $result = $user->user_login;

        if ($user->first_name) {

            $result = $user->first_name;

            if ($user->last_name) {

                $result .= ' ' . $user->last_name;
            }
        }
        if ($user->user_email) {
            $result .= ' ( ' . $user->user_email . ' )';
        }
        return $result;
    }
    public function import_csv_userdata($userdata, $usermeta){
        $userType = $usermeta['type'];
        if(isset($userdata['user_email']) && !empty($userdata['user_email'])){
            $userdata['user_login'] = $userdata['user_email'];
        }
        if(isset($userType) && array_key_exists($userType, USERROLE)){
            $userdata['role'] = USERROLE[$userType];
        }
        return $userdata;
    }
    public function import_csv_usermeta($usermeta, $userdata){
        if(!empty($usermeta) && isset($usermeta['sw_pa_email'])){
            $userEmails = explode(';',$usermeta['sw_pa_email']);
            $swpaIds = [];
            foreach($userEmails as $userEmail){
                $userInfo = get_user_by('email', $userEmail);
                $swpaIds[] = $userInfo->ID;
            }
            $usermeta['sw_pa_email'] = $swpaIds;
        }
        if(isset($usermeta['dob']) && $usermeta['dob']!=""){
            $usermeta['dob'] = date('Y/m/d', strtotime($usermeta['dob']));
        }
        if(isset($usermeta['next_pp_due_date']) && $usermeta['next_pp_due_date']!=""){
            $usermeta['next_pp_due_date'] = date('Y/m/d', strtotime($usermeta['next_pp_due_date']));
        }
        unset($usermeta['type']);
        return $usermeta;
    }
    public function remove_checkboxes_by_css(){
        $css = "<style>";
        $css .= "table.wp-list-table tr:nth-child(2),table.wp-list-table tr:nth-child(3){ display:none; }";
        $css .= "</style>";
        echo $css; 
    }
}

$commonFunObj = new class_common_functionality();
