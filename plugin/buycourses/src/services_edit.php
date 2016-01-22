<?php
/* For license terms, see /license.txt */
/**
 * Create new Services for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
use Doctrine\Common\Collections\Criteria;

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$serviceId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

if (!$serviceId) {
    header('Location: configuration.php');
}

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();
$em = Database::getManager();
$users = $em->getRepository('ChamiloUserBundle:User')->findAll();
$userOptions = [];
if (!empty($users)) {
    foreach ($users as $user) {
        $userOptions[$user->getId()] = $user->getCompleteNameWithUsername();
    }
}

api_protect_admin_script(true);

//view
$interbreadcrumb[] = [
    'url' => 'configuration.php',
    'name' => $plugin->get_lang('AvailableCourses')
];

$service = $plugin->getServices($serviceId);

$formDefaultValues = [
    'name' => $service['name'],
    'description' => $service['description'],
    'price' => $service['price'],
    'duration_days' => $service['duration_days'],
    'owner_id' => intval($service['owner_id']),
    'applies_to' => intval($service['applies_to']),
    'renewable' => ($service['renewable'] == 1) ? true : false,
    'visibility' => ($service['visibility'] == 1) ? true : false
];

$form = new FormValidator('Skill');
$form->addText('name', $plugin->get_lang('ServiceName'));
$form->addTextarea('description', $plugin->get_lang('Description'));
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currency['iso_code']],
    ['step' => 0.01]
);
$form->addElement(
    'number',
    'duration_days',
    [$plugin->get_lang('Duration'), null, get_lang('Days')],
    ['step' => 1]
);
$form->addCheckBox('renewable', $plugin->get_lang('Renewable'));
$form->addElement(
    'radio',
    'applies_to',
    $plugin->get_lang('ApliesTo'),
    get_lang('None'),
    0
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('User'),
    1
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Course'),
    2
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Session'),
    3
);
$form->addSelect(
    'owner_id',
    get_lang('Owner'),
    $userOptions
);
$form->addCheckBox('visibility', $plugin->get_lang('VisibleInCatalog'));
$form->addHidden('id', $serviceId);
$form->addButtonSave(get_lang('Edit'));
$form->setDefaults($formDefaultValues);
if ($form->validate()) {
    $values = $form->getSubmitValues();
    
    $plugin->updateService($values, $serviceId);
    
    header('Location: configuration.php');
}

$templateName = $plugin->get_lang('EditService');
$tpl = new Template($templateName);

$tpl->assign('header', $templateName);
$tpl->assign('content', $form->returnForm());
$tpl->display_one_col_template();