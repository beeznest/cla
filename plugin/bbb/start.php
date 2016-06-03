<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

require __DIR__ . '/../../vendor/autoload.php';

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$tool_name = get_lang('Videoconference');
$tpl = new Template($tool_name);

$vmIsEnabled = false;
$host = '';
$salt = '';
$isGlobal = isset($_GET['global']) ? true : false;
$bbb = new bbb('', '', $isGlobal);

if ($bbb->pluginEnabled) {
    if ($bbb->isServerRunning()) {

        if (isset($_GET['launch']) && $_GET['launch'] == 1) {

            if (file_exists(__DIR__ . '/config.vm.php')) {
                $config = require __DIR__ . '/config.vm.php';
                $vmIsEnabled = true;
                $host = '';
                $salt = '';

                require __DIR__ . '/lib/vm/AbstractVM.php';
                require __DIR__ . '/lib/vm/VMInterface.php';
                require __DIR__ . '/lib/vm/DigitalOceanVM.php';
                require __DIR__ . '/lib/VM.php';

                $vm = new VM($config);

                if ($vm->isEnabled()) {
                    try {
                        $vm->resizeToMaxLimit();
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                        exit;
                    }
                }
            }

            $meetingParams = array();
            $meetingParams['meeting_name'] = $bbb->getCurrentVideoConferenceName();

            if ($bbb->meetingExists($meetingParams['meeting_name'])) {
                $url = $bbb->joinMeeting($meetingParams['meeting_name']);
                if ($url) {
                    $bbb->redirectToBBB($url);
                } else {
                    $url = $bbb->createMeeting($meetingParams);
                    $bbb->redirectToBBB($url);
                }
            } else {
                if ($bbb->isConferenceManager()) {
                    $url = $bbb->createMeeting($meetingParams);
                    $bbb->redirectToBBB($url);
                } else {
                    $url = $bbb->getListingUrl();
                    $bbb->redirectToBBB($url);
                }
            }
        } else {
            $url = $bbb->getListingUrl();
            header('Location: ' . $url);
            exit;
        }
    } else {
        $message = Display::return_message(get_lang('ServerIsNotRunning'), 'warning');
    }
} else {
    $message = Display::return_message(get_lang('ServerIsNotConfigured'), 'warning');
}
$tpl->assign('message', $message);
$tpl->display_one_col_template();
