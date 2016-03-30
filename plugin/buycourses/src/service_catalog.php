<?php
/**
 * List of services
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
$servicesOnly = $plugin->get('show_services_only') === 'true';

$nameFilter = null;
$minFilter = 0;
$maxFilter = 0;
$appliesToFilter = '';
$renewableFilter = '';

$form = new FormValidator('search_filter_form', 'get', null, null, [], FormValidator::LAYOUT_INLINE);

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $nameFilter = isset($formValues['name']) ? $formValues['name'] : null;
    $minFilter = isset($formValues['min']) ? $formValues['min'] : 0;
    $maxFilter = isset($formValues['max']) ? $formValues['max'] : 0;
    $appliesToFilter = isset($formValues['applies_to']) ? $formValues['applies_to'] : '';
    $renewableFilter = isset($formValues['renewable']) ? $formValues['renewable'] : '';
}

$form->addHeader($plugin->get_lang('SearchFilter'));
$form->addText('name', $plugin->get_lang('ServiceName'), false);
$form->addElement(
    'number',
    'min',
    $plugin->get_lang('MinimumPrice'),
    ['step' => '0.01', 'min' => '0']
);
$form->addElement(
    'number',
    'max',
    $plugin->get_lang('MaximumPrice'),
    ['step' => '0.01', 'min' => '0']
);
$appliesToOptions = [
    '' => get_lang('Any'),
    0 => get_lang('None'),
    1 => get_lang('User'),
    2 => get_lang('Course'),
    3 => get_lang('Session')
];
$form->addSelect('applies_to', $plugin->get_lang('AppliesTo'), $appliesToOptions);
$renewableOptions = [
    '' => get_lang('Any'),
    0 => get_lang('No'),
    1 => get_lang('Yes')
];
$form->addSelect('renewable', $plugin->get_lang('Renewable').'?', $renewableOptions);
$form->addHtml('<hr>');
$form->addButtonFilter(get_lang('Search'));

$serviceList = $plugin->getCatalogServiceList($nameFilter, $minFilter, $maxFilter, $appliesToFilter, $renewableFilter);

//View
if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        'url' => 'configuration.php',
        'name' => $plugin->get_lang('AvailableCoursesConfiguration')
    ];
    $interbreadcrumb[] = [
        'url' => 'paymentsetup.php',
        'name' => $plugin->get_lang('PaymentsConfiguration')
    ];
} else {
    $interbreadcrumb[] = [
        'url' => '../index.php',
        'name' => $plugin->get_lang('UserPanel')
    ];
}

$templateName = $plugin->get_lang('ListOfServicesOnSale');
$tpl = new Template($templateName);
$tpl->assign('search_filter_form', $form->returnForm());
$tpl->assign('showing_services', true);
$tpl->assign('services', $serviceList);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('show_services_only', $servicesOnly);

$content = $tpl->fetch('buycourses/view/catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
