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

    class class_users_api extends class_json_api {

        public function usersApi() {
            add_action('rest_api_init', function () {
                $prefixApiV1 = 'wpapp/api/v2';
                $prefixV1 = 'wpnotify/v1';
                // AUTH

                register_rest_route($prefixApiV1, '/user/register', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_register_new_user'],
                        ], true);

                register_rest_route($prefixApiV1, '/user/login_password', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_login_user_password'],
                        ], true);

                register_rest_route($prefixApiV1, '/user/login', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_login_user'],
                        ], true);

                register_rest_route($prefixApiV1, '/user/forgot_password', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_forgot_password_email'],
                        ], true);

                register_rest_route($prefixApiV1, '/user/check_link', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_check_link'],
                ]);

                register_rest_route($prefixApiV1, '/user/verify_token', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_verify_token'],
                ]);

                register_rest_route($prefixApiV1, '/user/reset_password', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_reset_password_email'],
                ]);
                register_rest_route($prefixV1, '/token/update', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpnotify_update_push_token'],
                ], true);
                register_rest_route($prefixApiV1, '/user/edit_profile', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_user_edit_profile'],
                ]);
                register_rest_route($prefixApiV1, '/user/edit_contact', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_user_edit_contact'],
                ]);
                register_rest_route($prefixApiV1, '/user/edit_bio', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_user_edit_bio'],
                ]);
                register_rest_route( $prefixApiV1, '/user/info', [
                        'methods' => 'POST',
                        'callback' => [$this, 'wpapp_get_user_info'],
                ],true);
                register_rest_route( $prefixApiV1, '/user/info_full', [
                        'methods' => 'POST',
                        'callback' => [$this, 'wpapp_get_user_info_full'],
                ],true);
                register_rest_route($prefixApiV1, '/user/get_young_persons', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_young_persons'],
                ]);
                register_rest_route($prefixApiV1, '/user/remove_yp_from_swpa', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_remove_yp_from_swpa'],
                ]);
                register_rest_route($prefixApiV1, '/user/add_yp_ids', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_add_yp_ids'],
                ]);
                register_rest_route($prefixApiV1, '/user/get_all_yp', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_all_yp'],
                ]);
                register_rest_route($prefixApiV1, '/user/push_notification', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_push_notification'],
                ]);                
                register_rest_route($prefixApiV1, '/user/app_settings', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_user_app_settings'],
                ]);
            }
            );
        }

        public function wpapp_register_new_user(WP_REST_Request $request) {
            global $wpdb;
            $salt = SALT;

            $params = $request != null ? (array) $request->get_params() : null;

            $nonce = $params['nonce'];

            if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                return $this->standardizePayload([], 'Invalid nonce', 570);
            }

            $hash = $params['hash'];
            $password = $params['password'];
            $securityQuestion = $params['security_question'];
            $securityAnswer = $params['security_answer'];
            $results = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}users` WHERE MD5(CONCAT(user_email,'$salt',user_status)) = '$hash'", OBJECT);
            $user_id = $results->ID;
            $user = get_userdata($user_id);
            $user_roles = $user->roles;
            if(is_array($user_roles) && !empty($user_roles)){
                if (in_array(USERROLE['YP'], $user_roles)) {
                    $userType = "YP";
                }
                if (in_array(USERROLE['SW'], $user_roles)) {
                    $userType = "SW";
                }
                if (in_array(USERROLE['PA'], $user_roles)) {
                    $userType = "PA";
                }
            }
            if (intval($user_id) > 0) {
                if ($user_id instanceof WP_Error) {
                    if ($user_id->get_error_code() == 'existing_user_login') {
                        return $this->standardizePayload([], strip_tags($user_id->get_error_message()), 531);
                    }

                    if ($user_id->get_error_code() == 'existing_user_email') {
                        return $this->standardizePayload([], strip_tags($user_id->get_error_message()), 532);
                    }

                    return $this->standardizePayload([], strip_tags($user_id->get_error_message()), 500);
                }
                $token = $this->createNewToken();
                $res;
                if (isset($params['expiry'])) {
                    $expiry = $params['expiry'];
                    $res = $this->addNewTokenToDb($token, $user_id, $expiry);
                } else {
                    $res = $this->addNewTokenToDb($token, $user_id);
                }
                wp_set_password( $password, $user_id );
                update_user_meta($user_id, "security_question", $securityQuestion);
                update_user_meta($user_id, "security_answer", $securityAnswer);
                update_user_meta($user_id, "link_timestamp", strtotime('now'));
                $strtotime = strtotime('now');
                $wpdb->update($wpdb->prefix . 'users',
                        array('user_status' => $strtotime),
                        array("id" => $user_id),
                        array('%s'),
                        array('%d')
                );
                $user_id = $results->ID.SALT;
                $user_id = md5($user_id);
                return $this->standardizePayload([
                            'user_token' => $res['token'],
                            'user_id' => $user_id,
                            'user_type' => $userType,
                            'expiry' => $res['expires_at'],
                            'user_email' => $user->user_email,
                                ], 'Your data were successfully updated', 200);
            } else {
                return $this->standardizePayload([], "Either user don't exist or link expired", 527);
            }
        }

        public function wpapp_login_user_password(WP_REST_Request $request) {
            $params = $request != null ? (array) $request->get_params() : null;

            if (!isset($params['auth'])) {
                return $this->standardizePayload([], "Missing query 'auth' specify type e.g. ?auth=email or ?auth=username", 599);
            }

            if (!(($params['auth'] == "email") || ($params['auth'] == "username"))) {
                return $this->standardizePayload([], "Invalid value, the 'auth' query can only be 'email' or 'username'", 598);
            }

            $authType = $params['auth'];
            $requiredParams = ['nonce', 'password'];
            $requiredParams[] = $authType;

            if (isset($params['nonce'])) {

                // CHECK NONCE
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $check = null;

                if (isset($params[$authType]) && isset($params['password'])) {
                    if ($authType == 'email') {
                        $email = $params['email'];
                        $password = $params['password'];
                        $check = wp_authenticate_email_password(NULL, $email, $password);
                    } else if ($authType == 'username') {
                        $username = $params['username'];
                        $password = $params['password'];
                        $check = wp_authenticate_username_password(NULL, $username, $password);
                    }
                    $securityQuestion = get_user_meta($check->ID, "security_question", true);
                    $user = get_userdata($check->ID);
                    $user_roles = $user->roles;
                    if(is_array($user_roles) && !empty($user_roles)){
                        if (in_array(USERROLE['YP'], $user_roles)) {
                            $userType = "YP";
                        }
                        if (in_array(USERROLE['SW'], $user_roles)) {
                            $userType = "SW";
                        }
                        if (in_array(USERROLE['PA'], $user_roles)) {
                            $userType = "PA";
                        }
                    }
                    if ($check instanceof WP_Error) {

                        if ($check->get_error_code() == 'invalid_email') {
                            return $this->standardizePayload([], strip_tags($check->get_error_message()), 547);
                        }

                        if ($check->get_error_code() == 'incorrect_password') {
                            return $this->standardizePayload([], strip_tags($check->get_error_message()), 546);
                        }

                        if ($check->get_error_code() == 'invalid_username') {
                            return $this->standardizePayload([], strip_tags($check->get_error_message()), 545);
                        }
                        return $this->standardizePayload([], strip_tags($check->get_error_message()) . " Code: " . $check->get_error_code(), 567);
                    } else if ($check instanceof WP_User) {
                        $token = $this->createNewToken();
                        $res;

                        if (isset($params['expiry'])) {
                            $expiry = $params['expiry'];
                            $res = $this->addNewTokenToDb($token, $check->ID, $expiry);
                        } else {
                            $res = $this->addNewTokenToDb($token, $check->ID);
                        }
                        return $this->standardizePayload([
                                    'user_token' => $res['token'],
                                    'user_type' => $userType,
                                    'security_question' => $securityQuestion,
                                    'expiry' => $res['expires_at']
                                        ], 'Please enter security answer', 200);
                    } else {
                        return $this->standardizePayload(['error' => $check], 'Error, something went wrong.', 500);
                    }
                }
                return $check;
            }

            $msg = '';
            $missing = [];
            foreach ($requiredParams as $requiredParam) {
                if (empty($params[$requiredParam])) {
                    $missing[] = "'$requiredParam'";
                }
            }
            if (count($missing) != 0) {
                $msg = 'Missing params ' . implode(", ", $missing);
            }
            return $this->standardizePayload([], $msg, 500);
        }

        public function wpapp_login_user(WP_REST_Request $request) {
            $params = $request != null ? (array) $request->get_params() : null;

            if (!isset($params['auth'])) {
                return $this->standardizePayload([], "Missing query 'auth' specify type e.g. ?auth=email or ?auth=username", 599);
            }

            if (!(($params['auth'] == "email") || ($params['auth'] == "username"))) {
                return $this->standardizePayload([], "Invalid value, the 'auth' query can only be 'email' or 'username'", 598);
            }

            $authType = $params['auth'];
            $securityAns = trim($params['security_answer']);
            $securityAns = strtolower($securityAns);
            $requiredParams = ['nonce', 'password'];
            $requiredParams[] = $authType;

            if (isset($params['nonce'])) {

                // CHECK NONCE
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                if (!isset($securityAns) || $securityAns == "") {
                    return $this->standardizePayload([], 'Please enter security answer.', 404);
                }

                $check = null;
                if (isset($params[$authType]) && isset($params['password'])) {
                    if ($authType == 'email') {
                        $email = $params['email'];
                        $password = $params['password'];
                        $check = wp_authenticate_email_password(NULL, $email, $password);
                    } else if ($authType == 'username') {
                        $username = $params['username'];
                        $password = $params['password'];
                        $check = wp_authenticate_username_password(NULL, $username, $password);
                    }
                    $storedAns = get_user_meta($check->ID, "security_answer", true);
                    $user = get_userdata($check->ID);
                    $user_roles = $user->roles;
                    if(is_array($user_roles) && !empty($user_roles)){
                        if (in_array(USERROLE['YP'], $user_roles)) {
                            $userType = "YP";
                        }
                        if (in_array(USERROLE['SW'], $user_roles)) {
                            $userType = "SW";
                        }
                        if (in_array(USERROLE['PA'], $user_roles)) {
                            $userType = "PA";
                        }
                    }
                    $storedAns = strtolower($storedAns);

                    if ($check instanceof WP_Error) {

                        if ($check->get_error_code() == 'invalid_email') {
                            return $this->standardizePayload([], strip_tags($check->get_error_message()), 547);
                        }

                        if ($check->get_error_code() == 'incorrect_password') {
                            return $this->standardizePayload([], 'The password is not the correct one.', 546);
                        }

                        if ($check->get_error_code() == 'invalid_username') {
                            return $this->standardizePayload([], strip_tags($check->get_error_message()), 545);
                        }

                        if ($check->get_error_code() == 'empty_username') {
                            return $this->standardizePayload([], strip_tags($check->get_error_message()), 542);
                        }

                        return $this->standardizePayload([], strip_tags($check->get_error_message()) . " Code: " . $check->get_error_code(), 567);
                    } else if ($check instanceof WP_User) {
                        $token = $this->createNewToken();

                        if (isset($params['expiry'])) {
                            $expiry = $params['expiry'];
                            $res = $this->addNewTokenToDb($token, $check->ID, $expiry);
                        } else {
                            $res = $this->addNewTokenToDb($token, $check->ID);
                        }
                        $user_id = $check->ID.SALT;
                        $user_id = md5($user_id);
                        if ($securityAns != $storedAns) {
                            return $this->standardizePayload([], 'Please enter correct security answer', 404);
                        }

                        return $this->standardizePayload([
                                    'user_token' => $res['token'],
                                    'user_id' => $user_id,
                                    'user_type' => $userType,
                                    'expiry' => $res['expires_at']
                                        ], 'User logged in successfully', 200);
                    } else {
                        return $this->standardizePayload(['error' => $check], 'Error, something went wrong.', 500);
                    }
                } else {
                    return $this->standardizePayload([], 'Invalid params', 523);
                }
                return $check;
            }

            $msg = '';
            $missing = [];
            foreach ($requiredParams as $requiredParam) {
                if (empty($params[$requiredParam])) {
                    $missing[] = "'$requiredParam'";
                }
            }
            if (count($missing) != 0) {
                $msg = 'Missing params ' . implode(", ", $missing);
            }
            return $this->standardizePayload([], $msg, 500);
        }

        public function wpapp_reset_password_email(WP_REST_Request $request) {
            global $wpdb;
            $salt = SALT;
            $params = $request != null ? (array) $request->get_params() : null;

            $nonce = $params['nonce'];

            if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                return $this->standardizePayload([], 'Invalid nonce', 570);
            }
            $password = $params['password'];
            if (empty($password)) {
                return $this->standardizePayload([], 'Password field can\' be empty', 400);
            }
            $hash = $params['hash'];
            $securityAnswer = $params['security_answer'];
            $securityQuestion = $params['security_question'];
            if (empty($securityAnswer)) {
                return $this->standardizePayload([], 'Security question can\' be empty', 400);
            }
            if (empty($securityQuestion)) {
                return $this->standardizePayload([], 'Security answer can\' be empty', 400);
            }
            $user_id =$wpdb->get_var("SELECT ID FROM $wpdb->users WHERE MD5(CONCAT(user_email,'$salt',user_status)) = '$hash'");
            if(!$user_id){
                return $this->standardizePayload([], 'The user is not found', 400);
            }
            
            $password = md5($password);
            try {
                $query = "UPDATE $wpdb->users SET `user_pass` = '$password' WHERE ID = ".$user_id;
                $updatePass = $wpdb->query($query);
                if ($updatePass == 1) {
                    update_user_meta($user_id, "security_question", $securityQuestion);
                    update_user_meta($user_id, "security_answer", $securityAnswer);
                    $now = strtotime('now');
                    $query = "UPDATE $wpdb->users SET `user_status` = '$now' WHERE MD5(CONCAT(user_email,'$salt',user_status)) = '$hash'";
                    $updateuserStatus = $wpdb->query($query);
                    return $this->standardizePayload([
                                'message' => 'Password updated successfully.'
                                    ], 'Password changed successfully', 200);
                } else {
                    return $this->standardizePayload([], 'Password link expired', 400);
                }
            } catch (Exception $e) {
                return $this->standardizePayload([], 'Server error', 500);
            }
        }

        public function wpapp_get_user_info(WP_REST_Request $request) {
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            // Check if user exists
            if (($user instanceof WP_User) && $user->exists()) {

                $params = $request != null ? (array) $request->get_params() : null;

                $user_id = $user->ID;

                $getuserdata = get_userdata($user_id);
                $role = $getuserdata->roles[0];
                $type = array_search($role, USERROLE);

                if (isset($params['yp_id']) && !empty($params['yp_id']) && ($type == 'SW' || $type == 'PA')) {

                    $ypid = $this->get_userid_from_hash($params['yp_id']);
                    if ($ypid) {
                        $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                        if (is_array($socialWorkers) && in_array($user_id, $socialWorkers)) {
                            $user_id = $ypid;
                            $getuserdata = get_userdata($user_id);
                            $role = $getuserdata->roles[0];
                            $type = array_search($role, USERROLE);
                        } else {
                            return $this->standardizePayload([], 'Not allowed', 500);
                        }
                    } else {
                        return $this->standardizePayload([], 'Cannot find user', 500);
                    }
                }

                $usermeta = get_user_meta($user_id);
                $userData = get_userdata($user_id);
                $responseData = array();
                $avatar = $usermeta['avatar'][0];
                $responseData['avatar'] = (string) ($avatar ? AVATAR_URL.$avatar : "");
                $responseData['first_name'] = $usermeta['first_name'][0];
                $responseData['last_name'] = $usermeta['last_name'][0];
                $responseData['dob'] = !empty($usermeta['dob'][0]) ? date("d/m/Y", strtotime(str_replace("/","-",$usermeta['dob'][0]))) : '';
                $responseData['gender'] = $usermeta['gender'][0];
                $responseData['gender_birth'] = $usermeta['gender_birth'][0];
                $responseData['first_language'] = $usermeta['first_language'][0];
                $responseData['other_languages'] = !empty($usermeta['other_languages'][0]) ? unserialize($usermeta['other_languages'][0]) : [];
                $responseData['mobile'] = $usermeta['phone_number'][0];
                $responseData['phone_number'] = $usermeta['phone_number'][0];
                $responseData['phone_numbers'] = !empty($usermeta['phone_numbers'][0]) ? json_decode($usermeta['phone_numbers'][0]) : [];
                $responseData['city'] = $usermeta['city'][0];
                $responseData['email'] = $userData->user_email;
                $responseData['emails'] = !empty($usermeta['emails'][0]) ? json_decode($usermeta['emails'][0]) : [];
                $responseData['address'] = $usermeta['address'][0];
                $responseData['about_me'] = $usermeta['about_me'][0];
                $responseData['allergies'] = $usermeta['allergies'][0];
                $responseData['achievements'] = $usermeta['achievements'][0];
                $responseData['care_history'] = $usermeta['care_history'][0];
                $responseData['show_group_notification'] = $usermeta['show_group_notification'][0];
                $responseData['notifications_email'] = $usermeta['notifications_email'][0];
                $responseData['notifications_sms'] = $usermeta['notifications_sms'][0];
                $responseData['notifications_push'] = $usermeta['notifications_push'][0];
                $responseData['receive_remainder'] = $usermeta['receive_remainder'][0];
                $responseData['inapp_vibrate'] = $usermeta['inapp_vibrate'][0];
                $responseData['inapp_sounds'] = $usermeta['inapp_sounds'][0];
                $responseData['type'] = $type;
                $responseData['sw_pa_email'] = '';
                $responseData['supporting_people'] = [];
                if(isset($usermeta['sw_pa_email']) && !empty($usermeta['sw_pa_email'])){
                    $swids = !empty($usermeta['sw_pa_email'][0]) ? unserialize($usermeta['sw_pa_email'][0]) : false;
                    if (!empty($swids)) {
                        $i = 0;
                        foreach ($swids as $swid) {
                            if ($swuser = get_user_by('id', $swid)) {
                                $role = $swuser->roles[0];
                                $type = array_search($role, USERROLE);
                                if ($type == 'SW' || count($swids) == 1) {
                                    $responseData['sw_pa_email'] = $swuser->user_email;
                                }else{
                                    $responseData['sw_pa_email'] = "";
                                }
                                $swUserMeta = get_user_meta($swid);
                                $avatar = $swUserMeta['avatar'][0];
                                $responseData['supporting_people'][$i]['avatar'] = (string) ($avatar ? AVATAR_URL.$avatar : "");
                                $responseData['supporting_people'][$i]['first_name'] = $swUserMeta['first_name'][0];
                                $responseData['supporting_people'][$i]['last_name'] = $swUserMeta['last_name'][0];
                                $responseData['supporting_people'][$i]['phone_number'] = $swUserMeta['phone_number'][0];
                                $responseData['supporting_people'][$i]['type'] = $type;
                                $responseData['supporting_people'][$i]['id'] = md5($swid.SALT);//$swid;
                                $responseData['supporting_people'][$i]['user_name'] = $swUserMeta['first_name'][0] . ' ' . $swUserMeta['last_name'][0];//$swid;
                                $i++;
                            }
                        }
                    }
                }
                return $this->standardizePayload($responseData, 'Success', 200);
            } else {
                return $this->standardizePayload([], 'Cannot find user', 500);
            }
        }

        public function wpapp_get_user_info_full(WP_REST_Request $request) {
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            // Check if user exists
            if (($user instanceof WP_User) && $user->exists()) {
                $role = $user->roles[0];
                $type = array_search($role, USERROLE);

                $info = $this->wpapp_get_user_info($request, true)['data'];

                $tasksApiObj = new class_tasks_api();
                $tasks = $tasksApiObj->wpapp_get_task_function($request)['data'];

                $pathwayApiObj = new class_pathway_api();

                if ($type == 'YP') {
                    $pathwayPlans = $pathwayApiObj->wpapp_get_pathway($request)['data'];
                } else {
                    $pathwayPlans = $pathwayApiObj->wpapp_get_pathway_sw_pa($request)['data'];
                }

                $contactsApiObj = new class_contact_api();
                $contacts = $contactsApiObj->wpapp_get_all_contact($request)['data'];

                $badgesApiObj = new class_badge_api();
                $badges = $badgesApiObj->wpapp_get_all_badges_function($request, 'own')['data'];

                if ($type != 'YP') {
                    $myCaseLoad = $this->wpapp_get_young_persons($request)['data'];
                    $responseData['myCaseLoad'] = $myCaseLoad;
                }

                $responseData['info'] = $info;
                $responseData['tasks'] = $tasks;
                $responseData['pathwayPlans'] = $pathwayPlans;
                $responseData['contacts'] = $contacts;
                $responseData['badges'] = $badges;

                return $this->standardizePayload($responseData, 'Success', 200);
            } else {
                return $this->standardizePayload([], 'Cannot find user', 500);
            }
        }

        public function wpapp_user_edit_bio(WP_REST_Request $request) {

            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            // Check if user exists
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];

                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }

                $about_me = trim($params['about_me']);
                $allergies = trim($params['allergies']);
                $achievements = $params['achievements'];
                $care_history = $params['care_history'];
                $user_id = $user->ID;

                $swPaUpdate = false;
                $ypHash = false;

                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    $getuserdata = get_userdata($user_id);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if ($type == 'SW' || $type == 'PA') {
                        $ypid = $this->get_userid_from_hash($params['yp_id']);
                        if ($ypid) {
                            $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                            if (is_array($socialWorkers) && in_array($user_id, $socialWorkers)) {
                                $user = get_userdata($user_id);
                                $type = array_search(array_values($user->roles)[0], USERROLE);
                                $user_id = $ypid;
                                $swPaUpdate = true;
                                $ypHash = $params['yp_id'];
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find user', 500);
                        }
                    }
                }
                try {
                    if (isset($about_me) && $about_me != "") {
                        update_user_meta($user_id, 'about_me', $about_me);
                    }
                    if (isset($allergies) && $allergies != "") {
                        update_user_meta($user_id, 'allergies', $allergies);
                    }
                    if (isset($achievements) && !empty($achievements)) {
                        update_user_meta($user_id, 'achievements', $achievements);
                    }
                    if (isset($care_history) && !empty($care_history)) {
                        update_user_meta($user_id, 'care_history', $care_history);
                    }
                } catch (Exception $e) {
                    return $this->standardizePayload([], 'Server error.', 500);
                }

                if ($swPaUpdate && $ypHash) {
                    $this->dispatch_app_update('info', $ypHash);
                }

                return $this->standardizePayload([
                            'result' => "Success",
                                ], 'User bio updated successfully', 200);
            } else {
                return $this->standardizePayload([], 'Cannot find user', 500);
            }
        }

        public function wpapp_user_edit_contact(WP_REST_Request $request) {

            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            // Check if user exists
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];

                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }

                $phone_number = trim($params['mobile']);
                $phone_numbers = explode(',', $params['phone_numbers']);
                $emails = explode(',', $params['emails']);
                $address = trim($params['address']);
                $city = trim($params['city']);
                $user_id = $user->ID;

                $swPaUpdate = false;
                $ypHash = false;

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
                                $user = get_userdata($user_id);
                                $type = array_search(array_values($user->roles)[0], USERROLE);
                                $swPaUpdate = true;
                                $ypHash = $params['yp_id'];
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find user', 500);
                        }
                    }
                }
                try {
                    if (isset($phone_number) && $phone_number != "") {
                        update_user_meta($user_id, 'phone_number', $phone_number);
                    }
                    if (isset($phone_numbers) && $phone_numbers != "") {
                        update_user_meta($user_id, 'phone_numbers', json_encode($phone_numbers));
                    }
                    if (isset($emails) && $emails != "") {
                        update_user_meta($user_id, 'emails', json_encode($emails));
                    }
                    if (isset($address) && $address != "") {
                        update_user_meta($user_id, 'address', $address);
                    }
                    if (isset($city) && $city != "") {
                        update_user_meta($user_id, 'city', $city);
                    }
                } catch (Exception $e) {
                    return $this->standardizePayload([], 'Server error.', 500);
                }

                if ($swPaUpdate && $ypHash) {
                    $this->dispatch_app_update('info', $ypHash);
                }

                return $this->standardizePayload([
                            'result' => "Success",
                                ], 'Contact updated successfully', 200);
            } else {
                return $this->standardizePayload([], 'Cannot find user', 500);
            }
        }

        public function wpapp_user_edit_profile(WP_REST_Request $request) {
            $genderArr = array('m', 'f');
            $genderBirthArr = array('yes', 'no');
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            // Check if user exists
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];

                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $user_id = $user->ID;
                $type = array_search(array_values($user->roles)[0], USERROLE);
                $swPaUpdate = false;
                $ypHash = false;
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
                                $user = get_userdata($user_id);
                                $type = array_search(array_values($user->roles)[0], USERROLE);
                                $swPaUpdate = true;
                                $ypHash = $params['yp_id'];
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find user', 500);
                        }
                    }
                }
                if (isset($params['avatar']) && $params['avatar']!="" && strpos($params['avatar'], 'http') === false) {
                    $avatar = trim($params['avatar']);
                    $avatar = explode(',', $avatar);
                    $data = base64_decode($avatar[1]);
                    if (strlen($data) > 0) {
                        $path = AVATAR_PATH;
                        if (!file_exists($path)) {
                            mkdir($path);
                        }
                        $filename = $user_id.'_'.time();
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
                        if (!file_put_contents($path.$filename.$extension, $data)) {
                            return $this->standardizePayload([], 'Error! Error in saving Avatar. Please try again.', 500);
                        }
                        $avatar = $filename.$extension;
                    }
                }
                $firstname = trim($params['first_name']);
                $lastname = trim($params['last_name']);
                $dob = trim($params['dob']);
                $gender = trim($params['gender']);
                $gender_birth = trim($params['gender_birth']);

                if (!in_array($gender, $genderArr) && $type == 'YP') {
                    return $this->standardizePayload([], 'Please enter correct gender', 404);
                }
                if (!in_array($gender_birth, $genderBirthArr) && $type == 'YP') {
                    return $this->standardizePayload([], 'Please enter correct gender birth', 404);
                }
                $first_language = trim($params['first_language']);
                $other_languages = trim($params['other_languages']);
                $other_languages = explode(',', $other_languages);
                try {
                    if (isset($firstname) && $firstname != "") {
                        update_user_meta($user_id, 'first_name', $firstname);
                    }
                    if (isset($lastname) && $lastname != "") {
                        update_user_meta($user_id, 'last_name', $lastname);
                    }
                    if (isset($avatar) && $avatar != "") {
                        update_user_meta($user_id, 'avatar', $avatar);
                    }
                    if (isset($dob) && $dob != "") {
                        update_user_meta($user_id, 'dob', $dob);
                    }
                    if (isset($gender) && $gender != "") {
                        update_user_meta($user_id, 'gender', $gender);
                    }
                    if (isset($gender_birth) && $gender_birth != "") {
                        update_user_meta($user_id, 'gender_birth', $gender_birth);
                    }
                    if (isset($first_language) && $first_language != "") {
                        update_user_meta($user_id, 'first_language', $first_language);
                    }
                    if (isset($other_languages) && !empty($other_languages)) {
                        update_user_meta($user_id, 'other_languages', $other_languages);
                    }

                    if (isset($params['jobTitle']) && in_array($type, ['SW', 'PA']) && in_array($params['jobTitle'], ['SW', 'PA'])) {
                        $user->remove_role( USERROLE[$type] );
                        $user->add_role( USERROLE[$params['jobTitle']] );
                        // $type = $params['jobTitle'];
                    }
                } catch (Exception $e) {
                    return $this->standardizePayload([], 'Server error.', 500);
                }

                if ($swPaUpdate && $ypHash) {
                    $this->dispatch_app_update('info', $ypHash);
                }

                return $this->standardizePayload([
                            "type" => $type,
                                ], 'Profile edited successfully', 200);
            } else {
                return $this->standardizePayload([], 'Cannot find user', 400);
            }
        }

        public function wpapp_user_app_settings(WP_REST_Request $request) {
            $genderArr = array('m', 'f');
            $genderBirthArr = array('yes', 'no');
            $this->validate_token($request);
            $token = $this->getToken($request);

            $user = $this->get_user_for_token($token);

            // Check if user exists
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];

                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $user_id = $user->ID;
                $show_group_notification = isset($params['show_group_notification']) && $params['show_group_notification'] ? true : false;
                $notifications_email = isset($params['notifications_email']) && $params['notifications_email'] ? true : false;
                $notifications_sms = isset($params['notifications_sms']) && $params['notifications_sms'] ? true : false;
                $notifications_push = isset($params['notifications_push']) && $params['notifications_push'] ? true : false;
                $receive_remainder = isset($params['receive_remainder']) && $params['receive_remainder'] ? true : false;
                $inapp_vibrate = isset($params['inapp_vibrate']) && $params['inapp_vibrate'] ? true : false;
                $inapp_sounds = isset($params['inapp_sounds']) && $params['inapp_sounds'] ? true : false;

                update_user_meta($user_id, 'show_group_notification', $show_group_notification);
                update_user_meta($user_id, 'notifications_email', $notifications_email);
                update_user_meta($user_id, 'notifications_sms', $notifications_sms);
                update_user_meta($user_id, 'notifications_push', $notifications_push);
                update_user_meta($user_id, 'receive_remainder', $receive_remainder);
                update_user_meta($user_id, 'inapp_vibrate', $inapp_vibrate);
                update_user_meta($user_id, 'inapp_sounds', $inapp_sounds);

                $data['show_group_notification'] = $show_group_notification;
                $data['notifications_email'] = $notifications_email;
                $data['notifications_sms'] = $notifications_sms;
                $data['notifications_push'] = $notifications_push;
                $data['receive_remainder'] = $receive_remainder;
                $data['inapp_vibrate'] = $inapp_vibrate;
                $data['inapp_sounds'] = $inapp_sounds;

                return $this->standardizePayload($data, 'App settings updated', 200);
            }
        }

        public function wpnotify_update_push_token(WP_REST_Request $request) {
            $this->validate_token($request);
            // print_r($params);
            // die;
        }

        public function wpapp_forgot_password_email(WP_REST_Request $request) {
            global $wpdb;
            $params = $request != null ? (array) $request->get_params() : null;

            $nonce = $params['nonce'];

            if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                return $this->standardizePayload([], 'Invalid nonce', 570);
            }
            $email = sanitize_email($params['email']);

            $userData = get_user_by('email', $email);
            if (true == email_exists($email)) {
                $user_login = $userData->user_login;
                $strtotime = strtotime('now');
                $wpdb->update($wpdb->prefix . 'users',
                        array('user_status' => $strtotime),
                        array("id" => $userData->ID),
                        array('%s'),
                        array('%d')
                );
                $hash = $email . SALT . $strtotime;
                $hash = md5($hash);
                $key = get_password_reset_key($userData);
                $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
                $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
                $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
                $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
                $message .= get_site_url(null, '/resetPassword?hash=' . $hash);
                $headers = "";
                $emailSent = wp_mail($email, "password reset", $message, $headers);
                if ($emailSent) {
                    return $this->standardizePayload([
                                'user_id' => $userData->ID,
                                'message' => 'Password reset email sent.'
                                    ], 'Please check your email inbox. An email with further instruction was sent to the submitted address', 200);
                } else {
                    return $this->standardizePayload([], 'Email sending fail', 500);
                }
            } else {
                return $this->standardizePayload([], 'User does not exists', 404);
            }
        }

        public function wpapp_verify_token(WP_REST_Request $request) {
            $token = $this->getToken($request);
            $user = $this->get_user_for_token($token);
            if ($user instanceof WP_User) {
                $user_id = $user->ID . SALT;
                $hashed = md5($user_id);
                return $this->standardizePayload(["status" => true, 'id' => $hashed], 'Valid token', 200);
            } else{
                return $this->standardizePayload(["status"=>false], 'Invalid Token', 400);
            }
        }

        public function wpapp_check_link(WP_REST_Request $request) {
            global $wpdb;
            $salt = SALT;
            $params = $request != null ? (array) $request->get_params() : null;

            $nonce = $params['nonce'];

            if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                return $this->standardizePayload([], 'Invalid nonce', 570);
            }
            $hash = $params['hash'];
            $sql = "SELECT ID FROM $wpdb->users WHERE MD5(CONCAT(user_email,'$salt',user_status)) = '$hash'";
            $id = $wpdb->get_var($sql);
            if(intval($id) > 0){
                $securityQuestion = get_user_meta($id, "security_question", true);
                $securityAnswer = get_user_meta($id, "security_answer", true);
                return $this->standardizePayload(["status"=>true, "security_question" => $securityQuestion, "security_answer" => $securityAnswer], 'Valid link', 200);
            }else{
                return $this->standardizePayload(["status"=>false], 'Password link expired', 400);
            }
        }
        public function wpapp_get_young_persons(WP_REST_Request $request){
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
                $userid = $user->ID;
                $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'sw_pa_email' AND (meta_value LIKE '%\"$userid\"%' OR meta_value LIKE '%:$userid;%')";
                $ypIds = $wpdb->get_results($sql);

                if(empty($ypIds) || $ypIds==""){
                    return $this->standardizePayload([], 'No young person found', 400);
                }else{
                    $userData = [];
                    $cnt = 0;
                    $pathwayApiObj = new class_pathway_api();

                    foreach($ypIds as $ypId){
                        $avatar = get_user_meta($ypId->user_id, 'avatar', true);
                        $userData[$cnt]['id'] = md5($ypId->user_id.$salt);
                        $userData[$cnt]['avatar'] = $avatar ? AVATAR_URL.$avatar : "";
                        $userData[$cnt]['firstName'] = get_user_meta($ypId->user_id, 'first_name', true);
                        $userData[$cnt]['lastName'] = get_user_meta($ypId->user_id, 'last_name', true);
                        $securityAns = get_user_meta($ypId->user_id, 'security_answer', true);
                        if(isset($securityAns) && $securityAns!=""){
                            $userData[$cnt]['registered'] = true;
                        }else{
                            $userData[$cnt]['registered'] = false;
                        }
                        if ($userData[$cnt]['registered']) {
                            $pathwayPlans = $pathwayApiObj->wpapp_get_pathway(false, $ypId->user_id);

                            if (isset($pathwayPlans['current'][0])) {
                                $userData[$cnt]['status'] = 'ends:<br>'.date("d/m/Y",strtotime($pathwayPlans['current'][0]->expiry_date));
                            } elseif (isset($pathwayPlans['next'][0])) {
                                $userData[$cnt]['status'] = 'needs signing by:<br>'.date("d/m/Y",strtotime($pathwayPlans['next'][0]->due_date));
                            } else {
                                $userData[$cnt]['status'] = 'registered';
                            }
                        } else {
                            $userData[$cnt]['status'] = 'not registered';
                        }
                        $cnt++;
                    }
                    return $this->standardizePayload($userData, 'List of young person', 200);
                }

            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
        public function wpapp_remove_yp_from_swpa(WP_REST_Request $request){
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
                $swpaid = $user->ID; // sw/pa id
                $ypid = $this->get_userid_from_hash($params['user_id']);
                try{
                    $sql = "SELECT meta_value FROM $wpdb->usermeta WHERE (user_id = '$ypid') AND (meta_key = 'sw_pa_email' AND (meta_value LIKE '%\"$swpaid\"%' OR meta_value LIKE '%:$swpaid;%'))";
                    $swpaids = $wpdb->get_var($sql);
                    $swpaids = maybe_unserialize($swpaids);
                    if (($key = array_search($swpaid, $swpaids)) !== false) {
                        unset($swpaids[$key]);
                    }
                    update_user_meta($ypid, 'sw_pa_email', $swpaids);
                    return $this->standardizePayload([], 'YP user removed successfully', 200);
                }catch(Exception $e){
                    return $this->standardizePayload([], 'Internal server error', 500);
                }

            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }

        public function wpapp_add_yp_ids(WP_REST_Request $request){
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
                $swpaid = $user->ID;
                $swpadata = get_userdata($swpaid);
                $role = $swpadata->roles[0];
                if($role == "social_worker"){
                    $msguser = "social worker";
                }else if($role == "personal_assistant"){
                    $msguser = "personal assistant";
                }else{
                    return $this->standardizePayload([], 'User must be social worker or personal assistant', 404);
                }
                $ypids = $params['yp_ids'];
                $ypids = explode(',',$ypids);
                foreach($ypids as $yphash){
                    $yphash = trim($yphash,"'");
                    $ypid = $this->get_userid_from_hash($yphash);
                    $swpaids = (array) get_user_meta($ypid,'sw_pa_email',true);
                    $swpaids[] = (string) $swpaid;
                    $swpaids = array_values(array_unique(array_filter($swpaids)));
                    update_user_meta($ypid,'sw_pa_email',$swpaids);
                }
                return $this->standardizePayload([], 'Young persons assigned to social worker', 200);
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
        public function wpapp_get_all_yp(WP_REST_Request $request){
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
                $ypusers = get_users( array( 'role__in' => array( 'young_person') ) );
                $userData = [];
                $cnt = 0;
                $myId = $user->ID;
                $myRole = '';
                if(is_array($user->roles) && !empty($user->roles)){
                    if (in_array(USERROLE['PA'], $user->roles)) {
                        $myRole = 'PA';
                    }
                    if (in_array(USERROLE['SW'], $user->roles)) {
                        $myRole = 'SW';
                    }
                }
                foreach ( $ypusers as $user ) {
                    $userId = $user->ID;
                    $userData[$cnt]['firstName'] = get_user_meta($userId, 'first_name', true);
                    $userData[$cnt]['lastName'] = get_user_meta($userId, 'last_name', true);
                    $userData[$cnt]['mobile'] = get_user_meta($userId, 'phone_number', true);
                    $avatar = get_user_meta($userId, 'avatar', true);
                    $userData[$cnt]['avatar'] = $avatar ? AVATAR_URL.$avatar : "";
                    $userData[$cnt]['registered'] = ''; // to be completed
                    $userData[$cnt]['dueSigningDate'] = ''; // to be completed
                    $userData[$cnt]['swName'] = '';
                    $sw_pa_ids = (array) get_user_meta($userId,'sw_pa_email',true);
                    $userData[$cnt]['isMyCaseLoad'] = false;
                    $userData[$cnt]['hasSW'] = false;
                    $userData[$cnt]['leadBy'] = '';
                    $pa = false;
                    $sw = false;
                    if(!empty($sw_pa_ids)){
                        foreach($sw_pa_ids as $sw_pa_id) {
                            $swpaData = get_userdata($sw_pa_id);
                            if (in_array(USERROLE['PA'], $swpaData->roles)) {
                                $pa = $swpaData;
                                $pa->full_name = get_user_meta($pa->ID, 'first_name', true) . ' ' . get_user_meta($pa->ID, 'last_name', true);;
                            }

                            if (in_array(USERROLE['SW'], $swpaData->roles)) {
                                $sw = $swpaData;
                                $sw->full_name = get_user_meta($sw->ID, 'first_name', true) . ' ' . get_user_meta($sw->ID, 'last_name', true);;
                            }
                        }
                    }
                    if ($pa && $myRole == 'PA' && $pa->ID == $myId) {
                        $userData[$cnt]['isMyCaseLoad'] = true;
                    }
                    if ($sw && $myRole == 'SW' && $sw->ID == $myId) {
                        $userData[$cnt]['isMyCaseLoad'] = true;
                    }
                    if (!$userData[$cnt]['isMyCaseLoad']) {
                        if ($myRole == 'PA' && $pa) {
                            $userData[$cnt]['leadBy'] = $pa->full_name;
                            $userData[$cnt]['hasSW'] = true;
                        }
                        if ($myRole == 'SW' && $sw) {
                            $userData[$cnt]['leadBy'] = $sw->full_name;
                            $userData[$cnt]['hasSW'] = true;
                        }
                        if (empty($userData[$cnt]['leadBy']) && $sw && $pa) {
                            $userData[$cnt]['leadBy'] = $sw->full_name;
                            $userData[$cnt]['hasSW'] = true;
                        }
                    }
                    $userData[$cnt]['id'] = md5($userId.$salt);
                    $cnt++;
               }
                return $this->standardizePayload($userData, 'List of all yp users', 200);
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
        public function wpapp_push_notification(WP_REST_Request $request){
            global $wpdb;
            $this->validate_token($request);
            $token = $this->getToken($request);
            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;
                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $swpaId = $user->ID;
                $dueDate = $params['due_date'];
                $yphashs = $params['yp_ids'];
                if (empty($yphashs)) {
                    return $this->standardizePayload([], 'No YP selected', 400);
                }
                $description = $params['description'];
                $file = $params['file'];
                $name = $params['file_name'];
                $filename = $attachment = '';
                if (!empty($file)) {
                    $file = explode(',', $file);
                    $data = base64_decode($file[1]);
                    $path = FILE_PATH;
                    if (!file_exists($path)) {
                        mkdir($path);
                    }
                    $filename = $user->ID.'_'.time();
                    $file = explode('/', $file[0])[1];
                    $extension = explode(';', $file)[0];
                    if ($extension != 'pdf') {
                        return $this->standardizePayload([], 'Error! You need to upload PDF.', 500);
                    }
                    $filename .= '.'.$extension;
                    if (file_put_contents($path.$filename, $data)) {
                        $attachment = FILE_URL.$filename;
                        $attachment = str_replace(' ','',$attachment);
                    }
                }
                if(!empty($yphashs)){
                    foreach($yphashs as $yphash){
                        $ypid = $this->get_userid_from_hash($yphash);
                        $yp_info = get_userdata($ypid);
                        $yp_email = $yp_info->user_email;
                        $message = __('This is the notification from the BREN') . "\r\n\r\n";
                        $message .= __('Description: '.$description) . "\r\n\r\n";
                        $message .= __('Due date: '.$dueDate) . "\r\n\r\n";
                        if (!empty($attachment)) {
                            $message .= __('Please check attached file') . "\r\n\r\n";
                        }
                        $headers = "";
                        $emailSent = wp_mail( $yp_email, "Notification from Brent", $message, $headers , $attachment);
                        $data = [];
                        if($emailSent == 1){
                            $table = $wpdb->prefix.'users_tasks';
                            $data['user_id'] = $swpaId;
                            $data['assignee_id'] = $ypid;
                            $data['due_date'] = $dueDate;
                            $data['description'] = $description;
                            $data['type'] = 'event';
                            $data['filename'] = $filename;
                            $data['file_name'] = $name;
                            $type = array('%d','%d','%s','%s','%s','%s');
                            $wpdb->insert(
                                $table,
                                $data,
                                $type
                            );
                            $eventId = $wpdb->insert_id;
                            $this->dispatch_app_update('get_task', $yphash);
                        }else{
                            $txt = "faild to send notification to $yp_email. \r\n";
                            $txt .= "----------------------------------";
                            $myfile = file_put_contents(trailingslashit(get_stylesheet_directory()).'/userlog.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
                        }
                    }
                }
                return $this->standardizePayload($attachment, 'Notification sent successfully', 200);
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
    }

    $usersApiObj = new class_users_api();
    $usersApiObj->usersApi();
}