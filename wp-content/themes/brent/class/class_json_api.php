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
if (class_exists('Json_Mobile_Api_Wpapp_Loader')) {

    class class_json_api extends Json_Mobile_Api_Wpapp_Loader {

        public function customApi() {
            add_action('rest_api_init', function () {
                $prefixApiV1 = 'wpapp/api/v2';
                $prefixV1 = 'wpnotify/v1';
                // AUTH
                }
            );
        }
        public function get_userid_from_hash($userhash){
            global $wpdb;
            $salt = SALT;
            $user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE md5(CONCAT(ID,'$salt')) = '$userhash'");
            return $user_id;
        }
        public function get_postid_from_hash($posthash){
            global $wpdb;
            $salt = SALT;
            $post_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE md5(CONCAT(ID,'$salt')) = '$posthash'");
            return $post_id;
        }
        public function get_pathwayid_from_hash($pphash){
            global $wpdb;
            $salt = SALT;
            $pp_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}pathway_plans WHERE md5(CONCAT(id,'$salt')) = '$pphash'");
            return $pp_id;
        }
        public function get_visitid_from_hash($visithash){
            global $wpdb;
            $salt = SALT;
            $visit_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}pathway_visits WHERE md5(CONCAT(id,'$salt')) = '$visithash'");
            return $visit_id;
        }
        public function get_taskid_from_hash($taskhash){
            global $wpdb;
            $salt = SALT;
            $task_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}users_tasks WHERE md5(CONCAT(id,'$salt')) = '$taskhash'");
            return $task_id;
        }

        public function dispatch_app_update ($api_to_update, $user_hash, $message = '' ) {
            $port = NODE_SOCKET_PORT; // Port the node app listens to
            $address = '127.0.0.1'; // IP the node app is on
            try {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if ($socket === false) {
                    // echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
                }
                $result = socket_connect($socket, $address, $port);
                if ($result === false) {
                    // echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
                }
                $data = array('api' => $api_to_update, 'user' => $user_hash, 'message' => $message);

                $encdata = json_encode($data);
                socket_write($socket, $encdata, strlen($encdata));
                socket_close($socket);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
         }
    }
    $jsonApiObj = new class_json_api();
    $jsonApiObj->customApi();
}
