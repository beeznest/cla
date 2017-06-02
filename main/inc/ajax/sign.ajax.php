<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'signUp':
        
        $firstname = isset($_POST['firstname']) ? $_POST['firstname'] : null;
        $lastname = isset($_POST['lastname']) ? $_POST['lastname'] : null;
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $pass1 = isset($_POST['pass1']) ? $_POST['pass1'] : null;
        $pass2 = isset($_POST['pass2']) ? $_POST['pass2'] : null;
        
        if ($firstname && $lastname && $username && $pass1 && $pass2) {
            if ($pass1 === $pass2) {
                if ($username !== $pass1) {
                    if (strlen($pass1) > 1) {
                        $user_id = UserManager::create_user(
                            $firstname,
                            $lastname,
                            STUDENT,
                            $username,
                            $username,
                            $pass1,
                            '',
                            api_get_interface_language(),
                            null,
                            null,
                            PLATFORM_AUTH_SOURCE,
                            null,
                            1,
                            0,
                            null,
                            null,
                            true
                        );
                        $redir = api_get_path(WEB_CODE_PATH).'auth/profile.php';
                        echo json_encode(['url' => $redir, 'message' => Display::return_message(get_lang('RegisterSuccess'), 'success')]);
                        break;
                    } else {
                        echo Display::return_message(get_lang('PasswordIsTooShort'), 'error');
                        break;
                    }
                } else {
                    echo Display::return_message(get_lang('YourPasswordCannotBeTheSameAsYourUsername'), 'error');
                    break;
                }
            } else {
                echo Display::return_message(get_lang('PasswordsNeedsSame'), 'error');
                break;
            }
        } else { 
            echo Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error');
            break;
        }

        break;  
    case 'signIn':
    default:
        // In case of signIn ( or LogIn ) it post all the values to handle inside the global.inc.php
        // Nothing to do Here
        break;
}
exit;
