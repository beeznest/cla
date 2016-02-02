<?php
/* For license terms, see /license.txt */
/**
 * Process purchase confirmation script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

$serviceSaleId = $_SESSION['bc_service_sale_id'];

if (empty($serviceSaleId)) {
    api_not_allowed(true);
}

$serviceSale = $plugin->getServiceSale($serviceSaleId);

if (empty($serviceSale)) {
    api_not_allowed(true);
}

$currency = $plugin->getCurrency($serviceSale['currency_id']);

switch ($serviceSale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $pruebas = $paypalParams['sandbox'] == 1;
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        require_once("paypalfunctions.php");

        $i = 0;
        $extra = "&L_PAYMENTREQUEST_0_NAME0={$serviceSale['service']['name']}";
        $extra .= "&L_PAYMENTREQUEST_0_AMT0={$serviceSale['price']}";
        $extra .= "&L_PAYMENTREQUEST_0_QTY0=1";

        $expressCheckout = CallShortcutExpressCheckout(
            $serviceSale['price'],
            $currency['iso_code'],
            'paypal',
            api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_success.php',
            api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/error.php',
            $extra
        );

        if ($expressCheckout["ACK"] !== 'Success') {
            $erroMessage = vsprintf(
                $plugin->get_lang('ErrorOccurred'),
                [$expressCheckout['L_ERRORCODE0'], $expressCheckout['L_LONGMESSAGE0']]
            );
            Display::addFlash(
                Display::return_message($erroMessage, 'error', false)
            );
            header('Location: ../index.php');
            exit;
        }

        RedirectToPayPal($expressCheckout["TOKEN"]);
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:

        switch ($serviceSale['node_type']) {
            case BuyCoursesPlugin::SERVICE_TYPE_USER:
                $buyingCourse = true;
                $user = api_get_user_info(intval($serviceSale['node_id']));
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_COURSE:
                $buyingCourse = true;
                $course = $plugin->getCourseInfo($serviceSale['node_id']);
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_SESSION:
                $buyingSession = true;
                $session = $plugin->getSessionInfo($serviceSale['node_id']);
                break;
        }

        $transferAccounts = $plugin->getTransferAccounts();
        $userInfo = api_get_user_info($serviceSale['buyer']['id']);

        $form = new FormValidator('success', 'POST', api_get_self(), null, null, FormValidator::LAYOUT_INLINE);

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            if (isset($formValues['cancel'])) {
                $plugin->cancelSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id']);

                header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/index.php');
                exit;
            }

            $messageTemplate = new Template();
            $messageTemplate->assign(
                'service_sale',
                [
                    'name' => $serviceSale['service']['name'],
                    'buyer' => $serviceSale['buyer']['name'],
                    'buy_date' => api_format_date($serviceSale['buy_date'], DATE_TIME_FORMAT_LONG_24H),
                    'start_date' => api_format_date($serviceSale['start_date'], DATE_TIME_FORMAT_LONG_24H),
                    'end_date' => api_format_date($serviceSale['end_date'], DATE_TIME_FORMAT_LONG_24H),
                    'currency' => $currency['currency'],
                    'price' => $serviceSale['price'],
                    'reference' => $serviceSale['reference']
                ]
            );
            $messageTemplate->assign('transfer_accounts', $transferAccounts);
            $buyer = api_get_user_info($serviceSale['buyer']['id']);
            api_mail_html(
                $buyer['complete_name'],
                $buyer['email'],
                $plugin->get_lang('bc_subject'),
                $messageTemplate->fetch('buycourses/view/message_transfer.tpl')
            );

            Display::addFlash(
                Display::return_message(
                    sprintf(
                        $plugin->get_lang('PurchaseStatusX'),
                        $plugin->get_lang('PendingReasonByTransfer')
                    ),
                    'success',
                    false
                )
            );

            unset($_SESSION['bc_service_sale_id']);
            header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_catalog.php');
            exit;
        }

        $form->addButton('confirm', $plugin->get_lang('ConfirmOrder'), 'check', 'success');
        $form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

        $template = new Template();

        $template->assign('title', $serviceSale['service']['name']);
        $template->assign('price', $serviceSale['price']);
        $template->assign('currency', $serviceSale['currency_id']);
        $template->assign('buying_service', $serviceSale);
        $template->assign('user', $userInfo);
        $template->assign('service', $serviceSale);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();
        break;
}

