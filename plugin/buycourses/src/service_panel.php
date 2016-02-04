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

$userInfo = api_get_user_info();

if (!$userInfo) {
    api_not_allowed();
}

$paymentTypes = $plugin->getPaymentTypes();
$serviceTypes = $plugin->getServiceTypes();

$orderId = isset($_REQUEST['order']) ? $_REQUEST['order'] : false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;

switch ($action) {
    case 'enable_recurring_payment':
        $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED);
        break;
    case 'disable_recurring_payment':
        $plugin->updateRecurringPayments($orderId, BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);
        break;     
}

$serviceSales = $plugin->getServiceSale(null, $userInfo['user_id']);
$saleList = [];

foreach ($serviceSales as $sale) {
    $saleList[] = [
        'id' => $sale['id'],
        'name' => $sale['service']['name'],
        'service_type' => $serviceTypes[$sale['service']['applies_to']],
        'reference' => $sale['reference'],
        'date' => api_format_date($sale['buy_date'], DATE_TIME_FORMAT_LONG_24H),
        'date_end' => api_format_date($sale['date_end'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['currency'],
        'price' => $sale['price'],
        'payment_type' => $paymentTypes[$sale['payment_type']],
        'recurring_payment' => $sale['recurring_payment']
    ];
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('UserPanel')];

$templateName = get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('sale_list', $saleList);

$content = $tpl->fetch('buycourses/view/service_panel.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();