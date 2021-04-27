<?php

define("IS_DEMO",               true);
define("HTTP_PROTOCOL",         "https");
define("MERCHANT_ID",           "YOUR-MERCHANT-ID");
define("API_KEY",               "YOUR-VIVAWALLET-API-KEY");
define("TAG",                   "ECOMMERCE_STORE");
define("VIVAWALLET_ENDPOINT",   HTTP_PROTOCOL . ((IS_DEMO) ? "://demo.vivapayments.com/" : "://www.vivapayments.com/"));

$status = pay("Test purchase", 20, "Test user", "user@example.com");

if ($status !== false) {

    // Payment id
    $payment_id = isset($status->OrderCode) ? $status->OrderCode : exit("error");

    echo "<a href='https://demo.vivapayments.com/web/newtransaction.aspx?ref=" . $payment_id . "'>Pay now</a>";

} else {

    echo "Payment failed";
}

function pay($internal_reference, $amount, $buyer_name, $buyer_email) {


    if ($amount == 0) {

        return false;
    }

    $post = http_build_query(array(

        "AllowRecurring"    => false,
        "customerTrns"      => $internal_reference,
        "fullName"          => $buyer_name,
        "Amount"            => intval(($amount * 100) . ''), // Amount in cents
        "email"             => $buyer_email,
        "tags"              => TAG
    ));

    $curl = curl_init();

    // Set the POST options.
    curl_setopt($curl, CURLOPT_URL,             VIVAWALLET_ENDPOINT . "api/orders");
    curl_setopt($curl, CURLOPT_POST,            true);
    curl_setopt($curl, CURLOPT_HEADER,          true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,  true);
    curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
    curl_setopt($curl, CURLOPT_POSTFIELDS,      $post);
    curl_setopt($curl, CURLOPT_USERPWD,         MERCHANT_ID . ':' . API_KEY);

    // Do the POST and then close the session
    $response = curl_exec($curl);

    // Separate Header from Body
    $header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $resHeader  = substr($response, 0, $header_len);
    $resBody    = substr($response, $header_len);
    
    curl_close($curl);

    // Parse the JSON response
    try {
        
        if (is_object(json_decode($resBody))) {
            
            return json_decode($resBody);

        } else {    
            
            preg_match('#^HTTP/1.(?:0|1) [\d]{3} (.*)$#m', $resHeader, $match);
                    throw new Exception("API Call failed! The error was: ".trim($match[1]));
        }

    } catch( Exception $e ) {
        
        return false;
    }
}