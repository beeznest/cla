<?php
/* For licensing terms, see /license.txt */
/**
 *   @package chamilo.admin
 */
// resetting the course id
$cidReset = true;

// including some necessary files
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$form = new FormValidator('archive_cleanup_form', 'post', '', '', array(), FormValidator::LAYOUT_BOX);
$form->addButtonSend(get_lang('ArchiveDirCleanupProceedButton'));

$message = null;

if ($form->validate()) {
	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$htaccess = @file_get_contents($archive_path.'.htaccess');
	$result = rmdirr($archive_path, true, true);

	\Chamilo\CoreBundle\Composer\ScriptHandler::dumpCssFiles();

	if (!empty($htaccess)) {
		@file_put_contents($archive_path.'/.htaccess', $htaccess);
	}
	if ($result) {
		$message = 'ArchiveDirCleanupSucceeded';
		$type = 'confirmation';
	} else {
		$message = 'ArchiveDirCleanupFailed';
		$type = 'error';
	}

	header('Location: '.api_get_self().'?msg='.$message.'&type='.$type);
	exit;
}

Display::display_header(get_lang('ArchiveDirCleanup'));
Display::display_warning_message(get_lang('ArchiveDirCleanupDescr'));

if (isset($_GET['msg']) && isset($_GET['type'])) {
	if (in_array($_GET['msg'], array('ArchiveDirCleanupSucceeded', 'ArchiveDirCleanupFailed')))
	switch($_GET['type']) {
		case 'error':
			$message = Display::return_message(get_lang($_GET['msg']), 'error');
			break;
		case 'confirmation':
			$message = Display::return_message(get_lang($_GET['msg']), 'confirm');
	}
}

if (!empty($message)) {
    echo $message;
}
$form->display();
Display::display_footer();
