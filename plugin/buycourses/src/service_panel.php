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

$paymentTypes = $plugin->getPaymentTypes();

$serviceSales = $plugin->getServiceSale(null, $userInfo['user_id']);
$saleList = [];

foreach ($serviceSales as $sale) {
    $saleList[] = [
        'id' => $sale['id'],
        'name' => $sale['service']['name'],
        'reference' => $sale['reference'],
        'date' => api_format_date($sale['buy_date'], DATE_TIME_FORMAT_LONG_24H),
        'date_end' => api_format_date($sale['date_end'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['currency'],
        'price' => $sale['price'],
        'payment_type' => $paymentTypes[$sale['payment_type']]
    ];
}

$toolbar = Display::toolbarButton(
    $plugin->get_lang('CourseListOnSale'),
    'course_catalog.php',
    'search-plus',
    'primary',
    ['title' => $plugin->get_lang('CourseListOnSale')]
);

$templateName = get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('sale_list', $saleList);

$content = $tpl->fetch('buycourses/view/service_panel.tpl');

$tpl->assign('actions', $toolbar);
$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();