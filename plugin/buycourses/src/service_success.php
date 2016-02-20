<?php
/* For license terms, see /license.txt */
/**
 * Success page for the purchase of a service in the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$paypalEnabled = $plugin->get('paypal_enable') === 'true';

if (!$paypalEnabled) {
    api_not_allowed(true);
}

$serviceSaleId = $_SESSION['bc_service_sale_id'];

$serviceSale = $plugin->getServiceSale($serviceSaleId);

if (empty($serviceSale)) {
    api_not_allowed(true);
}


$paypalParams = $plugin->getPaypalParams();

$pruebas = $paypalParams['sandbox'] == 1;
$paypalUsername = $paypalParams['username'];
$paypalPassword = $paypalParams['password'];
$paypalSignature = $paypalParams['signature'];

require_once("paypalfunctions.php");

$form = new FormValidator('success', 'POST', api_get_self(), null, null, FormValidator::LAYOUT_INLINE);
$form->addButton('confirm', $plugin->get_lang('ConfirmOrder'), 'check', 'success');
$form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    if (isset($formValues['cancel'])) {
        $plugin->cancelServiceSale($serviceSale['id']);

        unset($_SESSION['bc_service_sale_id']);

        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/index.php');
        exit;
    }

    $confirmPayments = ConfirmPayment($serviceSale['price']);

    if ($confirmPayments['ACK'] !== 'Success') {
        $erroMessage = vsprintf(
            $plugin->get_lang('ErrorOccurred'),
            [$expressCheckout['L_ERRORCODE0'], $confirmPayments['L_LONGMESSAGE0']]
        );
        Display::addFlash(
            Display::return_message($erroMessage, 'error', false)
        );
        header('Location: ../index.php');
        exit;
    }

    $transactionId = $confirmPayments["PAYMENTINFO_0_TRANSACTIONID"];
    $transactionType = $confirmPayments["PAYMENTINFO_0_TRANSACTIONTYPE"];

    switch ($confirmPayments["PAYMENTINFO_0_PAYMENTSTATUS"]) {
        case 'Completed':
            $serviceSaleIsCompleted = $plugin->completeServiceSale($serviceSale['id']);

            if ($serviceSaleIsCompleted) {
                Display::addFlash(
                    Display::return_message(
                        sprintf($plugin->get_lang('SubscriptionToServiceXSuccessful'), $serviceSale['service']['name']),
                        'success'
                    )
                );
                
                
                $paypalAccount = $plugin->verifyPaypalAccountByBeneficiary($serviceSale['buyer']['id'], true);
    
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
                
                break;
            }

            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
        case 'Pending':
            switch ($confirmPayments["PAYMENTINFO_0_PENDINGREASON"]) {
                case 'address':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByAddress');
                    break;
                case 'authorization':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByAuthorization');
                    break;
                case 'echeck':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByEcheck');
                    break;
                case 'intl':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByIntl');
                    break;
                case 'multicurrency':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByMulticurrency');
                    break;
                case 'order':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByOrder');
                    break;
                case 'paymentreview':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByPaymentReview');
                    break;
                case 'regulatoryreview':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByRegulatoryReview');
                    break;
                case 'unilateral':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByUnilateral');
                    break;
                case 'upgrade':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByUpgrade');
                    break;
                case 'verify':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByVerify');
                    break;
                case 'other':
                    //no break
                default:
                    $purchaseStatus = $plugin->get_lang('PendingReasonByOther');
                    break;
            }

            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('PurchaseStatusX'), $purchaseStatus),
                    'warning',
                    false
                )
            );
            break;
        default:
            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
    }

    unset($_SESSION['bc_service_sale_id']);
    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_catalog.php');
    exit;
}

$token = isset($_GET['token']) ? Security::remove_XSS($_GET['token']) : null;

if (empty($token)) {
    api_not_allowed(true);
}

$shippingDetails = GetShippingDetails($token);

if ($shippingDetails['ACK'] !== 'Success') {
    $erroMessage = vsprintf(
        $plugin->get_lang('ErrorOccurred'),
        [$expressCheckout['L_ERRORCODE0'], $shippingDetails['L_LONGMESSAGE0']]
    );
    Display::addFlash(
        Display::return_message($erroMessage, 'error', false)
    );
    header('Location: ../index.php');
    exit;
}

$interbreadcrumb[] = array("url" => "service_catalog.php", "name" => $plugin->get_lang('ServiceListOnSale'));

$templateName = $plugin->get_lang('PaymentMethods');
$tpl = new Template($templateName);

$tpl->assign('title', $serviceSale['service']['name']);
$tpl->assign('price', $serviceSale['price']);
$tpl->assign('currency', $serviceSale['currency_id']);
$tpl->assign('service', $serviceSale);
$tpl->assign('buying_service', true);
$tpl->assign('user', api_get_user_info($serviceSale['buyer']['id']));
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('buycourses/view/success.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
