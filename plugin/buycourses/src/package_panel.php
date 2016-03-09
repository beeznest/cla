<?php

require_once '../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services') === 'true';

if (!$includeServices) {
    api_not_allowed();
}

$serviceSaleId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;

$serviceSale = $plugin->getServiceSale($serviceSaleId);

$subscriberUsers = $plugin->getSubscriberUsers($serviceSaleId);

$em = Database::getManager();
$userGroup = $em->getRepository('ChamiloCoreBundle:Usergroup')->find(intval($serviceSale['node_id']));
$courses = $em->getRepository('ChamiloCoreBundle:Course')->findAll();
$sessions = $em->getRepository('ChamiloCoreBundle:Session')->findAll();

$templateName = $plugin->get_lang('SubscriptionPackage') . ' "' . $userGroup->getName() .'"';
$tpl = new Template($templateName);
$tpl->assign('package', $subscriberUsers);
$tpl->assign('package_id', $serviceSaleId);
$tpl->assign('group_id', $serviceSale['node_id']);
$tpl->assign('courses', $courses);
$tpl->assign('sessions', $sessions);

$content = $tpl->fetch('buycourses/view/package_panel.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();