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
$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : false;

if (isset($_SESSION['action'])) {
    $action = $_SESSION['action'];
}

$profileId = isset($_REQUEST['profile']) ? $_REQUEST['profile'] : false;

$paypalParams = $plugin->getPaypalParams();

$pruebas = $paypalParams['sandbox'] == 1;
$paypalUsername = $paypalParams['username'];
$paypalPassword = $paypalParams['password'];
$paypalSignature = $paypalParams['signature'];

$serviceSale = $plugin->getServiceSale($orderId);

require_once("paypalfunctions.php");


switch ($action) {
    case 'enable_recurring_payment':
        
        $_SESSION['action'] = 'enable_recurring_payment';
        
        if (!$token) {
            
            $expectedATM = floatval($serviceSale['price'])/2;
        
            $extraParams = "&PAYMENTREQUEST_0_AMT=0";
            $extraParams .= "&MAXAMT={$expectedATM}";
            $extraParams .= "&RETURNURL=" . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/recurring_payment_process.php?order='.$orderId;
            $extraParams .= "&CANCELURL=" . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/error.php';
            $extraParams .= "&L_PAYMENTREQUEST_0_NAME0={$serviceSale['service']['name']}";
            $extraParams .= "&L_PAYMENTREQUEST_0_AMT0=0";
            $extraParams .= "&L_PAYMENTREQUEST_0_QTY0=1";
            $extraParams .= "&L_BILLINGTYPE0=RecurringPayments";
            $extraParams .= "&L_BILLINGAGREEMENTDESCRIPTION0={$serviceSale['service']['name']}";

            $setExpressCheckout = MinimalExpressCheckout($extraParams);
            
            if ($setExpressCheckout['ACK'] == 'Success') {
                
                RedirectToPayPal($setExpressCheckout['TOKEN']);
                
            } elseif ($setExpressCheckout['ACK'] !== 'Success') {
                $erroMessage = vsprintf(
                    $plugin->get_lang('ErrorOccurred'),
                    [$setExpressCheckout['L_ERRORCODE0'], $setExpressCheckout['L_LONGMESSAGE0']]
                );
                Display::addFlash(
                    Display::return_message($erroMessage, 'error', false)
                );
                header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
                exit;
            }
            
        } else {
            $paypalAccount = $plugin->verifyPaypalAccountByBeneficiary($serviceSale['buyer']['id'], true);
        
            if (!$paypalAccount) {
                Display::addFlash(
                    Display::return_message(get_lang('ThereIsNoPaypalAccount'), 'error', false)
                );
                header('Location: service_panel.php');
                exit;
            }
            
            $extra = "&L_PAYMENTREQUEST_0_ITEMCATEGORY0=Digital";
            $extra .= "&L_PAYMENTREQUEST_0_NAME0={$serviceSale['service']['name']}";
            $extra .= "&L_PAYMENTREQUEST_0_AMT0={$serviceSale['price']}";
            $extra .= "&L_PAYMENTREQUEST_0_QTY0=1";

            $recurringPaymentProfile = CreateRecurringPaymentsProfile(
                $serviceSale['buyer']['name'],
                $serviceSale['date_end'],
                $serviceSale['reference'],
                $serviceSale['service']['name'],
                'Day',
                $serviceSale['service']['duration_days'],
                $serviceSale['price'],
                $serviceSale['currency'],
                $paypalAccount,
                $extra
            );

            if ($recurringPaymentProfile['ACK'] == 'Success') {
                $plugin->updateRecurringProfileId($serviceSale['id'], $recurringPaymentProfile['PROFILEID']);
                $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED);
            } else {
                $erroMessage = vsprintf(
                    $plugin->get_lang('ErrorOccurred'),
                    [$recurringPaymentProfile['L_ERRORCODE0'], $recurringPaymentProfile['L_LONGMESSAGE0']]
                );
                Display::addFlash(
                    Display::return_message($erroMessage, 'error', false)
                );
                header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
                exit;
            }
        }
   
        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
        exit;
        break;
    case 'disable_recurring_payment':
        
        $update = ManageRecurringPaymentsProfileStatus($serviceSale['recurring_profile_id'], BuyCoursesPlugin::PAYPAL_RECURRING_PAYMENT_CANCEL);

        if ($update['ACK'] == 'Success') {
            $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);    
        } else {
            $erroMessage = vsprintf(
                $plugin->get_lang('ErrorOccurred'),
                [$update['L_ERRORCODE0'], $update['L_LONGMESSAGE0']]
            );
            Display::addFlash(
                Display::return_message($erroMessage, 'error', false)
            );
            header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
            exit;
        }
        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_panel.php');
        exit;
        break;     
}