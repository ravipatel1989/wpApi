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

    class class_tasks_api extends class_json_api {

        public function taskApi() {
            add_action('rest_api_init', function () {
                $prefixApiV1 = 'wpapp/api/v2';
                $prefixV1 = 'wpnotify/v1';
                // AUTH

                register_rest_route($prefixApiV1, '/task/get_assigned_users', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_task_assign_to_user'],
                ]);
                register_rest_route($prefixApiV1, '/task/save_task', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_save_task_function'],
                ]);
                register_rest_route($prefixApiV1, '/task/get_task', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_task_function'],
                ]);
                register_rest_route($prefixApiV1, '/task/task_completed', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_task_completed_function'],
                ]);
                register_rest_route($prefixApiV1, '/task/update_task', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_task_update_function'],
                ]);
                register_rest_route($prefixApiV1, '/task/delete_task', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_task_delete_function'],
                ]);
            }
            );
        }

        public function wpapp_task_assign_to_user(WP_REST_Request $request) {
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

                if (isset($params['user_id'])) {
                    $getuserdata = get_userdata($userId);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if (in_array($type, ['SW', 'PA'])) {
                        $userId = $this->get_userid_from_hash($params['user_id']);
                    } else {
                        return $this->standardizePayload([], 'Invalid role', 570);
                    }
                }

                $socialWorkerIds = get_user_meta($userId, 'sw_pa_email', true);

                if (empty($socialWorkerIds) || intval($socialWorkerIds[0]) == 0) {
                    return $this->standardizePayload([], 'Social worker not found', 404);
                } else {
                    $data = [];
                    $count = 0;
                    foreach($socialWorkerIds as $socialWorkerId){
                        $avatar = get_user_meta($socialWorkerId, 'avatar', true);
                        $first_name = get_user_meta($socialWorkerId, 'first_name', true);
                        $last_name = get_user_meta($socialWorkerId, 'last_name', true);
                        $phone_number = get_user_meta($socialWorkerId, 'phone_number', true);
                        $user_name = $first_name . ' ' . $last_name;
                        $user_info = get_userdata($socialWorkerId);
                        $user_role = $user_info->roles;
                        $user_role = $user_role[0];
                        $type = array_search($user_role,USERROLE);

                        $socialWorkerId = $socialWorkerId.SALT;
                        $socialWorkerId = md5($socialWorkerId);
                        $data[$count]['id'] = $socialWorkerId;
                        $data[$count]['type'] = $type;
                        $data[$count]['user_name'] = $user_name;
                        $data[$count]['avatar'] = $avatar ? AVATAR_URL.$avatar : "";
                        $data[$count]['first_name'] = $first_name;
                        $data[$count]['last_name'] = $last_name;
                        $data[$count]['phone_number'] = $phone_number;
                        $count++;
                    }

                    return $this->standardizePayload($data, 'List of assignee users', 200);
                }
            } else {
                return $this->standardizePayload([], 'Cannot find user', 400);
            }
        }

        public function wpapp_save_task_function(WP_REST_Request $request) {
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

                $user_id = $params['user_id'];
                $user_id = $this->get_userid_from_hash($user_id);
                $pp_id = $params['pp_id'];
                $pp_id = $this->get_pathwayid_from_hash($pp_id);
                $assignee_id = $params['assignee_id'];
                $assignee_id_hash = $params['assignee_id'];
                $assignee_id = $this->get_userid_from_hash($assignee_id);
                $due_date = $params['due_date'];
                $description = $params['description'];
                $badge = $params['badge'];
                $section = $params['section'];
                $type = !isset($params['type']) || empty($params['type']) ? 'task' : $params['type'];
                if (intval($user_id) == 0) {
                    return $this->standardizePayload([], 'User id not found', 404);
                }
                if (intval($assignee_id) == 0) {
                    return $this->standardizePayload([], 'Assignee id not found', 404);
                }
                try {
                    $insertData = $wpdb->query($wpdb->prepare("INSERT INTO `{$wpdb->prefix}users_tasks` (`user_id`,`pp_id`, `assignee_id`, `due_date`, `description`, `badge`, `section`, `type`) VALUES (%d, %d, %d, %s, %s, %s, %s, %s)", $user_id, $pp_id, $assignee_id, $due_date, $description, $badge, $section, $type));
                } catch (Exception $e) {
                    return $this->standardizePayload([], 'server error', 500);
                }

                if ($insertData == 1) {
                    if ($assignee_id !== $user_id) {
                        $this->dispatch_app_update('get_task', $assignee_id_hash, 'A new task was assigned');
                    }
                    return $this->standardizePayload([
                                'result' => "success",
                                    ], 'Task assigned successfully', 200);
                } else {
                    return $this->standardizePayload([], 'server error', 500);
                }
            } else {
                return $this->standardizePayload([], 'Cannot find user', 400);
            }
        }

        public function wpapp_get_task_function(WP_REST_Request $request) {
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
                $user_id = $user->ID;
                $getuserdata = get_userdata($user_id);
                $role = $getuserdata->roles[0];
                $type = array_search($role, USERROLE);
                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    if ($type == 'SW' || $type == 'PA') {
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
                } elseif ($type == 'SW' || $type == 'PA') {
                    // we need to load tasks for all his YPs as well
                    $sql = "SELECT GROUP_CONCAT(DISTINCT user_id) ids FROM $wpdb->usermeta WHERE meta_key = 'sw_pa_email' AND (meta_value LIKE '%\"$user_id\"%' OR meta_value LIKE '%:$userid;%')";
                    $ypIds = $wpdb->get_var($sql);
                    if (!empty($ypIds)) {
                        $user_id = $ypIds . ',' . $user_id ;
                    }
                }
                $where = (($type == 'SW' || $type == 'PA') && empty($params['yp_id'])) ? " AND type = 'task'" : "";
                $results = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}users_tasks` WHERE (`user_id` IN ($user_id) OR `assignee_id` IN ($user_id))".$where);
                if(isset($results[0]) && !empty($results[0])){
                    foreach($results as &$result){
                        $id = $result->id.SALT;
                        $id = md5($id);
                        $result->id = $id;
                        $pp_id = $result->pp_id.SALT;
                        $pp_id = md5($pp_id);
                        $result->pp_id = $pp_id;
                        $badgeId = $result->badge;
                        $badgeTitle = get_the_title($badgeId);
                        $badgeIcon = get_field("icon", $badgeId);
                        $badgeStatus = get_field("status", $badgeId);
                        $result->badge = array('id' => $badgeId, 'title'=>$badgeTitle,'icon'=>$badgeIcon,"status"=>$badgeStatus);
                        $userid = $result->user_id.SALT;
                        $userid = md5($userid);
                        $assignee_id = $result->assignee_id.SALT;
                        $assignee_id =  md5($assignee_id);
                        $completedBy = $result->completed_by.SALT;
                        $completedBy =  md5($completedBy);
                        $result->completed_by = $completedBy;
                        //assignee data
                        $assigneeData  = get_userdata($result->assignee_id);
                        $firstname = get_user_meta($assigneeData->ID,'first_name',true);
                        $lastname = get_user_meta($assigneeData->ID,'last_name',true);
                        $avatar = get_user_meta($assigneeData->ID,'avatar',true);
                        $avatar = $avatar ? AVATAR_URL.$avatar : "";
                        $name = $firstname.' '.$lastname;
                        $roles = $assigneeData->roles[0];
                        $role = array_search($roles, USERROLE);
                        $result->assignedTo = array('id'=>$assignee_id,'name'=>$name,'image'=>$avatar,'role'=>$role);
                        unset($result->assignee_id);
                        //assignee data

                        $assignByData  = get_userdata($result->user_id);
                        $firstname = get_user_meta($assignByData->ID,'first_name',true);
                        $lastname = get_user_meta($assignByData->ID,'last_name',true);
                        $avatar = get_user_meta($assignByData->ID,'avatar',true);
                        $avatar = $avatar ? AVATAR_URL.$avatar : "";
                        $name = $firstname.' '.$lastname;
                        $roles = $assignByData->roles[0];
                        $role = array_search($roles, USERROLE);
                        $result->assignedBy = array('id'=>$userid,'name'=>$name,'image'=>$avatar,'role'=>$role);

                        $result->user_id = $userid;

                        $result->file = NULL;
                        if (!empty($result->filename)) {
                            $size = 0;
                            $file = FILE_PATH.$result->filename;
                            if (file_exists($file)) {
                                $size = filesize($file);
                                $unit = false;
                                if( (!$unit && $size >= 1<<30) || $unit == "GB")
                                    $size = number_format($size/(1<<30),2)."GB";
                                elseif( (!$unit && $size >= 1<<20) || $unit == "MB")
                                    $size = number_format($size/(1<<20),2)."MB";
                                elseif( (!$unit && $size >= 1<<10) || $unit == "KB")
                                    $size = number_format($size/(1<<10),2)."KB";
                                else
                                    $size = number_format($size)." B";

                                $file_url = FILE_URL.$result->filename;
                                $name = empty($result->file_name) ? $result->filename : $result->file_name;

                                $result->file = array("size" => $size, "url" =>  $file_url, "name" => $name);
                            }
                        }
                       
                       unset($result->filename);
                       unset($result->file_name);
                    }
                    return $this->standardizePayload([
                        'tasks' => $results,
                    ], 'Success', 200);
                }else{
                    return $this->standardizePayload([], 'Task not found', 404);
                }
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }

        public function wpapp_task_completed_function(WP_REST_Request $request){
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
                $userid = $user->ID;
                $id = $params['ticket_id'];
                $status = ($params['status'] == "true") ? "1" : "0";
                $table = $wpdb->prefix.'users_tasks';
                $salt = SALT;

                $completed = $wpdb->query("UPDATE $table SET completed = $status, completed_by = $userid, date_completed = date('Y-m-d') WHERE MD5(CONCAT(id,'$salt'))= '$id'");
                if(isset($completed) && ($completed == 1 || $completed == 0) ){
                    return $this->standardizePayload([
                                'result' => "success",
                            ], 'Task completed successfully', 200);
                }else{
                    return $this->standardizePayload([], 'Server error', 500);
                }
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }

        public function wpapp_task_update_function(WP_REST_Request $request){
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
                $taskhash = $params['task_id'];
                if (empty($taskhash)) {
                     return $this->standardizePayload([], 'Task ID not set', 400);
                }
                $taskid = $this->get_taskid_from_hash($taskhash);
                if (!$taskid) {
                     return $this->standardizePayload([], 'Task not found', 400);
                }
                $userhash = $params['assignee_id'];
                $userid = $this->get_userid_from_hash($userhash);
                $duedate = $params['due_date'];
                $description = $params['description'];
                $badges = $params['badge'];
                if ($userid) {
                    $current_assignee_id = $wpdb->get_var("SELECT assignee_id FROM ".$wpdb->prefix."users_tasks WHERE id = ".$taskid);
                    if ($current_assignee_id != $userid) {
                        $data['pp_id'] = 0;
                        $data['section'] = "";
                    }
                    $data['assignee_id'] = $userid;
                }
                $data['badge'] = $badges;
                $data['due_date'] = $duedate;
                $data['description'] = $description;
                $return = $wpdb->update(
                    $wpdb->prefix.'users_tasks',
                    $data,
                    array('id'=>$taskid),
                    array('%d','%s','%s','%s'),
                    array('%d')
                );
                if ($params['user_id'] && $params['assignee_id']) {
                    $this->dispatch_app_update('get_task', $userhash);
                }
                return $this->standardizePayload('Success', 'Task updated successfully', 200);
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
        public function wpapp_task_delete_function(WP_REST_Request $request){
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
                $taskhash = $params['task_id'];
                $taskid = $this->get_taskid_from_hash($taskhash);
                if(intval($taskid) > 0){
                    $wpdb->delete( $wpdb->prefix.'users_tasks', array( 'id' => $taskid ), array( '%d' ) );
                    return $this->standardizePayload('Success', 'Task deleted successfully', 200);
                }else{
                    return $this->standardizePayload([], 'Task not found', 404);
                }
            }else{
                return $this->standardizePayload([], 'No user found', 400);
            }
        }
    }

    $taskApiObj = new class_tasks_api();
    $taskApiObj->taskApi();
}
