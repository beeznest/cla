<?php
/**
 * Script to enable or disable autobilling in recurring payment in a service from a customer paypal account
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
$profileId = isset($_REQUEST['profile']) ? $_REQUEST['profile'] : false;

$paypalParams = $plugin->getPaypalParams();

$pruebas = $paypalParams['sandbox'] == 1;
$paypalUsername = $paypalParams['username'];
$paypalPassword = $paypalParams['password'];
$paypalSignature = $paypalParams['signature'];

require_once("paypalfunctions.php");


switch ($action) {
    case 'enable_recurring_payment':
        $update = UpdateRecurringPaymentsProfile($profileId, BuyCoursesPlugin::AUTOBILLING_ENABLED);
        if ($update['ACK'] == 'Success') {
            $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED);
        } else {
            $erroMessage = vsprintf(
                $plugin->get_lang('ErrorOccurred'),
                [$recurringPaymentProfile['L_ERRORCODE0'], $recurringPaymentProfile['L_LONGMESSAGE0']]
            );
            Display::addFlash(
                Display::return_message($erroMessage, 'error', false)
            );
            header('Location: service_catalog.php');
            exit;
        }
        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
        exit;
        break;
    case 'disable_recurring_payment':
        $update = UpdateRecurringPaymentsProfile($profileId, BuyCoursesPlugin::AUTOBILLING_DISABLED);
        if ($update['ACK'] == 'Success') {
            $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);    
        } else {
            $erroMessage = vsprintf(
                $plugin->get_lang('ErrorOccurred'),
                [$recurringPaymentProfile['L_ERRORCODE0'], $recurringPaymentProfile['L_LONGMESSAGE0']]
            );
            Display::addFlash(
                Display::return_message($erroMessage, 'error', false)
            );
            header('Location: service_catalog.php');
            exit;
        }
        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
        exit;
        break;     
}