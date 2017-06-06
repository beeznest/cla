<?php
/* For license terms, see /license.txt */

/**
 * Create new Services for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();
$platformAdmins = UserManager::get_all_administrators();
$firstPlatformAdmin = current($platformAdmins);

$jsonFile = file_get_contents('../raw/services.json');
$services = json_decode($jsonFile, true);

foreach ($services as $service) {
    $plugin->storeService([
        'name' => $service['name'],
        'description' => $service['description'],
        'price' => $service['price'],
        'duration_days' => $service['duration_days'],
        'applies_to' => $service['applies_to'],
        'on_complete' => $service['on_complete'],
        'owner_id' => $firstPlatformAdmin['user_id'],
        'visibility' => false,
        'image' => '',
        'video_url' => '',
        'service_information' => ''
    ]);
}

header('Location: configuration.php');
