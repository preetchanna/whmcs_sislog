<?php
/**
 *
 * An addon module allows you to add additional functionality to WHMCS. It
 * can provide both client and admin facing user interfaces, as well as
 * utilise hook functionality within WHMCS.
 *
 */

/**
 * Require any libraries needed for the module to function.
 * require_once __DIR__ . '/path/to/library/loader.php';
 *
 * Also, perform any initialization required by the service's library.
 */

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Multipay\Admin\AdminDispatcher;
use WHMCS\Module\Addon\Multipay\Client\ClientDispatcher;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define addon module configuration parameters.
 *
 * Includes a number of required system fields including name, description,
 * author, language and version.
 *
 * @return array
 */
function multipay_config()
{
    return [
        // Display name for your module
        'name' => 'Multipay Gateway',
        // Description displayed within the admin interface
        'description' => 'This module is used to manage the datatabale for Multipay SISLog payment gateway.',
        // Module author name
        'author' => 'ITCORE',
        // Default language
        'language' => 'english',
        // Version number
        'version' => '1.1',
       /* 'fields' => [
            // a text field type allows for single line text input
            'username' => [
                'FriendlyName' => 'Username',
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter your username here',
            ],
            // a password field type allows for masked text input
            'apiKey' => [
                'FriendlyName' => 'API Key',
                'Type' => 'password',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter api key here',
            ],
            // the yesno field type displays a single checkbox option
            'testMode' => [
                'FriendlyName' => 'Test Mode',
                'Type' => 'yesno',
                'Description' => 'Tick to enable test mode',
            ]
        ]*/
    ];
}

/**
 * Activate.
 *
 * Called upon activation of the module for the first time.
 * Use this function to perform any database and schema modifications
 * required by your module.
 *
 * This function is optional.
 *
 * @see https://developers.whmcs.com/advanced/db-interaction/
 *
 * @return array Optional success/failure message
 */
function multipay_activate()
{
    // Create custom tables and schema required by your module
    try {
        Capsule::schema()
            ->create(
                'mod_gateway_multipay',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->integer('invoiceid')->default(0);
                    $table->string('reference');
                    $table->string('entity');
                    $table->string('amount');
                    $table->text('messageid');
                    $table->string('transactionid');
                    $table->string('status');
                    $table->string('pay_status')->default('unpaid');
                    $table->date('deadline');
                    $table->timestamp('api_datetime')->nullable();
                    $table->timestamps();
                }
            );

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'The Multipay module is successfully activated.',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Unable to create mod_gateway_multipay: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate.
 *
 * Called upon deactivation of the module.
 * Use this function to undo any database and schema modifications
 * performed by your module.
 *
 * This function is optional.
 *
 * @see https://developers.whmcs.com/advanced/db-interaction/
 *
 * @return array Optional success/failure message
 */
function multipay_deactivate()
{
    // Undo any database and schema modifications made by your module here
    try {
        Capsule::schema()
            ->dropIfExists('mod_gateway_multipay');

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'The Multi Reference Pay module is successfully deactivated.',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            "status" => "error",
            "description" => "Unable to drop mod_gateway_multipay: {$e->getMessage()}",
        ];
    }
}

/**
 * Admin Area Output.
 *
 * Called when the addon module is accessed via the admin area.
 * Should return HTML output for display to the admin user.
 *
 * This function is optional.
 *
 * @see AddonModule\Admin\Controller::index()
 *
 * @return string
 */
function multipay_output($vars)
{
    // Get common module parameters
    $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
    $version = $vars['version']; // eg. 1.0
    $_lang = $vars['_lang']; // an array of the currently loaded language variables

    // Get module configuration parameters
    $configTextField = $vars['Text Field Name'];
    $configPasswordField = $vars['Password Field Name'];
    $configCheckboxField = $vars['Checkbox Field Name'];
    $configDropdownField = $vars['Dropdown Field Name'];
    $configRadioField = $vars['Radio Field Name'];
    $configTextareaField = $vars['Textarea Field Name'];

    // Dispatch and handle request here. What follows is a demonstration of one
    // possible way of handling this using a very basic dispatcher implementation.

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new AdminDispatcher();
    $response = $dispatcher->dispatch($action, $vars);
    echo $response;
}
