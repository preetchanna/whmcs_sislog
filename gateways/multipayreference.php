<?php
/**
 * WHMCS SISLOG Merchant Gateway Module
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2019
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use WHMCS\Database\Capsule;
/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function multipayreference_MetaData()
{
    return array(
        'DisplayName' => 'Multipay Reference (SISLog)',
        'APIVersion' => '1.1', // Use API Version 1.1
    );
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function multipayreference_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Multipay Reference (SISLog)'
        ),
        // a text field type allows for single line text input
        'username' => array(
            'FriendlyName' => 'Username',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your username here'
        ),
        // a password field type allows for masked text input
        'apiKey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter api key here'
        ),
        // grace Period for Reference
        'gracePeriod' => array(
            'FriendlyName' => 'Grace Period',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter number of days'
        ),
        // the yesno field type displays a single checkbox option
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode'
        ),
        // transaction prefix
        'transPrefix' => array(
            'FriendlyName' => 'Transaction Prefix',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Max character limit 3, Prohibited character underscore(_)'
        )
    );
}

function multipayreference_link($params)
{
    /**
     * sislog gateway only supports MZN currency
     * Thus, make sure WHMCS currency list should have currency MZN
     */

    // Invoice Parameters
    $invoiceId      = $params['invoiceid'];
    $description    = $params["description"];
    $currencyCode   = $params['currency'];
    $amount         = $params['amount'];

    if($currencyCode != "MZN"){
        $htmlOutput = '<span class="label label-danger">SISLog only supports MZN currency</span>';
        return $htmlOutput;
    }

    $sislog_format_currecny_value = str_replace(".","",$amount);

    // Gateway Configuration Parameters
    $username = $params['username'];
    // $apiKey = $params['apiKey'];
    $testMode = $params['testMode'];

    // System Parameters
    // $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    // $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    // $moduleDisplayName = $params['name'];
    // $moduleName = $params['paymentmethod'];
    // $whmcsVersion = $params['whmcsVersion'];

    $url = $systemUrl . 'modules/gateways/multipayreference/createreference.php';

    $postfields = array();
    $postfields['username']     = $username;
    $postfields['invoiceId']    = $invoiceId;
    $postfields['amount']       = $amount;

    $sislog_format_currecny_value = str_replace(".","",$amount);
    //The SISLog production, only accept values above 90MT
    if(empty($testMode) && $sislog_format_currecny_value < 9000){
        return "<p class='lead'>Value is low for your chosen payment method!</p>";
    }

    $query = Capsule::table('mod_gateway_sislog')->where([
        'invoiceid' => $invoiceId,
        'pay_status' => 'unpaid'
    ]);
    $data = $query->first();
    if($query->exists()){
        $currentDate = date("Y-m-d");
        if($currentDate>$data->deadline){
            $langPayNow = "Regenerate Pay Reference";
        } else {
            $formattedAmount = formatCurrency($amount, $params['currencyId']);
            $htmlOutput = "Reference ID: {$data->reference} <br/>";
            $htmlOutput .= "Entity ID: {$data->entity} <br/>";
            if($params['clientdetails']['model']->currencyCode != 'MZN')
                $htmlOutput .= "Amount: {$formattedAmount}";
            return $htmlOutput;
        }
    }

    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }
    $htmlOutput .= '<input type="submit" class="btn btn-success" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}
