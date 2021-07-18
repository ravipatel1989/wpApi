<?php
function add_cors_http_header()
{
    header("Access-Control-Allow-Origin: *");
}

add_action('init', 'add_cors_http_header');

add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    // Add a Custom filter.
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Credentials: true');

        return $value;
    });
});

function checkForProfanity($word)
{
    $isBad = true;
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://neutrinoapi.net/bad-word-filter",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "censor-character=*&content=$word&catalog=strict",
        CURLOPT_HTTPHEADER => [
            "content-type: application/x-www-form-urlencoded",
            "user-id: standbyteam",
            "api-key: HaLuFT6fY2u2VeET7LXtFLVHwla3GrMuB5kawmbGQRH2KxYX",
        ],
    ]);

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:".$err;
    } else {
        $result = json_decode($response);
        $isBad = $result->{'is-bad'};
    }

    return $isBad;
}