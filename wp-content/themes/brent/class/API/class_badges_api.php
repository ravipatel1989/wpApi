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

    class class_badge_api extends class_json_api {

        public function badgeApi() {
            add_action('rest_api_init', function () {
                $prefixApiV1 = 'wpapp/api/v2';
                $prefixV1 = 'wpnotify/v1';
                // AUTH
                register_rest_route($prefixApiV1, '/badge/get_badges', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_all_badges_function'],
                ]);
                register_rest_route($prefixApiV1, '/badge/assign_badge', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_assign_badge_function'],
                ]);
                register_rest_route($prefixApiV1, '/badge/delete_badge', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_delete_badge_function'],
                ]);
            }
            );
        }

        public function wpapp_get_all_badges_function(WP_REST_Request $request, $type = 'all') {
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
                $type = isset($params['type']) && in_array($params['type'], array("all", "own")) ? $params['type'] : $type;
                $user_id = $user->ID;                
                $getuserdata = get_userdata($user_id);
                $role = $getuserdata->roles[0];
                $userType = array_search($role, USERROLE);

                if (isset($params['yp_id']) && !empty($params['yp_id']) && ($userType == 'SW' || $userType == 'PA')) {

                    $ypid = $this->get_userid_from_hash($params['yp_id']);
                    if ($ypid) {
                        $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                        if (is_array($socialWorkers) && in_array($user_id, $socialWorkers)) {
                            $user_id = $ypid;
                        } else {
                            return $this->standardizePayload([], 'Not allowed', 500);
                        }
                    } else {
                        return $this->standardizePayload([], 'Cannot find user', 500);
                    }
                }
                if ($type == 'own') {
                    $args = array(
                        'post_type'  => 'badge',
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key'     => 'status',
                                'value'   => '1',
                                'compare' => '=',
                            ),
                            array(
                                'key'     => 'user_id',
                                'value'   => $user_id,
                                'compare' => 'LIKE',
                            ),
                        ),
                    );
                }else{
                    $args = array(
                        'post_type' => 'badge',
                        'meta_key' => 'status',
                        'meta_value' => 1,
                        'meta_compare' => '='
                    );
                }

                $query = new WP_Query($args);
                $message = "";
                $data = [];
                $i = 0;
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $id = get_the_ID();
                        $data[$i]['id'] = md5($id.SALT);
                        $data[$i]['title'] = get_the_title();
                        $data[$i]['icon'] = get_field('icon', $id);
                        $i++;
                    }
                }
                wp_reset_postdata();
                if ($params['type'] != 'all') {
                    $badges = $wpdb->get_var($wpdb->prepare("SELECT GROUP_CONCAT(p.ID) badges FROM `{$wpdb->prefix}users_tasks` ut LEFT JOIN `{$wpdb->prefix}posts` p ON (ut.badge = MD5(CONCAT(p.ID, '" . SALT . "'))) WHERE (`user_id` = %d OR `assignee_id` = %d) AND completed = 1 AND badge IS NOT NULL AND badge != ''", $user_id, $user_id));
                    if (!empty($badges)) {
                        $badgesArr = explode(",", $badges);
                        foreach ($badgesArr as $badge) {
                            $data[$i]['id'] = md5($badge . SALT);
                            $data[$i]['title'] = get_the_title($badge);
                            $data[$i]['icon'] = get_field('icon', $badge);
                            $i++;
                        }
                    }
                }
            }
            if ($message != "") {
                return $this->standardizePayload([], $message, 404);
            } else {
                return $this->standardizePayload((array) $data, 'Success', 200);
            }
        }

        public function wpapp_assign_badge_function(WP_REST_Request $request){
            $this->validate_token($request);
            $token = $this->getToken($request);
            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                if (!isset($params['yp_id']) || !isset($params['badge_ids'])) {
                    return $this->standardizePayload([], 'Please check the parameters', 400);
                }
                $yphash = $params['yp_id'];
                $ypid = $this->get_userid_from_hash($yphash);
                if (!empty($ypid)) {
                    $badgeIds = $params['badge_ids'];
                    $badgeIdsArr = explode(',', $badgeIds);
                    if(!empty($badgeIdsArr)){
                        foreach($badgeIdsArr as $badgehash){
                            $badgeId = $this->get_postid_from_hash($badgehash);
                            $badgeUsers = (array)get_post_meta($badgeId,'user_id',true);
                            $badgeUsers[] = $ypid;
                            $badgeUsers = array_unique(array_filter($badgeUsers));
                            update_post_meta($badgeId,'user_id',$badgeUsers);
                        }
                    }else{
                        return $this->standardizePayload([], 'Badge not found', 400);
                    }
                    return $this->standardizePayload('success', 'Badges assigned to user successfully', 200);
                } else {
                    return $this->standardizePayload([], 'YP User not found', 404);
                }
            }else{
                return $this->standardizePayload([], 'User not found', 404);
            }
        }

        public function wpapp_delete_badge_function(WP_REST_Request $request){
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
                if (!isset($params['yp_id']) || !isset($params['badge_id'])) {
                    return $this->standardizePayload([], 'Please check the parameters', 400);
                }
                $yphash = $params['yp_id'];
                $ypid = $this->get_userid_from_hash($yphash);
                if (!empty($ypid)) {
                    $badgehash = $params['badge_id'];
                    $badgeId = $this->get_postid_from_hash($badgehash);
                    if (!empty($badgeId)) {
                        $badgeUsers = get_post_meta($badgeId,'user_id',true);
                        if (is_array($badgeUsers) && in_array($ypid, $badgeUsers)) {
                            $key = array_search($ypid, $badgeUsers);
                            unset($badgeUsers[$key]);
                            update_post_meta($badgeId,'user_id',$badgeUsers);
                        }
                        $wpdb->query("UPDATE ".$wpdb->prefix."users_tasks SET badge = NULL WHERE badge = '".$badgehash."'");
                    } else {
                        return $this->standardizePayload([], 'Badge Id not set', 400);
                    }
                    return $this->standardizePayload('success', 'Badges deleted from user successfully', 200);
                } else {
                    return $this->standardizePayload([], 'YP User not found', 404);
                }
            }else{
                return $this->standardizePayload([], 'User not found', 404);
            }
        }
    }

    $badgeApiObj = new class_badge_api();
    $badgeApiObj->badgeApi();
}
