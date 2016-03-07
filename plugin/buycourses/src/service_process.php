<?php
/* For license terms, see /license.txt */
/**
 * Process payments for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
require_once '../config.php';

$currentUserId = api_get_user_id();

if (empty($currentUserId)) {
    header('Location: ' . api_get_path(WEB_CODE_PATH) . 'auth/inscription.php');
    exit;
}

$em = Database::getManager();
$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services');
$paypalEnabled = $plugin->get('paypal_enable') === 'true';
$transferEnabled = $plugin->get('transfer_enable') === 'true';

if ($includeServices !== 'true') {
    api_not_allowed(true);
}

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    die;
}

$serviceId = intval($_REQUEST['i']);

$typeUser = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_USER;
$typeCourse = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_COURSE;
$typeSession = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_SESSION;
$typeSubscriptionPackage = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_SUBSCRIPTION_PACKAGE;
$queryString = 'i=' . intval($_REQUEST['i']) . '&t=' . intval($_REQUEST['t']);

$serviceInfo = $plugin->getServices(intval($_REQUEST['i']));
$userInfo = api_get_user_info();

$form = new FormValidator('confirm_sale');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    
    if (!$formValues['info_select']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('AdditionalInfoRequired'), 'error', false)
        );
        header('Location:' . api_get_self() . '?' . $queryString);
        exit;
    }
    
    if (!$formValues['payment_type']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToSelectPaymentType'), 'error', false)
        );
        header('Location:' . api_get_self() . '?' . $queryString);
        exit;
    }
    
    $serviceSaleId = $plugin->registerServiceSale($serviceId, $formValues['payment_type'], $formValues['info_select']);

    if ($serviceSaleId !== false) {
        $_SESSION['bc_service_sale_id'] = $serviceSaleId;
        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_process_confirm.php');  
    }

    exit;
}

$form->addHeader($plugin->get_lang('UserInformation'));
$form->addText('name', get_lang('Name'), false, ['cols-size' => [5, 7, 0]]);
$form->addText('username', get_lang('Username'), false, ['cols-size' => [5, 7, 0]]);
$form->addText('email', get_lang('EmailAddress'), false, ['cols-size' => [5, 7, 0]]);
$form->addHeader($plugin->get_lang('AdditionalInfo'));
$form->addHtml(Display::return_message($plugin->get_lang('PleaseSelectTheCorrectInfoToApplyTheService'), 'info'));

$selectOptions = [];

if ($typeUser) {
    $users = $em->getRepository('ChamiloUserBundle:User')->findAll();
    $selectOptions[$userInfo['user_id']] = api_get_person_name($userInfo['firstname'], $userInfo['lastname']) . ' (' . get_lang('Myself') . ')';
    if (!empty($users)) {
        foreach ($users as $user) {
            if (intval($userInfo['user_id']) !== intval($user->getId())) {
                $selectOptions[$user->getId()] = $user->getCompleteNameWithUsername();
            }
        }
    }
    $form->addSelect('info_select',get_lang('User'), $selectOptions);
} elseif ($typeCourse) {
    $user = $em->getRepository('ChamiloUserBundle:User')->find($currentUserId);
    $courses = $user->getCourses();
    if (!empty($courses)) {
        foreach ($courses as $course) {
            $selectOptions[$course->getCourse()->getId()] = $course->getCourse()->getTitle();
        }
    }
    $form->addSelect('info_select',get_lang('Course'), $selectOptions);
} elseif ($typeSession) {
    $user = $em->getRepository('ChamiloUserBundle:User')->find($currentUserId);
    $sessions = $user->getSessionCourseSubscriptions();
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            $selectOptions[$session->getSession()->getId()] = $session->getSession()->getName();
        }
    }
    $form->addSelect('info_select',get_lang('Session'), $selectOptions);
} elseif ($typeSubscriptionPackage) {
    $form->addText('info_select', $plugin->get_lang('PackageName'), true, ['cols-size' => [5, 7, 0]]);
}

$form->addHeader($plugin->get_lang('PaymentMethods'));

$paymentTypesOptions = $plugin->getPaymentTypes();

if (!$paypalEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL]);
}

if (!$transferEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER]);
}

$form->addRadio('payment_type', null, $paymentTypesOptions);
$form->addHidden('t', intval($_GET['t']));
$form->addHidden('i', intval($_GET['i']));
$form->freeze(['name', 'username', 'email']);
$form->setDefaults([
    'name' => $userInfo['complete_name'],
    'username' => $userInfo['username'],
    'email' => $userInfo['email']
]);
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = array("url" => "course_catalog.php", "name" => $plugin->get_lang('CourseListOnSale'));

$tpl = new Template($templateName);
$tpl->assign('buying_service', true);
$tpl->assign('service', $serviceInfo);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form', $form->returnForm());


$content = $tpl->fetch('buycourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
