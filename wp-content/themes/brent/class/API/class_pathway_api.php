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

    class class_pathway_api extends class_json_api {

        private $_pathway_table = false;

        function __construct(){
            global $wpdb;
            $this->_pathway_table = $wpdb->prefix.'pathway_plans';
        }

        public function pathwayApi() {
            global $wpdb;
            add_action('rest_api_init', function () {
                $prefixApiV1 = 'wpapp/api/v2';
                $prefixV1 = 'wpnotify/v1';
                // AUTH

                register_rest_route($prefixApiV1, '/pathway/create_pp', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_create_pathway_plan'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/get_pp', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_pathway'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/get_pp_sw_pa', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_pathway_sw_pa'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/get_pp_by_id', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_pathway_by_id'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/update_pp', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_update_pathway'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/delete_pp', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_delete_pathway'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/get_preview', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_get_preview'],
                ]);
                register_rest_route($prefixApiV1, '/pathway/override', [
                    'methods' => 'POST',
                    'callback' => [$this, 'wpapp_override_pathway'],
                ]);
            }
            );
        }

        public function wpapp_create_pathway_plan(WP_REST_Request $request){
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
                $data = $type = array();
                $data['created_by'] = $user->ID;
                $type[] = '%d';

                $data['created_date'] = date('Y-m-d');
                $type[] = '%s';

                $user_id = false;

                if(isset($params['user_id']) && $params['user_id']!=""){
                    $user_id = $this->get_userid_from_hash($params['user_id']);
                }

                if (empty($user_id)) {
                    return $this->standardizePayload([], 'You need to set user_id', 400);
                }
                    $data['user_id'] = $user_id;
                    $type[] = '%s';

                if(isset($params['due_date']) && $params['due_date']!=""){
                    $data['due_date'] = $params['due_date'];
                } else {
                    $data['due_date'] = date('Y-m-d', strtotime('+8 weeks'));
                }
                $type[] = '%s';

                $currentDate = date('Y-m-d');

                $nextPathwayPlan = $wpdb->get_var("SELECT id FROM $this->_pathway_table as pp WHERE pp.user_id IN ($user_id) AND '$currentDate' < due_date AND signed_by_sw = 'no' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");

                if ($nextPathwayPlan) {
                    return $this->standardizePayload(NULL, 'Next Pathway plan already exists', 200);
                }


                $currentPathwayPlan = $wpdb->get_row("SELECT pp.* FROM $this->_pathway_table as pp WHERE pp.user_id = $user_id AND '$currentDate' >= date_signed_by_sw AND TIMESTAMPDIFF(MONTH, date_signed_by_sw, '$currentDate') <= 6 AND signed_by_sw = 'yes' AND signed_by_yp = 'yes' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");

                if ($currentPathwayPlan) {
                    $data['first_name'] = $currentPathwayPlan->first_name;
                    $type[] = '%s';
                    $data['last_name'] = $currentPathwayPlan->last_name;
                    $type[] = '%s';
                    $data['dob'] = $currentPathwayPlan->dob;
                    $type[] = '%s';
                    $data['gender'] = $currentPathwayPlan->gender;
                    $type[] = '%s';
                    $data['gender_birth'] = $currentPathwayPlan->gender_birth;
                    $type[] = '%s';
                    $data['disabled'] = $currentPathwayPlan->disabled;
                    $type[] = '%s';
                    $data['communication_needs'] = $currentPathwayPlan->communication_needs;
                    $type[] = '%s';
                    $data['legal_status'] = $currentPathwayPlan->legal_status;
                    $type[] = '%s';
                    $data['leaving_care_status'] = $currentPathwayPlan->leaving_care_status;
                    $type[] = '%s';
                    $data['Immigration_status'] = $currentPathwayPlan->Immigration_status;
                    $type[] = '%s';
                    $data['who_has_got_my_birth_certificate'] = $currentPathwayPlan->who_has_got_my_birth_certificate;
                    $type[] = '%s';
                    $data['birth_certificate'] = $currentPathwayPlan->birth_certificate;
                    $type[] = '%s';
                    $data['birth_certificate_filename'] = empty($currentPathwayPlan->birth_certificate_filename)?"":FILE_URL.$currentPathwayPlan->birth_certificate_filename;
                    $type[] = '%s';
                    $data['who_has_got_my_passport'] = $currentPathwayPlan->who_has_got_my_passport;
                    $type[] = '%s';
                    $data['passport'] = $currentPathwayPlan->passport;
                    $type[] = '%s';
                    $data['passport_filename'] = empty($currentPathwayPlan->passport_filename)?"":FILE_URL.$currentPathwayPlan->passport_filename;
                    $type[] = '%s';
                    $data['ni_number'] = $currentPathwayPlan->ni_number;
                    $type[] = '%s';
                } else {
                    $data['first_name'] = get_user_meta($data['user_id'], 'first_name', true);
                    $type[] = '%s';
                    $data['last_name'] = get_user_meta($data['user_id'], 'last_name', true);
                    $type[] = '%s';
                    $data['dob'] = date("Y-m-d", strtotime(str_replace("/","-",get_user_meta($data['user_id'], 'dob', true))));
                    $type[] = '%s';
                    $data['gender'] = get_user_meta($data['user_id'], 'gender', true);
                    $type[] = '%s';
                    $data['gender_birth'] = get_user_meta($data['user_id'], 'gender_birth', true);
                    $type[] = '%s';
                }
                $data['signed_by_sw'] = 'no';
                $type[] = '%s';
                $data['signed_by_yp'] = 'no';
                $type[] = '%s';
                $data['started'] = 'no';
                $type[] = '%s';
                $data['past'] = 'no';
                $type[] = '%s';
                $data['cancelled'] = 'no';
                $type[] = '%s';
                $data['deleted'] = 'no';
                $type[] = '%s';
                $data['date_signed_by_sw'] = "";
                $type[] = '%s';
                $data['date_signed_by_yp'] = "";
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
                        $pathway_id = md5($pathway_id.SALT);
                        $data['id'] = $pathway_id;
                        $data['user_id'] = md5($data['user_id'].SALT);
                        $data['created_by'] = md5($data['created_by'].SALT);
                        $data['open_date'] = date('Y-m-d', strtotime('-8 weeks', strtotime($data['due_date'])));
                        $data['opened'] = (time() > strtotime('-8 weeks', strtotime($data['due_date']))) ? 'yes' : 'no';
                        $data['overall_plan_feeling'] = "";
                        $data['overall_care_plan'] = "";
                        $data['attempts'] = "";
                        $data['family_relationship'] = "";
                        $data['workers_assessment'] = "";
                        $data['contact_arrangements'] = "";
                        $data['date_of_visiting'] = "";
                        $data['seen_alone'] = "";
                        $data['comments'] = "";
                        $data['outside_statutory'] = "";
                        $data['visits'] = [];
                        $data['education_feeling'] = "";
                        $data['education_working_well'] = "";
                        $data['education_worried_about'] = "";
                        $data['education_current_establishment'] = "";
                        $data['education_address'] = "";
                        $data['education_phone'] = "";
                        $data['education_support_contact'] = "";
                        $data['education_date'] = "";
                        $data['education_responsible_la'] = "";
                        $data['education_next_steps'] = "";
                        $data['education_long_term_goals'] = "";
                        $data['education_contingency'] = "";
                        $data['managing_feeling'] = "";
                        $data['managing_working_well'] = "";
                        $data['managing_worried_about'] = "";
                        $data['managing_next_steps'] = "";
                        $data['managing_long_term_goals'] = "";
                        $data['managing_contingency'] = "";
                        $data['health_feeling'] = "";
                        $data['health_working_well'] = "";
                        $data['health_worried_about'] = "";
                        $data['health_next_steps'] = "";
                        $data['health_long_term_goals'] = "";
                        $data['health_contingency'] = "";
                        $data['money_feeling'] = "";
                        $data['money_working_well'] = "";
                        $data['money_worried_about'] = "";
                        $data['money_next_steps'] = "";
                        $data['money_long_term_goals'] = "";
                        $data['money_contingency'] = "";
                        $data['health_allergies'] = "";
                        $data['mental_health'] = "";
                        $data['dob'] = date("d/m/Y", strtotime($data['dob']));
                        $data['date_of_visiting'] = !empty($data['date_of_visiting']) ? date("d/m/Y", strtotime($data['date_of_visiting'])) : NULL;
                        return $this->standardizePayload($data, 'Pathway plan added successfully', 200);
                    }
                } catch (Exception $e) {
                    return $this->standardizePayload([], 'server error', 500);
                }
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }
        public function wpapp_get_pathway($request, $ypIds = false){//WP_REST_Request
            global $wpdb;
            $salt = SALT;
            if (!$ypIds) {
                $this->validate_token($request);
                $token = $this->getToken($request);
                $user = $this->get_user_for_token($token);
            }
            if (!empty($ypIds) || (($user instanceof WP_User) && $user->exists())) {
                $params = $request != null ? (array) $request->get_params() : null;

                if (!$ypIds) {
                    $nonce = $params['nonce'];
                    if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                        return $this->standardizePayload([], 'Invalid nonce', 570);
                    }
                }

                $pathwayType = !isset($params['pathway']) ? 'all' : $params['pathway'];
                if(!in_array($pathwayType, array('current','next','past','all'))){
                    return $this->standardizePayload([], 'Invalid "pathway" parameter, must be "current", "next", "past" or "all"', 500);
                }
                $userId = !$ypIds ? $user->ID : $ypIds;
                $pathwayTable = $this->_pathway_table;
                $visitTable = $wpdb->prefix.'pathway_visits';
                $currentDate = date('Y-m-d');
                $pathwayPlans = ['current' => [], 'next' => [], 'past' => []];
                if($pathwayType == "current" || $pathwayType == 'all'){
                    $pathways = $wpdb->get_results("SELECT pp.* FROM $pathwayTable as pp WHERE pp.user_id IN ($userId) AND '$currentDate' >= date_signed_by_sw AND TIMESTAMPDIFF(MONTH, date_signed_by_sw, '$currentDate') <= 6 AND signed_by_sw = 'yes' AND signed_by_yp = 'yes' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");

                    if(isset($pathways[0]) && !empty($pathways[0])){
                        foreach($pathways as &$pathway){
                            $visits = $wpdb->get_results("SELECT id,visit,member_name,professionals_name,date,update_visit FROM $visitTable WHERE pathway_id = '$pathway->id'");
                            $pathway->visits = [];
                            if(isset($visits[0]) && !empty($visits[0])){
                                foreach($visits as &$visit){
                                    $visit->id = md5($visit->id.$salt);
                                    if ($visit->date == '0000-00-00') {
                                        $visit->date = NULL;
                                    } else {
                                        $visit->date = date("d/m/Y", strtotime($visit->date));
                                    }
                                }
                                $pathway->visits = $visits;
                            }
                            $pathway->id = md5($pathway->id.$salt);
                            $avatar = get_user_meta($pathway->user_id, 'avatar', true);
                            $pathway->avatar = (string) ($avatar ? AVATAR_URL.$avatar : "");
                            $pathway->file = $this->wpapp_generate_pdf($pathway->user_id, $pathway->id);
                            $pathway->fileTitle = 'MY CURRENT PATHWAY PLAN';
                            $pathway->user_id = md5($pathway->user_id.$salt);
                            $pathway->created_by = md5($pathway->created_by.$salt);
                            $pathway->expiry_date = date('Y-m-d', strtotime('+6 months', strtotime($pathway->date_signed_by_sw)));
                            $pathway->started = empty($pathway->started) ? 'no' : $pathway->started;
                            $pathway->past = empty($pathway->past) ? 'no' : $pathway->past;
                            $pathway->passport = empty($pathway->passport)?"":FILE_URL.$pathway->passport;
                            $pathway->birth_certificate = empty($pathway->birth_certificate)?"":FILE_URL.$pathway->birth_certificate;
                            $pathway->dob = date("d/m/Y", strtotime($pathway->dob));
                            $pathway->date_of_visiting = !empty($pathway->date_of_visiting) ? date("d/m/Y", strtotime($pathway->date_of_visiting)) : NULL;
                        }
                        $data = $pathways;
                        $message = 'List of current pathway plans';
                        $statusCode = 200;
                        if ($pathwayType == 'all') {
                            $pathwayPlans['current'] = $data;
                            $data = [];
                        }
                    }else{
                        $data = [];
                        $message = 'No record found.';
                        $statusCode = 400;
                    }
                }
                if($pathwayType == "next" || $pathwayType == 'all'){
                    $pathways = $wpdb->get_results("SELECT pp.* FROM $pathwayTable as pp WHERE pp.user_id IN ($userId) AND '$currentDate' < due_date AND signed_by_sw = 'no' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");// AND signed_by_yp = 'no'
                    if(isset($pathways[0]) && !empty($pathways[0])){
                        foreach($pathways as &$pathway){
                            $visits = $wpdb->get_results("SELECT id,visit,member_name,professionals_name,date,update_visit FROM $visitTable WHERE pathway_id = '$pathway->id'");
                            $pathway->visits = [];
                            if(isset($visits[0]) && !empty($visits[0])){
                                foreach($visits as &$visit){
                                    $visit->id = md5($visit->id.$salt);
                                    if ($visit->date == '0000-00-00') {
                                        $visit->date = NULL;
                                    } else {
                                        $visit->date = date("d/m/Y", strtotime($visit->date));
                                    }
                                }
                                $pathway->visits = $visits;
                            }
                            $pathway->id = md5($pathway->id.$salt);
                            $avatar = get_user_meta($pathway->user_id, 'avatar', true);
                            $pathway->avatar = (string) ($avatar ? AVATAR_URL.$avatar : "");
                            $pathway->user_id = md5($pathway->user_id.$salt);
                            $pathway->created_by = md5($pathway->created_by.$salt);
                            $pathway->open_date = date('Y-m-d', strtotime('-8 weeks', strtotime($pathway->due_date)));
                            $pathway->opened = (time() > strtotime('-8 weeks', strtotime($pathway->due_date))) ? 'yes' : 'no';
                            $pathway->started = empty($pathway->started) ? 'no' : $pathway->started;
                            $pathway->passport = empty($pathway->passport)?"":FILE_URL.$pathway->passport;
                            $pathway->birth_certificate = empty($pathway->birth_certificate)?"":FILE_URL.$pathway->birth_certificate;
                            $pathway->past = empty($pathway->past) ? 'no' : $pathway->past;
                            $pathway->dob = date("d/m/Y", strtotime($pathway->dob));
                            $pathway->date_of_visiting = !empty($pathway->date_of_visiting) ? date("d/m/Y", strtotime($pathway->date_of_visiting)) : NULL;
                        }
                        $data = $pathways;
                        $message = 'List of next pathway plans';
                        $statusCode = 200;
                        if ($pathwayType == 'all') {
                            $pathwayPlans['next'] = $data;
                            $data = [];
                        }
                    }else{
                        $data = [];
                        $message = 'No record found.';
                        $statusCode = 400;
                    }
                }
                if($pathwayType == "past" || $pathwayType == 'all'){
                    $pathways = $wpdb->get_results("SELECT pp.* FROM $pathwayTable as pp WHERE pp.user_id IN ($userId) AND (past = 1 OR TIMESTAMPDIFF(MONTH, date_signed_by_sw, '$currentDate') >= 6) AND cancelled != 'yes' AND deleted != 'yes'");
                    if(isset($pathways[0]) && !empty($pathways[0])){
                        foreach($pathways as &$pathway){
                            $visits = $wpdb->get_results("SELECT id,visit,member_name,professionals_name,date,update_visit FROM $visitTable WHERE pathway_id = '$pathway->id'");
                            $pathway->visits = [];
                            if(isset($visits[0]) && !empty($visits[0])){
                                foreach($visits as &$visit){
                                    $visit->id = md5($visit->id.$salt);
                                    if ($visit->date == '0000-00-00') {
                                        $visit->date = NULL;
                                    } else {
                                        $visit->date = date("d/m/Y", strtotime($visit->date));
                                    }
                                }
                                $pathway->visits = $visits;
                            }
                            $pathway->id = md5($pathway->id.$salt);
                            $avatar = get_user_meta($pathway->user_id, 'avatar', true);
                            $pathway->avatar = (string) ($avatar ? AVATAR_URL.$avatar : "");
                            $pathway->file = $this->wpapp_generate_pdf($pathway->user_id, $pathway->id);
                            $pathway->user_id = md5($pathway->user_id.$salt);
                            $pathway->created_by = md5($pathway->created_by.$salt);
                            $pathway->started = empty($pathway->started) ? 'no' : $pathway->started;
                            $pathway->past = empty($pathway->past) ? 'no' : $pathway->past;
                            $pathway->fileTitle = 'Pathway Plan ended '.(!empty($pathway->expired_date) ? date('d/m/Y', strtotime($pathway->expired_date)) : date('Y-m-d', strtotime('-8 weeks', strtotime($pathway->date_signed_by_sw))));
                            $pathway->fileDate = date('d/m/Y', strtotime($pathway->date_signed_by_sw));
                            $pathway->dob = date("d/m/Y", strtotime($pathway->dob));
                            $pathway->date_of_visiting = !empty($pathway->date_of_visiting) ? date("d/m/Y", strtotime($pathway->date_of_visiting)) : NULL;

                        }
                        $data = $pathways;
                        $message = 'List of past pathway plans';
                        $statusCode = 200;
                        if ($pathwayType == 'all') {
                            $pathwayPlans['past'] = $data;
                            $data = [];
                        }
                    }else{
                        $data = [];
                        $message = 'No record found.';
                        $statusCode = 400;
                    }
                }
                if (!$ypIds) {
                    if ($pathwayType == 'all') {
                        if (!empty($pathwayPlans)) {
                            $data = $pathwayPlans;
                            $message = 'List of ALL pathway plans';
                            $statusCode = 200;
                        } else {
                            $data = [];
                            $message = 'No record found.';
                            $statusCode = 400;
                        }
                    }
                    return $this->standardizePayload($data, $message, $statusCode);
                } else {
                    return $pathwayPlans;
                }
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }

        public function wpapp_get_pathway_sw_pa(WP_REST_Request $request){
            global $wpdb;
            $this->validate_token($request);
            $token = $this->getToken($request);
            $salt = SALT;
            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $userid = $user->ID;
                $sql = "SELECT GROUP_CONCAT(DISTINCT user_id) ids FROM $wpdb->usermeta WHERE meta_key = 'sw_pa_email' AND (meta_value LIKE '%\"$userid\"%' OR meta_value LIKE '%:$userid;%')";
                $ypIds = $wpdb->get_var($sql);

                $pathwayPlans = ['current' => [], 'next' => [], 'past' => []];

                if (!empty($ypIds)) {
                    $pathwayPlans = $this->wpapp_get_pathway(false, $ypIds);
                }

                $data = $pathwayPlans;
                $message = 'List of ALL pathway plans';
                $statusCode = 200;

                return $this->standardizePayload($data, $message, $statusCode);
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }

        public function wpapp_get_pathway_by_id(WP_REST_Request $request){
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
                $pathway_hash = $params['pathway_id'];
                $pathwayTbl = $this->_pathway_table;
                $visitsTbl = $wpdb->prefix.'pathway_visits';
                $pathway_id = $wpdb->get_var("SELECT id FROM $pathwayTbl WHERE md5(CONCAT(id,'$salt')) = '$pathway_hash'");
                $pathwayData = $wpdb->get_row( $wpdb->prepare( "SELECT pp.*,pv.id as visit_id, pv.visit, pv.member_name, pv.professionals_name, pv.date, pv.update_visit FROM $pathwayTbl as pp LEFT JOIN $visitsTbl as pv on pp.id = pv.pathway_id WHERE pp.id = %d", $pathway_id ) );
                if(!empty($pathwayData) && intval($pathwayData->id) > 0){
                    $pathwayData->id = md5($pathwayData->id.$salt);
                    $pathwayData->user_id = md5($pathwayData->user_id.$salt);
                    $pathwayData->created_by = md5($pathwayData->created_by.$salt);
                    $pathwayData->started = empty($pathwayData->started) ? 'no' : $pathwayData->started;
                    $pathwayData->past = empty($pathwayData->past) ? 'no' : $pathwayData->past;
                    $pathwayData->dob = date("d/m/Y", strtotime($pathwayData->dob));
                    $pathwayData->date_of_visiting = !empty($pathwayData->date_of_visiting) ? date("d/m/Y", strtotime($pathwayData->date_of_visiting)) : NULL;
                    return $this->standardizePayload($pathwayData, 'Pathway plan record.', 200);
                }else{
                    return $this->standardizePayload($pathwayData, 'No pathway plan found.', 404);
                }
            }else{
                return $this->standardizePayload([], 'User not found', 400);
            }
        }
        public function wpapp_update_pathway(WP_REST_Request $request){
            global $wpdb;
            $salt = SALT;
            $this->validate_token($request);
            $token = $this->getToken($request);
            $filepath = FILE_PATH;
            if (!file_exists($filepath)) {
                mkdir($filepath);
            }
            $user = $this->get_user_for_token($token);
            if (($user instanceof WP_User) && $user->exists()) {
                $params = $request != null ? (array) $request->get_params() : null;

                $nonce = $params['nonce'];
                if (!wp_verify_nonce($nonce, 'wpapp_json_api')) {
                    return $this->standardizePayload([], 'Invalid nonce', 570);
                }
                $table = $wpdb->prefix.'pathway_plans';
                $pathway_hash = $params['pathway_id'];
                $pathwayPlan = $wpdb->get_row("SELECT id, user_id FROM $table WHERE md5(CONCAT(id,'$salt')) = '$pathway_hash'");
                if(!$pathwayPlan){
                    return $this->standardizePayload([], 'Invalid Pathway Plan Id', 500);
                }
                $pathway_id = $pathwayPlan->id;
                $data = $type = $whereData = $whereType = array();
                $whereData['id'] = $pathway_id;
                $whereType = array('%d');

                $pathwayPlanUserId = $pathwayPlan->user_id;
                $getuserdata = get_userdata($user->ID);
                $role = $getuserdata->roles[0];
                $loggedInRole = array_search($role, USERROLE);

                $dispatchedAppUpdated = false;

                if(isset($params['due_date']) && $params['due_date']!=""){
                    $data['due_date'] = $params['due_date'];
                    $type[] = '%s';
                }
                if(isset($params['first_name'])){
                    $data['first_name'] = trim($params['first_name']);
                    $type[] = '%s';
                }
                if(isset($params['last_name'])){
                    $data['last_name'] = trim($params['last_name']);
                    $type[] = '%s';
                }
                if(isset($params['dob'])){
                    $data['dob'] = date("Y-m-d", strtotime(str_replace("/","-",$params['dob'])));
                    $type[] = '%s';
                }
                if(isset($params['gender'])){
                    $data['gender'] = $params['gender'];
                    $type[] = '%s';
                }
                if(isset($params['gender_birth'])){
                    $data['gender_birth'] = $params['gender_birth'];
                    $type[] = '%s';
                }
                if(isset($params['disabled'])){
                    $data['disabled'] = $params['disabled'];
                    $type[] = '%s';
                }
                if(isset($params['communication_needs'])){
                    $data['communication_needs'] = trim($params['communication_needs']);
                    $type[] = '%s';
                }
                if(isset($params['legal_status'])){
                    $data['legal_status'] = trim($params['legal_status']);
                    $type[] = '%s';
                }
                if(isset($params['leaving_care_status'])){
                    $data['leaving_care_status'] = trim($params['leaving_care_status']);
                    $type[] = '%s';
                }
                if(isset($params['Immigration_status'])){
                    $data['Immigration_status'] = trim($params['Immigration_status']);
                    $type[] = '%s';
                }
                if(isset($params['who_has_got_my_birth_certificate'])){
                    $data['who_has_got_my_birth_certificate'] = trim($params['who_has_got_my_birth_certificate']);
                    $type[] = '%s';
                }
                if(isset($params['birth_certificate'])){
                    if (empty($params['birth_certificate'])) {
                        $data['birth_certificate'] = '';
                    } else {
                        $birth_certificate = trim($params['birth_certificate']);
                        $extension = explode('/', mime_content_type($params['birth_certificate']))[1];
                        list($filetype, $database64) = explode(";", $birth_certificate);
                        $database64 = base64_decode(explode(",",$database64)[1]);
                        if ($extension != 'pdf') {
                            return $this->standardizePayload([], 'Error! You need to upload PDF.', 500);
                        }
                        $extension = '.'.$extension;
                        $filename = 'birthcert_'.time();
                        if (file_put_contents($filepath.$filename.$extension, $database64)) {
                            $data['birth_certificate'] = $filename.$extension;
                        } else {
                            return $this->standardizePayload([], 'Error! Error in saving Birth Certificate. Please try again.', 500);
                        }                        
                    }
                    $type[] = '%s';
                }
                if(isset($params['birth_certificate_filename'])){
                    $data['birth_certificate_filename'] = trim($params['birth_certificate_filename']);
                    $type[] = '%s';
                }
                if(isset($params['who_has_got_my_passport'])){
                    $data['who_has_got_my_passport'] = trim($params['who_has_got_my_passport']);
                    $type[] = '%s';
                }
                if(isset($params['passport'])){
                    if (empty($params['passport'])) {
                        $data['passport'] = '';
                    } else {
                        $passport = trim($params['passport']);
                        $extension = explode('/', mime_content_type($params['passport']))[1];
                        list($filetype, $database64) = explode(";", $passport);
                        $database64 = base64_decode(explode(",",$database64)[1]);
                        if ($extension != 'pdf') {
                            return $this->standardizePayload([], 'Error! You need to upload PDF.', 500);
                        }
                        $extension = '.'.$extension;
                        $filename = 'passport_'.time();
                        if (file_put_contents($filepath.$filename.$extension, $database64)) {
                            $data['passport'] = $filename.$extension;
                        } else {
                            return $this->standardizePayload([], 'Error! Error in saving Passport. Please try again.', 500);
                        }
                    }
                    $type[] = '%s';
                }
                if(isset($params['passport_filename'])){
                    $data['passport_filename'] = trim($params['passport_filename']);
                    $type[] = '%s';
                }
                if(isset($params['ni_number'])){
                    $data['ni_number'] = trim($params['ni_number']);
                    $type[] = '%s';
                }
                if(isset($params['overall_plan_feeling'])){
                    if(in_array($params['overall_plan_feeling'], array(1,2,3,4,5))){
                        $data['overall_plan_feeling'] = $params['overall_plan_feeling'];
                        $type[] = '%d';
                    }
                }
                if(isset($params['overall_care_plan'])){
                    $data['overall_care_plan'] = trim($params['overall_care_plan']);
                    $type[] = '%s';
                }
                if(isset($params['attempts'])){
                    $data['attempts'] = $params['attempts'];
                    $type[] = '%s';
                }
                if(isset($params['family_relationship'])){
                    $data['family_relationship'] = trim($params['family_relationship']);
                    $type[] = '%s';
                }
                if(isset($params['workers_assessment'])){
                    $data['workers_assessment'] = trim($params['workers_assessment']);
                    $type[] = '%s';
                }
                if(isset($params['contact_arrangements'])){
                    $data['contact_arrangements'] = trim($params['contact_arrangements']);
                    $type[] = '%s';
                }
                if(isset($params['date_of_visiting'])){
                    $data['date_of_visiting'] = date("Y-m-d", strtotime(str_replace("/","-",trim($params['date_of_visiting']))));
                    $type[] = '%s';
                }
                if(isset($params['seen_alone'])){
                    $data['seen_alone'] = trim($params['seen_alone']);
                    $type[] = '%s';
                }
                if(isset($params['comments'])){
                    $data['comments'] = trim($params['comments']);
                    $type[] = '%s';
                }
                if(isset($params['outside_statutory'])){
                    $data['outside_statutory'] = trim($params['outside_statutory']);
                    $type[] = '%s';
                }
                if(isset($params['education_feeling'])){
                    if(in_array($params['education_feeling'], array(1,2,3,4,5))){
                        $data['education_feeling'] = $params['education_feeling'];
                        $type[] = '%d';
                    }
                }
                if(isset($params['education_working_well'])){
                    $data['education_working_well'] = trim($params['education_working_well']);
                    $type[] = '%s';
                }
                if(isset($params['education_worried_about'])){
                    $data['education_worried_about'] = trim($params['education_worried_about']);
                    $type[] = '%s';
                }
                if(isset($params['education_current_establishment'])){
                    $data['education_current_establishment'] = trim($params['education_current_establishment']);
                    $type[] = '%s';
                }
                if(isset($params['education_address'])){
                    $data['education_address'] = trim($params['education_address']);
                    $type[] = '%s';
                }
                if(isset($params['education_phone'])){
                    $data['education_phone'] = trim($params['education_phone']);
                    $type[] = '%s';
                }
                if(isset($params['education_support_contact'])){
                    $data['education_support_contact'] = trim($params['education_support_contact']);
                    $type[] = '%s';
                }
                if(isset($params['education_date'])){
                    $data['education_date'] = trim($params['education_date']);
                    $type[] = '%s';
                }
                if(isset($params['education_responsible_la'])){
                    $data['education_responsible_la'] = trim($params['education_responsible_la']);
                    $type[] = '%s';
                }
                if(isset($params['education_next_steps'])){
                    $data['education_next_steps'] = trim($params['education_next_steps']);
                    $type[] = '%s';
                }
                if(isset($params['education_long_term_goals'])){
                    $data['education_long_term_goals'] = trim($params['education_long_term_goals']);
                    $type[] = '%s';
                }
                if(isset($params['education_contingency'])){
                    $data['education_contingency'] = trim($params['education_contingency']);
                    $type[] = '%s';
                }
                if(isset($params['managing_feeling'])){
                    if(in_array($params['managing_feeling'], array(1,2,3,4,5))){
                        $data['managing_feeling'] = $params['managing_feeling'];
                        $type[] = '%d';
                    }
                }
                if(isset($params['managing_working_well'])){
                    $data['managing_working_well'] = trim($params['managing_working_well']);
                    $type[] = '%s';
                }
                if(isset($params['managing_worried_about'])){
                    $data['managing_worried_about'] = trim($params['managing_worried_about']);
                    $type[] = '%s';
                }
                if(isset($params['managing_next_steps'])){
                    $data['managing_next_steps'] = trim($params['managing_next_steps']);
                    $type[] = '%s';
                }
                if(isset($params['managing_long_term_goals'])){
                    $data['managing_long_term_goals'] = trim($params['managing_long_term_goals']);
                    $type[] = '%s';
                }
                if(isset($params['managing_contingency'])){
                    $data['managing_contingency'] = trim($params['managing_contingency']);
                    $type[] = '%s';
                }
                if(isset($params['health_feeling'])){
                    if(in_array($params['health_feeling'], array(1,2,3,4,5))){
                        $data['health_feeling'] = $params['health_feeling'];
                        $type[] = '%d';
                    }
                }
                if(isset($params['health_working_well'])){
                    $data['health_working_well'] = trim($params['health_working_well']);
                    $type[] = '%s';
                }
                if(isset($params['health_worried_about'])){
                    $data['health_worried_about'] = trim($params['health_worried_about']);
                    $type[] = '%s';
                }
                if(isset($params['health_next_steps'])){
                    $data['health_next_steps'] = trim($params['health_next_steps']);
                    $type[] = '%s';
                }
                if(isset($params['health_long_term_goals'])){
                    $data['health_long_term_goals'] = trim($params['health_long_term_goals']);
                    $type[] = '%s';
                }
                if(isset($params['health_contingency'])){
                    $data['health_contingency'] = trim($params['health_contingency']);
                    $type[] = '%s';
                }
                if(isset($params['money_feeling'])){
                    if(in_array($params['money_feeling'], array(1,2,3,4,5))){
                        $data['money_feeling'] = $params['money_feeling'];
                        $type[] = '%d';
                    }
                }
                if(isset($params['money_working_well'])){
                    $data['money_working_well'] = trim($params['money_working_well']);
                    $type[] = '%s';
                }
                if(isset($params['money_worried_about'])){
                    $data['money_worried_about'] = trim($params['money_worried_about']);
                    $type[] = '%s';
                }
                if(isset($params['money_next_steps'])){
                    $data['money_next_steps'] = trim($params['money_next_steps']);
                    $type[] = '%s';
                }
                if(isset($params['money_long_term_goals'])){
                    $data['money_long_term_goals'] = trim($params['money_long_term_goals']);
                    $type[] = '%s';
                }
                if(isset($params['money_contingency'])){
                    $data['money_contingency'] = trim($params['money_contingency']);
                    $type[] = '%s';
                }
                if(isset($params['health_allergies'])){
                    $data['health_allergies'] = trim($params['health_allergies']);
                    $type[] = '%s';
                }
                if(isset($params['mental_health'])){
                    $data['mental_health'] = trim($params['mental_health']);
                    $type[] = '%s';
                }
                $pathwayPlayCurrent =  $createNextPP = false;
                if(isset($params['signed_by_sw'])){
                    if(in_array($params['signed_by_sw'], array('yes','no'))){
                        $data['signed_by_sw'] = $params['signed_by_sw'];
                        $type[] = '%s';

                        if ($data['signed_by_sw'] == 'yes') {
                            $data['date_signed_by_sw'] = date('Y-m-d');
                            $type[] = '%s';

                            // get current current pathway plan, to convert it to past.
                            $currentDate = date("Y-m-d");
                            $user = get_user_by( 'id', $pathwayPlan->user_id );
                            $userId = $user->ID;

                            $pathwayPlayCurrent = $wpdb->get_var("SELECT id FROM $this->_pathway_table as pp WHERE pp.user_id = $userId AND '$currentDate' >= date_signed_by_sw AND TIMESTAMPDIFF(MONTH, date_signed_by_sw, '$currentDate') <= 6 AND signed_by_sw = 'yes' AND signed_by_yp = 'yes' AND cancelled != 'yes' AND deleted != 'yes' AND id != ".$pathway_id);
                            $createNextPP = true;

                            // we need to send YP notification
                            $message = __('Current Pathway Plan is started') . "\r\n\r\n";
                            $message .= get_site_url(null,'/pathway-plans/');
                            $headers = "";                            
                            $userEmail = $user->data->user_email;
                            $userHash = md5($userId.SALT);
                            $dispatchedAppUpdated = true;
                            $this->dispatch_app_update('get_pp', $userHash, "Current Pathway Plan is started");
                            wp_mail( $userEmail, "Current Pathway Plan is started", $message, $headers );
                        }
                    }
                }
                if(isset($params['signed_by_yp'])){
                    if(in_array($params['signed_by_yp'], array('yes','no'))){
                        $data['signed_by_yp'] = $params['signed_by_yp'];
                        $type[] = '%s';

                        if ($data['signed_by_yp'] == 'yes') {
                            $data['date_signed_by_yp'] = date('Y-m-d');
                            $type[] = '%s';

                            // we need to send SW notification
                            $message = __('YP Signed NEXT PP') . "\r\n\r\n";
                            $message .= get_site_url(null,'/pathway-plan/');
                            $headers = "";

                            $socialWorkerIds = get_user_meta($data['user_id'], 'sw_pa_email', true);

                            if ($socialWorkerIds) {
                                foreach($socialWorkerIds as $sw_pa_id){
                                    $swpaData = get_userdata($sw_pa_id);
                                    $userEmail = $swpaData->data->user_email;
                                    $userHash = md5($sw_pa_id.SALT);
                                    $dispatchedAppUpdated = true;
                                    $this->dispatch_app_update('get_pp_sw_pa', $userHash, "YP Signed NEXT PP");
                                    wp_mail( $userEmail, "YP Signed NEXT PP", $message, $headers );
                                }
                            }
                        }
                    }
                }
                if(isset($params['started'])){
                    $data['started'] = $params['started'];
                    $type[] = '%s';
                }
                try {
                    if(isset($params['visits'])){
                        $visitsArr = $params['visits'];
                        $visittable = $wpdb->prefix.'pathway_visits';
                        $visitIds = [];
                        if (count($visitsArr) > 0) {
                            foreach($visitsArr as $visitData){
                                $visitFields = $visitType = [];
                                $visitid = $visitData['id'];
                                if (!empty($visitid)) {
                                    $visitid = $this->get_visitid_from_hash($visitid);
                                }
                                $visitFields['pathway_id'] = $pathway_id;
                                $visitType[] = '%d';
                                if(isset($visitData['visit'])){
                                    $visitFields['visit'] = trim($visitData['visit']);
                                    $visitType[] = '%s';
                                }
                                if(isset($visitData['member_name'])){
                                    $visitFields['member_name'] = trim($visitData['member_name']);
                                    $visitType[] = '%s';
                                }
                                if(isset($visitData['professionals_name'])){
                                    $visitFields['professionals_name'] = trim($visitData['professionals_name']);
                                    $visitType[] = '%s';
                                }
                                if(isset($visitData['date'])){
                                    $visitFields['date'] = date("Y-m-d", strtotime(str_replace("/","-",trim($visitData['date']))));
                                    $visitType[] = '%s';
                                }
                                if(isset($visitData['update_visit'])){
                                    $visitFields['update_visit'] = trim($visitData['update_visit']);
                                    $visitType[] = '%s';
                                }
                                if(intval($visitid) > 0){
                                    $visitidType[] = '%d';
                                    $wpdb->update(
                                        $visittable,
                                        $visitFields,
                                        array('id'=>$visitid),
                                        $visitType,
                                        $visitidType
                                    );
                                }else{
                                    $wpdb->insert(
                                        $visittable,
                                        $visitFields,
                                        $visitType
                                    );
                                    $visitid = $wpdb->insert_id;
                                    $visitidType[] = '%d';
                                    if(intval($visitid) == 0){
                                        return $this->standardizePayload([], 'Error! inserting visits', 400);
                                    }
                                }
                                $visitIds[] = $visitid;
                            }
                        }
                        if(empty($visitIds)){
                            $wpdb->query("DELETE FROM `{$wpdb->prefix}pathway_visits` WHERE pathway_id = '$pathway_id'");
                        }else{
                            $visitIds = implode(',', $visitIds);
                            $wpdb->query("DELETE FROM `{$wpdb->prefix}pathway_visits` WHERE pathway_id = '$pathway_id' AND id NOT IN ($visitIds)");
                        }
                        $visits = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}pathway_visits` WHERE `pathway_id` = %d", $pathway_id));
                        $data['visits'] = [];
                        if ($visits) {
                            foreach($visits as &$visit){
                                $visit->id = md5($visit->id.$salt);
                                $visit->pathway_id = md5($visit->pathway_id.$salt);
                                $visit->date = date("d/m/Y", strtotime($visit->date));
                            }
                        }
                        $data['visits'] = $visits;
                    }
                    $wpdb->update(
                        $table,
                        $data,
                        $whereData,
                        $type,
                        $whereType
                    );
                    $data['id'] = $pathway_hash;
                    if (isset($data['due_date'])) {
                        $data['open_date'] = date('Y-m-d', strtotime('-8 weeks', strtotime($data['due_date'])));
                        $data['opened'] = (time() > strtotime('-8 weeks', strtotime($data['due_date']))) ? 'yes' : 'no';
                    }

                    if (isset($data['birth_certificate']) && !empty($data['birth_certificate'])) {
                        $data['birth_certificate'] = FILE_URL.$data['birth_certificate'];
                    }
                    if (isset($data['passport']) && !empty($data['passport'])) {
                        $data['passport'] = FILE_URL.$data['passport'];
                    }
                    if (isset($data['dob']) && !empty($data['dob'])) {
                        $data['dob'] = date("d/m/Y", strtotime($data['dob']));
                    }
                    if (isset($data['date_of_visiting']) && !empty($data['date_of_visiting'])) {
                        $data['date_of_visiting'] = date("d/m/Y", strtotime($data['date_of_visiting']));
                    }                    

                    if ($createNextPP) {
                        if ($pathwayPlayCurrent) {
                            $wpdb->query("UPDATE $this->_pathway_table SET past = 'yes', expired_date = '".date("Y-m-d")."' WHERE id = $pathwayPlayCurrent");
                        }

                        // once the NEXT PP is converted to Current, we need to create a new NEXT PP. Start date is Current Plan expiry - 8 weeks.
                        $currentPathwayPlan = $wpdb->get_row("SELECT pp.* FROM $this->_pathway_table as pp WHERE pp.user_id = $userId AND '$currentDate' >= date_signed_by_sw AND TIMESTAMPDIFF(MONTH, date_signed_by_sw, '$currentDate') <= 6 AND signed_by_sw = 'yes' AND signed_by_yp = 'yes' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");

                        if ($currentPathwayPlan) {
                            $type = [];
                            $nextPP = [];
                            $nextPP['user_id'] = $currentPathwayPlan->user_id;
                            $type[] = '%d';
                            $nextPP['due_date'] = date('Y-m-d', strtotime('+6 months'));
                            $type[] = '%s';
                            $nextPP['created_by'] = $currentPathwayPlan->created_by;
                            $type[] = '%d';
                            $nextPP['created_date'] = date('Y-m-d');
                            $type[] = '%s';
                            $nextPP['first_name'] = $currentPathwayPlan->first_name;
                            $type[] = '%s';
                            $nextPP['last_name'] = $currentPathwayPlan->last_name;
                            $type[] = '%s';
                            $nextPP['dob'] = $currentPathwayPlan->dob;
                            $type[] = '%s';
                            $nextPP['gender'] = $currentPathwayPlan->gender;
                            $type[] = '%s';
                            $nextPP['gender_birth'] = $currentPathwayPlan->gender_birth;
                            $type[] = '%s';
                            $nextPP['disabled'] = $currentPathwayPlan->disabled;
                            $type[] = '%s';
                            $nextPP['communication_needs'] = $currentPathwayPlan->communication_needs;
                            $type[] = '%s';
                            $nextPP['legal_status'] = $currentPathwayPlan->legal_status;
                            $type[] = '%s';
                            $nextPP['leaving_care_status'] = $currentPathwayPlan->leaving_care_status;
                            $type[] = '%s';
                            $nextPP['Immigration_status'] = $currentPathwayPlan->Immigration_status;
                            $type[] = '%s';
                            $nextPP['who_has_got_my_birth_certificate'] = $currentPathwayPlan->who_has_got_my_birth_certificate;
                            $type[] = '%s';
                            $nextPP['birth_certificate'] = $currentPathwayPlan->birth_certificate;
                            $type[] = '%s';
                            $nextPP['birth_certificate_filename'] = $currentPathwayPlan->birth_certificate_filename;
                            $type[] = '%s';
                            $nextPP['who_has_got_my_passport'] = $currentPathwayPlan->who_has_got_my_passport;
                            $type[] = '%s';
                            $nextPP['passport'] = $currentPathwayPlan->passport;
                            $type[] = '%s';
                            $nextPP['passport_filename'] = $currentPathwayPlan->passport_filename;
                            $type[] = '%s';
                            $nextPP['ni_number'] = $currentPathwayPlan->ni_number;
                            $type[] = '%s';
                            $nextPP['signed_by_sw'] = 'no';
                            $type[] = '%s';
                            $nextPP['signed_by_yp'] = 'no';
                            $type[] = '%s';
                            $nextPP['started'] = 'no';
                            $type[] = '%s';
                            $nextPP['past'] = 'no';
                            $type[] = '%s';
                            $nextPP['cancelled'] = 'no';
                            $type[] = '%s';
                            $nextPP['deleted'] = 'no';
                            $type[] = '%s';

                            try {
                                $table = $this->_pathway_table;
                                $wpdb->insert(
                                    $table,
                                    $nextPP,
                                    $type
                                );
                            } catch (Exception $e) {
                                return $this->standardizePayload([], 'Error while creating NextPP', 500);
                            }
                        }

                    }

                    // dispatch app update
                    if (!$dispatchedAppUpdated) {
                        if (in_array($loggedInRole, ['SW', 'PA'],)) {
                            $pathwayPlanUserHash = md5($pathwayPlanUserId . SALT);
                            $this->dispatch_app_update('get_pp', $pathwayPlanUserHash, 'Social Worker updated the pathway plan');
                        } else {
                            $socialWorkerIds = get_user_meta($pathwayPlanUserId, 'sw_pa_email', true);

                            if ($socialWorkerIds) {
                                foreach($socialWorkerIds as $sw_pa_id){
                                    $sw_hash = md5($sw_pa_id . SALT);
                                    $this->dispatch_app_update('get_pp_sw_pa', $sw_hash, "A young person updated a pathway plan ");
                                }
                            }
                        }
                    }

                    return $this->standardizePayload($data, 'Pathway plan updated successfully', 200);

                } catch (Exception $e) {
                    return $this->standardizePayload([], 'server error', 500);
                }
            }else{
                return $this->standardizePayload([], 'User not found', 404);
            }
        }

        public function wpapp_override_pathway(WP_REST_Request $request){
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
                $youngPersonId = false;
                $pathwayTable = $wpdb->prefix.'pathway_plans';
                if(isset($params['due_date']) && !empty($params['due_date'])){
                    $due_date = $params['due_date'];
                } else {
                    return $this->standardizePayload([], 'Due Date not set', 404);
                }
                if (isset($params['yp_id']) && !empty($params['yp_id'])) {
                    $getuserdata = get_userdata($swId);
                    $role = $getuserdata->roles[0];
                    $type = array_search($role, USERROLE);
                    if ($type == 'SW' || $type == 'PA') {
                        $ypid = $this->get_userid_from_hash($params['yp_id']);
                        if ($ypid) {
                            $socialWorkers = get_user_meta($ypid, 'sw_pa_email', true);
                            if (is_array($socialWorkers) && in_array($swId, $socialWorkers)) {
                                $youngPersonId = $ypid;
                                $currentDate = date('Y-m-d');
                                // we need to cancel the current PP and update the Due Date for Next PP
                                $currentPathwayPlan = $wpdb->get_row("SELECT pp.* FROM $this->_pathway_table as pp WHERE pp.user_id IN ($youngPersonId) AND '$currentDate' >= date_signed_by_sw AND TIMESTAMPDIFF(MONTH, date_signed_by_sw, '$currentDate') <= 6 AND signed_by_sw = 'yes' AND signed_by_yp = 'yes' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");

                                if ($currentPathwayPlan) {
                                    $wpdb->query("UPDATE $pathwayTable as pp SET cancelled = 'yes' WHEREpp.id = ".$currentPathwayPlan->id);
                                }

                                $pathway_id = $wpdb->get_var("SELECT id FROM $pathwayTable as pp WHERE pp.user_id IN ($youngPersonId) AND '$currentDate' < due_date AND signed_by_sw = 'no' AND past != 'yes' AND cancelled != 'yes' AND deleted != 'yes'");

                                if ($pathway_id) {

                                    $wpdb->query("UPDATE $pathwayTable as pp SET due_date = '".$due_date."', started = 'no' WHERE id = ".$pathway_id);
                                    $data['due_date'] = $due_date;
                                    $data['started'] = 'no';
                                    $data['pathway_id'] = md5($pathway_id.SALT);
                                    $data['open_date'] = date('Y-m-d', strtotime('-8 weeks', strtotime($due_date)));
                                    $data['opened'] = (time() > strtotime('-8 weeks', strtotime($due_date))) ? 'yes' : 'no';
                                    return $this->standardizePayload($data, 'Pathway updated', 200);
                                } else {

                                    // we need to create new PP
                                    // once the NEXT PP is converted to Current, we need to create a new NEXT PP. Start date is Current Plan expiry - 8 weeks.
                                    
                                    $type = [];
                                    $nextPP = [];
                                    $nextPP['user_id'] = $currentPathwayPlan->user_id;
                                    $type[] = '%d';
                                    $nextPP['due_date'] = $due_date;
                                    $type[] = '%s';
                                    $nextPP['created_by'] = $swId;
                                    $type[] = '%d';
                                    $nextPP['created_date'] = date('Y-m-d');
                                    $type[] = '%s';
                                    if ($currentPathwayPlan) {
                                        $nextPP['first_name'] = $currentPathwayPlan->first_name;
                                        $type[] = '%s';
                                        $nextPP['last_name'] = $currentPathwayPlan->last_name;
                                        $type[] = '%s';
                                        $nextPP['dob'] = $currentPathwayPlan->dob;
                                        $type[] = '%s';
                                        $nextPP['gender'] = $currentPathwayPlan->gender;
                                        $type[] = '%s';
                                        $nextPP['gender_birth'] = $currentPathwayPlan->gender_birth;
                                        $type[] = '%s';
                                        $nextPP['disabled'] = $currentPathwayPlan->disabled;
                                        $type[] = '%s';
                                        $nextPP['communication_needs'] = $currentPathwayPlan->communication_needs;
                                        $type[] = '%s';
                                        $nextPP['legal_status'] = $currentPathwayPlan->legal_status;
                                        $type[] = '%s';
                                        $nextPP['leaving_care_status'] = $currentPathwayPlan->leaving_care_status;
                                        $type[] = '%s';
                                        $nextPP['Immigration_status'] = $currentPathwayPlan->Immigration_status;
                                        $type[] = '%s';
                                        $nextPP['who_has_got_my_birth_certificate'] = $currentPathwayPlan->who_has_got_my_birth_certificate;
                                        $type[] = '%s';
                                        $nextPP['birth_certificate'] = $currentPathwayPlan->birth_certificate;
                                        $type[] = '%s';
                                        $nextPP['birth_certificate_filename'] = $currentPathwayPlan->birth_certificate_filename;
                                        $type[] = '%s';
                                        $nextPP['who_has_got_my_passport'] = $currentPathwayPlan->who_has_got_my_passport;
                                        $type[] = '%s';
                                        $nextPP['passport'] = $currentPathwayPlan->passport;
                                        $type[] = '%s';
                                        $nextPP['passport_filename'] = $currentPathwayPlan->passport_filename;
                                        $type[] = '%s';
                                        $nextPP['ni_number'] = $currentPathwayPlan->ni_number;
                                        $type[] = '%s';
                                    } else {
                                        $nextPP['first_name'] = get_user_meta($youngPersonId, 'first_name', true);
                                        $type[] = '%s';
                                        $nextPP['last_name'] = get_user_meta($youngPersonId, 'last_name', true);
                                        $type[] = '%s';
                                        $nextPP['dob'] = date("Y-m-d", strtotime(str_replace("/","-",get_user_meta($youngPersonId, 'dob', true))));
                                        $type[] = '%s';
                                        $nextPP['gender'] = get_user_meta($youngPersonId, 'gender', true);
                                        $type[] = '%s';
                                        $nextPP['gender_birth'] = get_user_meta($youngPersonId, 'gender_birth', true);
                                        $type[] = '%s';
                                    }
                                    $nextPP['signed_by_sw'] = 'no';
                                    $type[] = '%s';
                                    $nextPP['signed_by_yp'] = 'no';
                                    $type[] = '%s';
                                    $nextPP['started'] = 'no';
                                    $type[] = '%s';
                                    $nextPP['past'] = 'no';
                                    $type[] = '%s';
                                    $nextPP['cancelled'] = 'no';
                                    $type[] = '%s';
                                    $nextPP['deleted'] = 'no';
                                    $type[] = '%s';

                                    try {
                                        $table = $this->_pathway_table;
                                        $wpdb->insert(
                                            $table,
                                            $nextPP,
                                            $type
                                        );
                                        $pathway_id = $wpdb->insert_id;

                                        $nextPP['pathway_id'] = md5($pathway_id.SALT);
                                        $nextPP['open_date'] = date('Y-m-d', strtotime('-8 weeks', strtotime($due_date)));
                                        $nextPP['opened'] = (time() > strtotime('-8 weeks', strtotime($due_date))) ? 'yes' : 'no';
                                        $nextPP['dob'] = date("d/m/Y", strtotime($nextPP['dob']));
                                        $nextPP['date_of_visiting'] = !empty($nextPP['date_of_visiting']) ? date("d/m/Y", strtotime($nextPP['date_of_visiting'])) : NULL;

                                        return $this->standardizePayload($nextPP, 'Next PP was created', 200);
                                    } catch (Exception $e) {
                                        return $this->standardizePayload([], 'There was an error creating Next PP', 404);
                                    }                                    
                                }
                            } else {
                                return $this->standardizePayload([], 'Not allowed', 500);
                            }
                        } else {
                            return $this->standardizePayload([], 'Cannot find YP user', 404);
                        }
                    }
                } else {
                    return $this->standardizePayload([], 'YP not set', 500);
                }
            } else {
                return $this->standardizePayload([], 'User not found', 404);
            }
        }
        public function wpapp_delete_pathway(WP_REST_Request $request){
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
                $pathwayHash = $params['pathway_id'];

                if (empty($pathwayHash)) {
                    return $this->standardizePayload($e->getMessage(), 'pathway_id not set', 400);
                }

                $pathwayId = $this->get_pathwayid_from_hash($pathwayHash);
                if (!$pathwayId) {
                    return $this->standardizePayload($e->getMessage(), 'PP Not found', 400);
                }
                try{
                    $deletepp = $wpdb->query("UPDATE `{$wpdb->prefix}pathway_plans` SET deleted = 'yes' WHERE `id` = '$pathwayId'");
                    return $this->standardizePayload([], 'Pathway plan deleted successfully', 200);
                }catch(Exception $e){
                    return $this->standardizePayload($e->getMessage(), 'Error deleting pathway plans', 500);
                }

            }else{
                return $this->standardizePayload([], 'User not found', 404);
            }
        }
        public function wpapp_get_preview(WP_REST_Request $request){
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
                $pathwayHash = $params['pathway_id'];
                $pathwayId = $this->get_pathwayid_from_hash($pathwayHash);
                if(intval($pathwayId) <= 0){
                    return $this->standardizePayload([], 'Pathway plan not found', 400);
                }
                $userId = $user->ID;

                $file = $this->wpapp_generate_pdf($userId, $pathwayId);                
				
                return $this->standardizePayload($file, 'Pathway Plan PDF', 200);
            }else{
                return $this->standardizePayload([], 'User not found', 404);
            }
        }

        public function wpapp_generate_pdf($userId, $pathway_id){
            global $wpdb;
            $pathwayHash = md5($pathway_id.SALT);
            $pathway_pdf_name = $userId."_".$pathwayHash;
            $filePath = PDF_PATH.$pathway_pdf_name. ".pdf";
            if(file_exists($filePath)){
                $fileUrl = PDF_URL.$pathway_pdf_name. ".pdf";
                $size = filesize($filePath);
                $unit = false;
                if( (!$unit && $size >= 1<<30) || $unit == "GB")
                    $size = number_format($size/(1<<30),2)."GB";
                elseif( (!$unit && $size >= 1<<20) || $unit == "MB")
                    $size = number_format($size/(1<<20),2)."MB";
                elseif( (!$unit && $size >= 1<<10) || $unit == "KB")
                    $size = number_format($size/(1<<10),2)."KB";
                else
                    $size = number_format($size)." B";

                return ['filename' => $pathway_pdf_name, "fileurl" => $fileUrl, "size" => $size];
            }
            
            $address = get_user_meta($userId,'address',true);
            
            $sql = "SELECT * FROM {$wpdb->prefix}pathway_plans WHERE id = '$pathwayId'";
            $pathwayData = $wpdb->get_row($sql);
            $visitsData = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}pathway_visits` WHERE `pathway_id` = %d", $pathwayId));

            $createdByFname = get_user_meta($pathwayData->created_by,"first_name",true);
            $createdByLname = get_user_meta($pathwayData->created_by,"last_name",true);
            $createdForFname = get_user_meta($pathwayData->user_id,"first_name",true);
            $createdForLname = get_user_meta($pathwayData->user_id,"last_name",true);

            $createdBy = $createdByFname.' '.$createdByLname;
            $createdFor = $createdForFname.' '.$createdForLname;
            $pathwayData->createdBy = $createdBy;
            
            if($pathwayData->disabled == 'no'){
                $pathwayData->disabled = "&#x2716;";
            }else{
                $pathwayData->disabled = "&#x2714;";
            }
            
            $swids = get_user_meta($pathwayData->user_id, 'sw_pa_email',true);
            if (!empty($swids)) {
                $i = 0;
                foreach ($swids as $swid) {
                    if ($swuser = get_user_by('id', $swid)) {
                        $role = $swuser->roles[0];
                        $type = array_search($role, USERROLE);
                        
                        $swUserMeta = get_user_meta($swid);
                        $pathwayData->supporting_people[$i]['first_name'] = $swUserMeta['first_name'][0];
                        $pathwayData->supporting_people[$i]['last_name'] = $swUserMeta['last_name'][0];
                        $pathwayData->supporting_people[$i]['phone_number'] = $swUserMeta['phone_number'][0];
                        $pathwayData->supporting_people[$i]['type'] = $type;
                        $i++;
                    }
                }
            }
            
            $visitTable = $wpdb->prefix.'pathway_visits';
            $visits = $wpdb->get_results("SELECT id,visit,member_name,professionals_name,date,update_visit FROM $visitTable WHERE pathway_id = $pathwayId ");
            if(isset($visits)){
                $pathwayData->visits = $visits;
            }
            $pathwayData->pathway_pdf_name = $pathway_pdf_name;

            $themeuri = get_stylesheet_directory();
            require_once('pdf_my_pathway_plan.php');
            $file = generate_pdf::my_pathway_plan_fn($pathwayData);

            return $file;
        }
    }

    $pathwayApiObj = new class_pathway_api();
    $pathwayApiObj->pathwayApi();
}