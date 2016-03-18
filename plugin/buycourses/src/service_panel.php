<?php
/**
 * User Panel
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */

$cidReset = true;

require_once '../../../main/inc/global.inc.php';
$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services') === 'true';
$includeSessions = $plugin->get('include_sessions') === 'true';
$servicesOnly = $plugin->get('show_services_only') === 'true';

// this is for clear the recurring payment process
unset($_SESSION['TOKEN']);
unset($_SESSION['action']);

$userInfo = api_get_user_info();

if (!$userInfo) {
    api_not_allowed();
}

$paymentTypes = $plugin->getPaymentTypes();
$serviceTypes = $plugin->getServiceTypes();

$serviceSaleStatuses['status_cancelled'] = BuyCoursesPlugin::SERVICE_STATUS_CANCELLED;
$serviceSaleStatuses['status_pending'] = BuyCoursesPlugin::SERVICE_STATUS_PENDING;
$serviceSaleStatuses['status_completed'] = BuyCoursesPlugin::SERVICE_STATUS_COMPLETED;

$serviceSales = $plugin->getServiceSale(null, $userInfo['user_id']);
$saleList = [];

foreach ($serviceSales as $sale) {
    $saleList[] = [
        'id' => $sale['id'],
        'name' => $sale['service']['name'],
        'service_type' => $serviceTypes[$sale['service']['applies_to']],
        'applies_to' => $sale['service']['applies_to'],
        'reference' => $sale['reference'],
        'date' => api_format_date(api_get_local_time($sale['buy_date']), DATE_TIME_FORMAT_LONG_24H),
        'date_end' => api_format_date(api_get_local_time($sale['date_end']), DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['currency'],
        'price' => $sale['price'],
        'payment_type' => $paymentTypes[$sale['payment_type']],
        'recurring_payment' => $sale['recurring_payment'],
        'recurring_profile_id' => $sale['recurring_profile_id'],
        'status' => $sale['status']  
    ];
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('UserPanel')];

$templateName = get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('service_sale_statuses', $serviceSaleStatuses);
$tpl->assign('sale_list', $saleList);
if ($servicesOnly) {
    $tpl->assign('show_services_only', true);
}

$content = $tpl->fetch('buycourses/view/service_panel.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();