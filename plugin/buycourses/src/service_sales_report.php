<?php

/* For license terms, see /license.txt */
/**
 * List of pending payments of the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
//Initialization
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');
$includeServices = $plugin->get('include_services');

if (isset($_GET['order'])) {
    $serviceSale = $plugin->getServiceSale($_GET['order']);

    if (empty($serviceSale)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self() . '?';
    
    switch ($_GET['action']) {
        case 'confirm':
            $plugin->completeServiceSale($serviceSale['id']);
            
            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('SubscriptionToServiceXSuccessful'), $serviceSale['service']['name']),
                    'success'
                )
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SERVICE_STATUS_COMPLETED,
                'sale' => $serviceSale['id']
            ]);
            break;
        case 'cancel':
            $plugin->cancelServiceSale($serviceSale['id']);

            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('OrderCancelled'),
                    'warning'
                )
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SERVICE_STATUS_CANCELLED,
                'sale' => $serviceSale['id']
            ]);
            break;
    }

    header("Location: $urlToRedirect");
    exit;
}

$saleStatuses = $plugin->getServiceSaleStatuses();
$paymentTypes = $plugin->getPaymentTypes();

$selectedFilterType = '0';
$selectedStatus = isset($_GET['status']) ? invtal($_GET['status']) : BuyCoursesPlugin::SERVICE_STATUS_PENDING;
$selectedSale = isset($_GET['sale']) ? intval($_GET['sale']) : 0;
$searchTerm = '';

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedFilterType = $form->getSubmitValue('filter_type');
    $selectedStatus = $form->getSubmitValue('status');
    $searchTerm = $form->getSubmitValue('user');

    if ($selectedStatus === false) {
        $selectedStatus = BuyCoursesPlugin::SERVICE_STATUS_PENDING;
    }

    if ($selectedFilterType === false) {
        $selectedFilterType = '0';
    }
}

$form->addRadio(
    'filter_type',
    get_lang('Filter'),
    [$plugin->get_lang('ByStatus'), $plugin->get_lang('ByUser')]
);
$form->addHtml('<div id="report-by-status" ' . ($selectedFilterType !== '0' ? 'style="display:none"' : '') . '>');
$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-user" ' . ($selectedFilterType !== '1' ? 'style="display:none"' : '') . '>');
$form->addText('user', get_lang('UserName'), false);
$form->addHtml('</div>');
$form->addButtonFilter(get_lang('Search'));
$form->setDefaults([
    'filter_type' => $selectedFilterType,
    'status' => $selectedStatus
]);

switch ($selectedFilterType) {
    case '0':
        $servicesSales = $plugin->getServiceSale(null, null, $selectedStatus);
        break;
    case '1':
        $servicesSales = $plugin->getServiceSale(null, $searchTerm);
        break;
}

$serviceSaleList = [];

foreach ($servicesSales as $sale) {
    $serviceSaleList[] = [
        'id' => $sale['id'],
        'reference' => $sale['reference'],
        'status' => $sale['status'],
        'date' => api_format_date($sale['buy_date'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['currency'],
        'price' => $sale['price'],
        'service_type' => $sale['service']['applies_to'],
        'service_name' => $sale['service']['name'],
        'complete_user_name' => $sale['buyer']['name'],
        'payment_type' => $paymentTypes[$sale['payment_type']]
    ];
    
}

//View
$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);

$toolbar = '';

if ($paypalEnable == 'true' && $commissionsEnable == 'true') {

    $toolbar .= Display::toolbarButton(
        $plugin->get_lang('PaypalPayoutCommissions'),
        api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/paypal_payout.php',
        'paypal',
        'primary',
        ['title' => $plugin->get_lang('PaypalPayoutCommissions')]
    );
    
    $template->assign('actions', $toolbar);
    
}

if ($commissionsEnable == 'true') {

    $toolbar .= Display::toolbarButton(
        $plugin->get_lang('PayoutReport'),
        api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/payout_report.php',
        'money',
        'info',
        ['title' => $plugin->get_lang('PayoutReport')]
    );
    
    $template->assign('actions', $toolbar);
    
}
$template->assign('form', $form->returnForm());
$template->assign('selected_sale', $selectedSale);
$template->assign('selected_status', $selectedStatus);
$template->assign('showing_services', true);
$template->assign('services_are_included', $includeServices);
$template->assign('sale_list', $serviceSaleList);
$template->assign('sale_status_cancelled', BuyCoursesPlugin::SERVICE_STATUS_CANCELLED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SERVICE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SERVICE_STATUS_COMPLETED);

$content = $template->fetch('buycourses/view/service_sales_report.tpl');


$template->assign('content', $content);
$template->display_one_col_template();
