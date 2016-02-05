<?php
/**
 * Script to enable recurring payment in a service from a customer paypal account
 * @package chamilo.plugin.buycourses
 * @author Jose Loguercio Silva <jose.loguercio@beeznest.com>
 */
/**
 * Initialization
 */

require_once '../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services') === 'true';

$userInfo = api_get_user_info();

if (!$userInfo || !$includeServices) {
    api_not_allowed();
}

$orderId = isset($_REQUEST['order']) ? $_REQUEST['order'] : false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;

$paypalParams = $plugin->getPaypalParams();

$pruebas = $paypalParams['sandbox'] == 1;
$paypalUsername = $paypalParams['username'];
$paypalPassword = $paypalParams['password'];
$paypalSignature = $paypalParams['signature'];

require_once("paypalfunctions.php");


switch ($action) {
    case 'enable_recurring_payment':
        $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED);
        break;
    case 'disable_recurring_payment':
        $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);
        break;     
}