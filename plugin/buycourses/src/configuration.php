<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSession = $plugin->get('include_sessions') === 'true';
$servicesOnly = $plugin->get('show_services_only') === 'true';

api_protect_admin_script(true);

Display::addFlash(Display::return_message(get_lang('Info').' - '.$plugin->get_lang('CoursesInSessionsDoesntDisplayHere'), 'info'));

$courses = $plugin->getCoursesForConfiguration();
$services = $plugin->getServices();

//view
if ($servicesOnly) {
    $interbreadcrumb[] = [
        'url' => 'service_catalog.php',
        'name' => $plugin->get_lang('ListOfServicesOnSale')
    ];
    $templateName = $plugin->get_lang('Services');
} else {
    $interbreadcrumb[] = [
        'url' => 'course_catalog.php',
        'name' => $plugin->get_lang('CourseListOnSale')
    ];
    $templateName = $plugin->get_lang('AvailableCourses');
}
$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration')
];

$tpl = new Template($templateName);
$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
$tpl->assign('courses', $courses);
$tpl->assign('services', $services);
$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('show_services_only', $servicesOnly);

if ($includeSession) {
    $sessions = $plugin->getSessionsForConfiguration();

    $tpl->assign('sessions', $sessions);
}

$content = $tpl->fetch('buycourses/view/configuration.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
