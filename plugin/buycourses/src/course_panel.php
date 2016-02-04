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
$includeSessions = $plugin->get('include_sessions') === 'true';
$includeServices = $plugin->get('include_services') === 'true';

$userInfo = api_get_user_info();

if (!$userInfo) {
    api_not_allowed();
}

$productTypes = $plugin->getProductTypes();
$saleStatuses = $plugin->getSaleStatuses();
$paymentTypes = $plugin->getPaymentTypes();

$sales = $plugin->getSaleListByUserId($userInfo['user_id']);

$saleList = [];

foreach ($sales as $sale) {
    if ($sale['product_type'] == 1) {
        $saleList[] = [
            'id' => $sale['id'],
            'reference' => $sale['reference'],
            'date' => api_format_date($sale['date'], DATE_TIME_FORMAT_LONG_24H),
            'currency' => $sale['iso_code'],
            'price' => $sale['price'],
            'product_name' => $sale['product_name'],
            'product_type' => $productTypes[$sale['product_type']],
            'payment_type' => $paymentTypes[$sale['payment_type']]
        ]; 
    }
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('UserPanel')];

$templateName = get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sale_list', $saleList);

$content = $tpl->fetch('buycourses/view/course_panel.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
