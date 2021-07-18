<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class_json_api
 *
 * @author Ravi
 */
if (class_exists('class_json_api')) {

    class class_contact_api extends class_json_api {

        public function contactApi() {
            add_action('rest_api_init', function () {
                $prefixApiV1 = 'wpapp/api/v2';
                $prefixV1 = 'wpnotify/v1';
                // AUTH
                register_rest_route($prefixApiV1, '/contact/add_contact', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_add_contact'],
                ]);
                register_rest_route($prefixApiV1, '/contact/get_all_contact', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_all_contact'],
                ]);
                register_rest_route($prefixApiV1, '/contact/set_quick_contact', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_set_quick_contact'],
                ]);
                register_rest_route($prefixApiV1, '/contact/delete_my_contact', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_delete_my_contact'],
                ]);
                register_rest_route($prefixApiV1, '/contact/share_contact', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_share_contact'],
                ]);
                register_rest_route($prefixApiV1, '/contact/report_errors', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_report_errors'],
                ]);
            }
            );
        }

        public function wpapp_add_contact(WP_REST_Request $request){
            global $wpdb;
            $salt = SALT;
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];

                $swPaUpdate = false;
                $ypHash = false;

                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $userId = $user->ID;
                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    $getuserdata = get_userdata($userId);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if ($type == 'SW' || $type == 'PA') {
                        $ypid = $this->get_userid_from_hash($params['yp_id']);
                        if ($ypid) {
                            $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                            if (is_array($socialWorkers) && in_array($userId, $socialWorkers)) {
                                $userId = $ypid;
                                $swPaUpdate = true;
                                $ypHash = $params['yp_id'];
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find YP user', 500);
                        }
                    }
                }
                $data = array();
                $title = $params['firstName'].' '.$params['lastName'];
                $title = trim($title);
                if(!isset($params['id'])){
                    $data = array('post_type'=>'contact','post_status'=>'publish');
                    if($title!=""){
                        $data['post_title'] = $title;
                    }
                    $contactId = wp_insert_post($data);
                }else{
                    $contactId = $this->get_postid_from_hash($params['id']);
                    $contact = array(
                        'ID'           => $contactId,
                    );
                    if($title!=""){
                        $contact['post_title'] = $title;
                    }
                    $contactId = wp_update_post( $contact );
                }
                
                $data = [];
                if(isset($contactId) && intval($contactId)>0){
                    update_post_meta($contactId, 'user_id', $userId);
                        // $data['user_id'] = $params['user_id'];
                    if(isset($params['firstName'])){
                        update_post_meta($contactId, 'first_name', $params['firstName']);
                        $data['firstName'] = $params['firstName'];
                    }    
                    if(isset($params['lastName'])){
                        update_post_meta($contactId, 'last_name', $params['lastName']);
                        $data['lastName'] = $params['lastName'];
                    }    
                    if(isset($params['avatar']) && $params['avatar']!=""){
                        $avatar = trim($params['avatar']);
                        $avatar = explode(',', $avatar);
                        $data64 = base64_decode($avatar[1]);
                        if (strlen($data64) > 0) {
                            $path = AVATAR_PATH;
                            $url = AVATAR_URL;
                            if (!file_exists($path)) {
                                mkdir($path);
                            }
                            $filename = $userId . '_' . time();
                            $extension = false;
                            if (strpos($avatar[0], 'jpeg') !== false || strpos($avatar[0], 'jpg') !== false) {
                                $extension = '.jpeg';
                            }
                            if (strpos($avatar[0], 'png') !== false) {
                                $extension = '.png';
                            }
                            if (!$extension) {
                                return $this->standardizePayload([], 'Error! You need to upload jpeg, jpg or png.', 500);
                            }
                            if (!file_put_contents($path . $filename . $extension, $data64)) {
                                return $this->standardizePayload([], 'Error! Error in saving Avatar. Please try again.', 500);
                            }
                            update_post_meta($contactId, 'avatar', $filename . $extension);
                            $data['avatar'] = $url . $filename . $extension;
                        }
                    }    
                    if(isset($params['organization'])){
                        update_post_meta($contactId, 'organization', $params['organization']);
                        $data['organization'] = $params['organization'];
                    }    
                    if(isset($params['phone'])){
                        update_post_meta($contactId, 'phone', $params['phone']);
                        $data['phone'] = $params['phone'];
                    }    
                    if(isset($params['phoneNumbers'])){
                        update_post_meta($contactId, 'phoneNumbers', $params['phoneNumbers']);
                        $data['phoneNumbers'] = $params['phoneNumbers'];
                    }    
                    if(isset($params['email'])){
                        update_post_meta($contactId, 'email', $params['email']);
                        $data['email'] = $params['email'];
                    }
                    /*if(isset($params['isQuickContact'])){
                        update_post_meta($contactId, 'isQuickContact', $params['isQuickContact']);
                        $data['isQuickContact'] = $params['isQuickContact'];
                    }*/
                }
                if(intval($contactId)>0){
                    $id = $contactId.$salt;
                    $id = md5($id);
                    $data['id'] = $id;

                    if ($swPaUpdate && $ypHash) {
                        $this->dispatch_app_update('get_all_contact', $ypHash);
                    }

                    if(isset($params['id'])){
                        return $this->standardizePayload($data, 'Contact updated successfully.', 200);
                    }else{
                        return $this->standardizePayload($data, 'Contact added successfully.', 200);
                    }
                }
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }
        
        public function wpapp_get_all_contact(WP_REST_Request $request){
            global $wpdb;
            $salt = SALT;
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $loggedinUserId = $user->ID;
                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    $getuserdata = get_userdata($loggedinUserId);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if ($type == 'SW' || $type == 'PA') {
                        $ypid = $this->get_userid_from_hash($params['yp_id']);
                        if ($ypid) {
                            $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                            if (is_array($socialWorkers) && in_array($loggedinUserId, $socialWorkers)) {
                                $loggedinUserId = $ypid;
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find user', 500);
                        }
                    }
                }
                $post_typeS = isset($params['type']) ? $params['type'] : false;
                if(empty($post_typeS)){
                    $post_type[] = "contact";
                    $post_type[] = "service";
                }else{
                    $post_type[] = $post_typeS;
                }
                $data = [];
                $i = 0;
                foreach($post_type as $posttype){
                    if($posttype == "contact"){
                        $args = array(  
                            'post_type' => $posttype,
                            'post_status' => 'publish',
                            'meta_query' => array(
                                array(
                                    'key'     => 'user_id',
                                    'value'   => $loggedinUserId,
                                    'compare' => '=',
                                ),
                            )
                        );
                    }else{
                        $args = array(  
                            'post_type' => $posttype,
                            'post_status' => 'publish',
                        );
                    }
                    $query = new WP_Query( $args );
                    // The Loop
                    $idArr = [];
                    if ( $query->have_posts() ) {
                        while ( $query->have_posts() ) {
                            $query->the_post();
                            $id = get_the_ID();
                            $idArr[] = $id;
                            $posttype = get_post_type($id);
                            $data[$i]['id'] = md5($id . SALT);
                            if($posttype == "contact"){
                                $user_id = get_post_meta($id, 'user_id', true);
                                if ($loggedinUserId == $user_id){
                                    // $data[$i]['own'] = true;
                                }
                                $data[$i]['firstName'] = (string) get_post_meta($id, 'first_name', true);
                                $data[$i]['lastName'] = (string) get_post_meta($id, 'last_name', true);
                                $avatar = (string) get_post_meta($id,'avatar', true);
                                $data[$i]['avatar'] = $avatar ? AVATAR_URL.$avatar : "";
                                $data[$i]['organization'] = (string) get_post_meta($id, 'organization', true);
                                $data[$i]['phone'] = (string) get_post_meta($id, 'phone', true);
                                $data[$i]['phoneNumbers'] = get_post_meta($id, 'phoneNumbers', true);
                                $data[$i]['email'] = (string) get_post_meta($id, 'email', true);
                                $quickContact = get_user_meta($loggedinUserId, 'isQuickContact', true);
                                if (is_array($quickContact) && in_array($id, $quickContact)) {
                                    $data[$i]['isQuickContact'] = true;
                                } else {
                                    $data[$i]['isQuickContact'] = false;
                                }
                                $data[$i]['isMyContact'] = true;
                                $data[$i]['website'] = "";
                                $data[$i]['aboutService'] = "";
                                $data[$i]['serviceDocuments'] = [];
                                $data[$i]['serviceCategory'] = "";
                                $data[$i]['serviceCategoryIcon'] = "";
                                $data[$i]['type'] = $posttype;
                            }else{
                                $data[$i]['firstName'] = get_the_title();
                                $data[$i]['lastName'] = "";
                                $data[$i]['organization'] = "";
                                $data[$i]['avatar'] = (string) get_field('logo');
                                $data[$i]['phone'] = (string) get_field('phone_number');
                                $data[$i]['phoneNumbers'] = [];
                                $data[$i]['website'] = (string) get_field('website');
                                $quickServices = get_user_meta($loggedinUserId, 'isQuickService', true);
                                if (is_array($quickServices) && in_array($id, $quickServices)) {
                                    $data[$i]['isQuickContact'] = true;
                                } else {
                                    $data[$i]['isQuickContact'] = false;
                                }
                                $data[$i]['isMyContact'] = false;
                                $data[$i]['aboutService'] = (string) get_post_meta($id, 'about', true);
                                $data[$i]['serviceDocuments'] = [];
                                $data[$i]['serviceCategory'] = (string) get_post_meta($id, 'category', true);
                                $data[$i]['serviceCategoryIcon'] = (string) get_field('icon');
                                $data[$i]['type'] = $posttype;
                                $data[$i]['email'] = (string) get_post_meta($id, 'email', true);
                                if( have_rows('documents') ):
                                    $docCnt = 0;
                                    while( have_rows('documents') ) : the_row();
                                        $fileName = get_sub_field('file_name');
                                        $fileArray = get_sub_field('file');
                                        $date = get_sub_field('date');
                                        $size = $file = '';
                                        if (!empty($fileArray)) {
                                            $file = $fileArray['url'];
                                            $size = $fileArray['filesize'];
                                            $unit = false;
                                            if( (!$unit && $size >= 1<<30) || $unit == "GB")
                                                $size = number_format($size/(1<<30),2)."GB";
                                            elseif( (!$unit && $size >= 1<<20) || $unit == "MB")
                                                $size = number_format($size/(1<<20),2)."MB";
                                            elseif( (!$unit && $size >= 1<<10) || $unit == "KB")
                                                $size = number_format($size/(1<<10),2)."KB";
                                            else
                                                $size = number_format($size)." B";
                                        }
                                        $data[$i]['serviceDocuments'][$docCnt]['filename'] = $fileName;
                                        $data[$i]['serviceDocuments'][$docCnt]['file'] = $file;
                                        $data[$i]['serviceDocuments'][$docCnt]['date'] = $date;
                                        $data[$i]['serviceDocuments'][$docCnt]['size'] = $size;
                                        $docCnt++;
                                    endwhile;
                                endif;
                            }
                            $i++;
                        }
                        
                    }
                    wp_reset_postdata();
                }
                $contactIds = implode(',',$idArr);
                $contactids = $wpdb->get_results("SELECT contact_id FROM `{$wpdb->prefix}users_contact` WHERE yp_id = '$loggedinUserId' AND contact_id NOT IN ($contactIds)");
                if(!empty($contactids)){
                    foreach($contactids as $ids){
                        $id = $ids->contact_id;
                        $data[$i]['id'] = md5($id . SALT);
                        $user_id = get_post_meta($id, 'user_id', true);
                        if ($loggedinUserId == $user_id){
                            // $data[$i]['own'] = true;
                        }
                        $data[$i]['firstName'] = (string) get_post_meta($id, 'first_name', true);
                        $data[$i]['lastName'] = (string) get_post_meta($id, 'last_name', true);
                        $avatar = (string) get_post_meta($id,'avatar', true);
                        $data[$i]['avatar'] = $avatar ? AVATAR_URL.$avatar : "";
                        $data[$i]['organization'] = (string) get_post_meta($id, 'organization', true);
                        $data[$i]['phone'] = (string) get_post_meta($id, 'phone', true);
                        $data[$i]['phoneNumbers'] = get_post_meta($id, 'phoneNumbers', true);
                        $data[$i]['email'] = (string) get_post_meta($id, 'email', true);
                        $quickContact = get_user_meta($user->ID, 'isQuickContact', true);
                        if (is_array($quickContact) && in_array($id, $quickContact)) {
                            $data[$i]['isQuickContact'] = true;
                        } else {
                            $data[$i]['isQuickContact'] = false;
                        }
                        $data[$i]['isMyContact'] = false;
                        $data[$i]['website'] = "";
                        $data[$i]['aboutService'] = "";
                        $data[$i]['serviceDocuments'] = [];
                        $data[$i]['serviceCategory'] = "";
                        $data[$i]['serviceCategoryIcon'] = "";
                        $data[$i]['type'] = 'contact';
                        $i++;
                    }
                }
                if($i==0){
                    if(count($post_type) > 1){
                        return $this->standardizePayload($data, 'No contact or service found.', 400);
                    }else{
                        if($posttype == "service"){
                            return $this->standardizePayload($data, 'No service found.', 200);
                        }else{
                            return $this->standardizePayload($data, 'No contact found.', 200);
                        }
                    }
                }else{
                    if(count($post_type) > 1){
                        return $this->standardizePayload($data, 'List of contacts and services.', 200);
                    }else{
                        if($posttype == "service"){
                            return $this->standardizePayload($data, 'List of services.', 200);
                        }else{
                            return $this->standardizePayload($data, 'List of contacts.', 200);
                        }
                    }
                }
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }
        
        public function wpapp_set_quick_contact(WP_REST_Request $request){
            global $wpdb;
            $this->validate_token($request);
            $token = $this->getToken($request);

            $swPaUpdate = false;
            $ypHash = false;
            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $id = $params['id'];
                $id = $this->get_postid_from_hash($id);
                $user_id = $user->ID;
                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    $getuserdata = get_userdata($user_id);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if ($type == 'SW' || $type == 'PA') {
                        $ypid = $this->get_userid_from_hash($params['yp_id']);
                        if ($ypid) {
                            $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                            if (is_array($socialWorkers) && in_array($user_id, $socialWorkers)) {
                                $user_id = $ypid;
                                $swPaUpdate = true;
                                $ypHash = $params['yp_id'];
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find YP user', 500);
                        }
                    }
                }
                $quickcontact = $params['isQuickContact'];
                $type = $params['type'];
                if($type == "contact"){
                    $meta_key = 'isQuickContact';
                } else {
                    $meta_key = 'isQuickService';
                }
                $quickcontactArr = get_user_meta($user_id, $meta_key, true);
                if (!is_array($quickcontactArr)) {
                    $quickcontactArr = [];
                }

                if (!in_array($id, $quickcontactArr)) {
                    $quickcontactArr[] = $id;
                } else {
                    $key = array_search($id, $quickcontactArr);
                    unset($quickcontactArr[$key]);
                }
                $metaId = update_user_meta($user_id, $meta_key, $quickcontactArr);

                if(intval($metaId) > 0){
                    if ($swPaUpdate && $ypHash) {
                        $this->dispatch_app_update('get_all_contact', $ypHash);
                    }
                    return $this->standardizePayload([], 'Data saved', 200);
                }else{
                    return $this->standardizePayload([], 'Failed to update quick contact', 500);
                }
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
        public function wpapp_delete_my_contact(WP_REST_Request $request){
            global $wpdb;
            $salt = SALT;
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            $swPaUpdate = false;
            $ypHash = false;

            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $idHash = $params['id'];
                $id = $this->get_postid_from_hash($idHash);
                if (!$id) {
                    return $this->standardizePayload([], 'Contact not found', 570);
                }
                $created_by = get_post_meta($id, 'user_id', true);
                $user_id = $user->ID;
                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    $getuserdata = get_userdata($user_id);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if ($type == 'SW' || $type == 'PA') {
                        $ypid = $this->get_userid_from_hash($params['yp_id']);
                        if ($ypid) {
                            $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                            if (is_array($socialWorkers) && in_array($user_id, $socialWorkers)) {
                                $user_id = $ypid;
                                $swPaUpdate = true;
                                $ypHash = $params['yp_id'];
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find YP user', 500);
                        }
                    }
                }

                if ($created_by != $user_id) {
                    return $this->standardizePayload([], 'Failed to delete contact', 500);
                } else {
                    $return = wp_delete_post($id);
                    if($return != false || $return != ''){
                        $wpdb->query("DELETE FROM ".$wpdb->prefix."users_contact WHERE contact_id = ".$id);
                        if ($swPaUpdate && $ypHash) {
                            $this->dispatch_app_update('get_all_contact', $ypHash);
                        }
                        return $this->standardizePayload(md5($id.SALT), 'Contact deleted successfully', 200);
                    }else{
                        return $this->standardizePayload([], 'Failed to delete contact', 500);
                    }
                }
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
        public function wpapp_share_contact(WP_REST_Request $request){
            global $wpdb;
            $salt = SALT;
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $swId = $user->ID;
                $contacts = is_array($params['contact_id']) ? $params['contact_id'] : (array)$params['contact_id'];
                if(empty($contacts)){
                    return $this->standardizePayload([], 'Contact(s) not selected', 400);
                }             
                $ypHash = $params['yp_ids'];
                $ypHashArr = !empty($ypHash) ? explode(',', $ypHash) : [];
                $ypIdArr = [];
                if(!empty($ypHashArr)){
                    foreach ($contacts as $contactHash) {
                        $contactId = $this->get_postid_from_hash($contactHash);
                        if(intval($contactId) > 0){
                            foreach($ypHashArr as $yphash){
                                $ypId = $this->get_userid_from_hash($yphash);
                                if ($ypId) {
                                    $exists = $wpdb->get_var( $wpdb->prepare(
                                        "SELECT COUNT(*) FROM  ".$wpdb->prefix."users_contact WHERE contact_id = %d AND sw_id = %d AND yp_id = %d", $contactId, $swId, $ypId
                                      ) );
                                    if (!$exists) {
                                        $wpdb->insert(
                                                $wpdb->prefix.'users_contact',
                                                array('contact_id'=>$contactId,'sw_id'=>$swId,'yp_id'=>$ypId),
                                                array('%d','%d','%d')
                                        );
                                    }
                                }
                            }                            
                        }
                    }
                    if (count($contacts) && count($ypHashArr)) {
                        foreach($ypHashArr as $yphash) {
                            $this->dispatch_app_update('get_all_contact', $yphash);
                        }
                    }
                } else {
                    return $this->standardizePayload([], 'No YP selected', 400);
                }
                return $this->standardizePayload([], 'Contact assinged', 200);
            }else{
                return $this->standardizePayload([], 'SW/PA not found', 400);
            }
        }
        
        public function wpapp_report_errors(WP_REST_Request $request){
            global $wpdb;
            $salt = SALT;
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $userId = $user->ID;
                $serviceHash = $params['service_id'];
                $serviceId = $this->get_postid_from_hash($serviceHash);
                if ($serviceId) {
                    $message = esc_sql($params['message']);
                    $date = date("Y-m-d");
                    $name =  get_user_meta($ypId->user_id, 'first_name', true) .' ' . get_user_meta($ypId->user_id, 'last_name', true);
                    $service =  get_the_title($serviceId);

                    $report_error_id = wp_insert_post(
                    array(
                        "post_type"=>"report_error",
                        "post_status"=>"publish",
                        "post_title"=>"Error reported by $name for $service",
                        "post_content"=>$message
                    ));
                    update_field("user_id", $userId, $report_error_id);
                    update_field("service_id", $serviceId, $report_error_id);
                    update_field("report_date", $date, $report_error_id);
                    return $this->standardizePayload('Success', 'Report error saved successfully.', 200);
                } else {
                    return $this->standardizePayload([], 'Service not found', 400);
                }
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }
    }

    $contactApiObj = new class_contact_api();
    $contactApiObj->contactApi();
}