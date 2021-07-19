<?php

// if (!defined("WHMCS")) {
//     die("This file cannot be accessed directly");
// }

require_once __DIR__ . '/../../../init.php';
App::load_function('gateway');
App::load_function('invoice');

use WHMCS\Database\Capsule;

$gatewayModuleName = "multipayreference";
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

extract($_POST);//$username $invoiceId $amount
$systemUrl = $gatewayParams['systemurl'];
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
// echo $gatewayParams["username"], $username;
$validRequest = isset($_POST["username"]) && isset($_POST["invoiceId"]) && ($gatewayParams["username"] == $username);

if($validRequest){
    $endURL = ($gatewayParams["testMode"] === "on")
        ?"https://lin4.sislog.com/mobile/reference/request"
        :"https://sms2q.com/mobile/reference/request";

    $apiKey     = $gatewayParams["apiKey"];
    $grace_days = (is_numeric($gatewayParams["gracePeriod"]) 
                    && ($gatewayParams["gracePeriod"]>0 && $gatewayParams["gracePeriod"]<=30))
                    ?$gatewayParams["gracePeriod"]:3;

    $sislog_format_currecny_value = str_replace(".","",$amount);

    $Date = date("Y-m-d");
    $api_deadline   = date('Ymd', strtotime($Date. ' + '. $grace_days .' days'));
    $db_deadline    = date('Y-m-d', strtotime($Date. ' + '. $grace_days .' days'));

    // $unique_transactionid = substr($invoiceId .'_'. time(),0,22);//api supports length 22
    $transPrefix = $gatewayParams["transPrefix"];
    if(strlen($transPrefix) > 3 || strpos($transPrefix, '_'))
        $transPrefix = "WHM";

    $unique_transactionid = substr($transPrefix."_".$invoiceId."_".bin2hex(random_bytes(6)),0,22);//api supports length 22
    $postData = [
        'username'      => $username,
        'transactionId' => $unique_transactionid,
        'value'         => $sislog_format_currecny_value,
        'deadline'      => $api_deadline
    ];

    $responseData = multipayreference_curl($endURL, $apiKey, $postData);

    $transactionStatus = 'Request: Failure';
    if (strtolower($responseData["status"]) == "valid") {
        $transactionStatus = 'Request: Success';
        try {
            $apiCreateDateTime = date('Y-m-d h:i:s', strtotime($responseData['dateTime']));
            $query = Capsule::table('mod_gateway_sislog')->where([
                'invoiceid' => $invoiceId
            ]);
            if($query->exists()){
                Capsule::table('mod_gateway_sislog')
                ->where(['invoiceid' => $invoiceId, 'pay_status' => 'unpaid'])
                ->update(
                    [
                        'reference'     => $responseData['reference'],
                        'entity'        => $responseData['entity'],
                        'amount'        => $responseData['value'],
                        'messageid'     => $responseData['messageId'],
                        'transactionid' => $unique_transactionid,
                        'status'        => $responseData['status'],
                        'deadline'      => $db_deadline,
                        'api_datetime'  => $apiCreateDateTime,
                        'updated_at'    => date('Y-m-d h:i:s')
                    ]
                );
            } else {
                $insert = [
                    'invoiceid'     => $invoiceId,
                    'reference'     => $responseData['reference'],
                    'entity'        => $responseData['entity'],
                    'amount'        => $responseData['value'],
                    'messageid'     => $responseData['messageId'],
                    'transactionid' => $unique_transactionid,
                    'status'        => $responseData['status'],
                    'pay_status'    => 'unpaid',
                    'deadline'      => $db_deadline,
                    'api_datetime'  => $apiCreateDateTime,
                    'created_at'    => date('Y-m-d h:i:s'),
                    'updated_at'    => date('Y-m-d h:i:s')
                ];
                
                Capsule::connection()->transaction(
                    function ($connectionManager) use($insert)
                    {
                        $connectionManager->table('mod_gateway_sislog')->insert($insert);
                    }
                );
            }
            
        } catch (\Exception $e) {
            echo "Uh oh! Inserting didn't work, but I was able to rollback. {$e->getMessage()}";
        }
        
    }
    $responseData = array_merge($responseData,[
        'invoiceid' => $invoiceId
    ]);
    logTransaction($gatewayParams['name'], $responseData, $transactionStatus);
    redir("id={$invoiceId}",$systemUrl . "viewinvoice.php");
    // callback3DSecureRedirect($invoiceId, false);
} else {
    die("InValid Request!!");
}

function multipayreference_curl($url, $apiKey, $postData){
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>json_encode($postData),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: '. $apiKey
    ),
    ));

    $response = curl_exec($curl);
    if (curl_error($curl)) {
        die('Unable to connect: ' . curl_errno($curl) . ' - ' . curl_error($curl));
    }
    curl_close($curl);
    //Decode response
    return json_decode($response, true);
}