<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                      moderation of posts (approval)
 *                      reply only forums (students cannot create new threads)
 *                      multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message
 *
 * @Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @Copyright Ghent University
 * @Copyright Patrick Cool
 *
 *  @package chamilo.forum
 */

use ChamiloSession as Session;

// Including the global initialization file.
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_FORUM;

// Notification for unauthorized people.
api_protect_course_script(true);

// The section (tabs).
$this_section = SECTION_COURSES;

$nameTools = get_lang('ToolForum');

// Are we in a lp ?
$origin = '';
$origin_string = '';
if (isset($_GET['origin'])) {
    $origin = Security::remove_XSS($_GET['origin']);
    $origin_string = '&origin='.$origin;
}

/* Including necessary files */
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$userid  = api_get_user_id();
$sessionId = api_get_session_id();

/* MAIN DISPLAY SECTION */

$groupId = api_get_group_id();
$my_forum = isset($_GET['forum']) ? $_GET['forum'] : '';
// Note: This has to be validated that it is an existing forum.
$current_forum = get_forum_information($my_forum);

if (empty($current_forum)) {
    api_not_allowed();
}

$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$is_group_tutor = false;

if (!empty($groupId)) {
    //Group info & group category info
    $group_properties = GroupManager::get_group_properties($groupId);
    //User has access in the group?
    $user_has_access_in_group = GroupManager::user_has_access($userid, $groupId, GroupManager::GROUP_TOOL_FORUM);
    $is_group_tutor = GroupManager::is_tutor_of_group(api_get_user_id(), $groupId);

    //Course
    if (
        !api_is_allowed_to_edit(false, true) AND  //is a student
        (($current_forum_category && $current_forum_category['visibility'] == 0) OR
        $current_forum['visibility'] == 0 OR !$user_has_access_in_group)
    ) {
        api_not_allowed(true);
    }
} else {
    //Course
    if (
        !api_is_allowed_to_edit(false, true) AND  //is a student
        (
            ($current_forum_category && $current_forum_category['visibility'] == 0) OR
            $current_forum['visibility'] == 0
        ) //forum category or forum visibility is false
    ) {
        api_not_allowed();
    }
}

/* Header and Breadcrumbs */

$my_search = isset($_GET['search']) ? $_GET['search'] : '';
$my_action = isset($_GET['action']) ? $_GET['action'] : '';

$gradebook = null;
if (isset($_SESSION['gradebook'])){
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

if (!empty($_GET['gidReq'])) {
    $toolgroup = Database::escape_string($_GET['gidReq']);
    Session::write('toolgroup',$toolgroup);
}

$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';

if ($origin == 'group') {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH) . 'group/group.php',
        'name' => get_lang('Groups')
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH) . 'group/group_space.php?' . api_get_cidreq(),
        'name' => get_lang('GroupSpace') . ' ' . $group_properties['name']
    );
    $interbreadcrumb[] = array(
        'url' => '#',
        'name' => get_lang('Forum') . ' ' . Security::remove_XSS($current_forum['forum_title'])
    );
} else {
    $interbreadcrumb[] = array(
        'url' => $forumUrl . 'index.php?search=' . Security::remove_XSS($my_search),
        'name' => get_lang('ForumCategories')
    );
    $interbreadcrumb[] = array(
        'url' => $forumUrl . 'viewforumcategory.php?forumcategory=' . $current_forum_category['cat_id']
            . '&search=' . Security::remove_XSS(urlencode($my_search)),
        'name' => prepare4display($current_forum_category['cat_title'])
    );
    $interbreadcrumb[] = array(
        'url' => '#',
        'name' => Security::remove_XSS($current_forum['forum_title'])
    );
}

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string.
    Display::display_header('');
}

/* Actions */
// Change visibility of a forum or a forum category.
if (
    ($my_action == 'invisible' OR $my_action == 'visible') AND
    isset($_GET['content']) AND
    isset($_GET['id']) AND
    api_is_allowed_to_edit(false, true) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $message = change_visibility($_GET['content'], $_GET['id'], $_GET['action']);
}
// Locking and unlocking.
if (
    ($my_action == 'lock' OR $my_action == 'unlock') AND
    isset($_GET['content']) AND isset($_GET['id']) AND
    api_is_allowed_to_edit(false, true) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $message = change_lock_status($_GET['content'], $_GET['id'], $my_action);
}
// Deleting.
if (
    $my_action == 'delete' AND
    isset($_GET['content']) AND
    isset($_GET['id']) AND
    api_is_allowed_to_edit(false, true) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $locked = api_resource_is_locked_by_gradebook($_GET['id'], LINK_FORUM_THREAD);
    if ($locked == false) {
        $message = deleteForumCategoryThread($_GET['content'], $_GET['id']);

        // Delete link
        $link_info = GradebookUtils::is_resource_in_course_gradebook(
            api_get_course_id(),
            5,
            intval($_GET['id']),
            api_get_session_id()
        );
        $link_id = $link_info['id'];
        if ($link_info !== false) {
            GradebookUtils::remove_resource_from_course_gradebook($link_id);
        }
    }
}
// Moving.
if ($my_action == 'move' && isset($_GET['thread']) &&
    api_is_allowed_to_edit(false, true ) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $message = move_thread_form();
}
// Notification.
if (
    $my_action == 'notify' &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $return_message = set_notification($_GET['content'], $_GET['id']);
    Display::display_confirmation_message($return_message, false);
}

// Student list

if (
    $my_action == 'liststd' &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    (api_is_allowed_to_edit(null, true) || $is_group_tutor)
) {
    $active = null;
    $listType = isset($_GET['list']) ? $_GET['list'] : null;

    switch ($listType) {
        case 'qualify':
            $student_list = get_thread_users_qualify($_GET['id']);
            $nrorow3 = -2;
            $active = 2;
            break;
        case 'notqualify':
            $student_list = get_thread_users_not_qualify($_GET['id']);
            $nrorow3 = -2;
            $active = 3;
            break;
        default:
            $student_list = get_thread_users_details($_GET['id']);
            $nrorow3 = Database::num_rows($student_list);
            $active = 1;
            break;
    }

    $table_list = Display::page_subheader(get_lang('ThreadUsersList') . ': ' . get_name_thread_by_id($_GET['id']));

    if ($nrorow3 > 0 || $nrorow3 == -2) {
        $url = 'cidReq=' . Security::remove_XSS($_GET['cidReq']) .
            '&forum=' . Security::remove_XSS($my_forum) . '&action='
            . Security::remove_XSS($_GET['action']) . '&content='
            . Security::remove_XSS($_GET['content'], STUDENT) . '&id=' . intval($_GET['id']);
        $tabs = array(
            array(
                'content' =>  get_lang('AllStudents'),
                'url' => $forumUrl . 'viewforum.php?' . $url . '&origin=' . $origin . '&list=all'
            ),
            array(
                'content' =>  get_lang('StudentsQualified'),
                'url' => $forumUrl . 'viewforum.php?' . $url . '&origin=' . $origin . '&list=qualify'
            ),
            array(
                'content' =>  get_lang('StudentsNotQualified'),
                'url' => $forumUrl . 'viewforum.php?' . $url . '&origin=' . $origin . '&list=notqualify'
            ),
        );
        $table_list .= Display::tabsOnlyLink($tabs, $active);

        $icon_qualify = 'blog_new.gif';
        $table_list .= '<center><br /><table class="data_table" style="width:50%">';
        // The column headers (TODO: Make this sortable).
        $table_list .= '<tr >';
        $table_list .= '<th height="24">' . get_lang('NamesAndLastNames') . '</th>';

        if ($listType == 'qualify') {
            $table_list .= '<th>' . get_lang('Qualification') . '</th>';
        }
        if (api_is_allowed_to_edit(null, true)) {
            $table_list .= '<th>' . get_lang('Qualify') . '</th>';
        }
        $table_list .= '</tr>';
        $max_qualify = showQualify('2', $userid, $_GET['id']);
        $counter_stdlist = 0;

        if (Database::num_rows($student_list) > 0) {
            while ($row_student_list=Database::fetch_array($student_list)) {
                $userInfo = api_get_user_info($row_student_list['id']);
                if ($counter_stdlist % 2 == 0) {
                    $class_stdlist = 'row_odd';
                } else {
                    $class_stdlist = 'row_even';
                }
                $table_list .= '<tr class="' . $class_stdlist . '"><td>';
                $table_list .= UserManager::getUserProfileLink($userInfo);

                $table_list .= '</td>';
                if ($listType == 'qualify') {
                    $table_list .= '<td>' . $row_student_list['qualify'] . '/' . $max_qualify . '</td>';
                }
                if (api_is_allowed_to_edit(null, true)) {
                    $current_qualify_thread = showQualify(
                        '1',
                        $row_student_list['id'],
                        $_GET['id']
                    );
                    $table_list .= '<td>
                        <a href="' . $forumUrl . 'forumqualify.php?' . api_get_cidreq()
                        . '&forum=' . Security::remove_XSS($my_forum) . '&thread='
                        . Security::remove_XSS($_GET['id']) . '&user=' . $row_student_list['id']
                        . '&user_id=' . $row_student_list['id'] . '&idtextqualify='
                        . $current_qualify_thread . '&origin=' . $origin . '">'
                        . Display::return_icon($icon_qualify, get_lang('Qualify')) . '</a></td></tr>';
                }
                $counter_stdlist++;
            }
        } else {
            if ($listType == 'qualify') {
                $table_list .= '<tr><td colspan="2">' . get_lang('ThereIsNotQualifiedLearners') . '</td></tr>';
            } else {
                $table_list .= '<tr><td colspan="2">' . get_lang('ThereIsNotUnqualifiedLearners') . '</td></tr>';
            }
        }

        $table_list .= '</table></center>';
        $table_list .= '<br />';
    } else {
        $table_list .= Display::return_message(get_lang('NoParticipation'), 'warning');
    }
}

if ($origin == 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

/* Display the action messages */

if (!empty($message)) {
    Display::display_confirmation_message($message);
}

/* Action links */

echo '<div class="actions">';

if ($origin != 'learnpath') {
    if ($origin=='group') {
        echo '<a href="' . api_get_path(WEB_CODE_PATH) . 'group/group_space.php?'
            . api_get_cidreq() . '&gradebook=' . $gradebook . '">'
            . Display::return_icon('back.png', get_lang('BackTo')
            . ' ' . get_lang('Groups'), '', ICON_SIZE_MEDIUM) . '</a>';
    } else {
        echo '<span style="float:right;">'.search_link().'</span>';
        echo '<a href="' . $forumUrl . 'index.php?' . api_get_cidreq() . '">'
            . Display::return_icon('back.png', get_lang('BackToForumOverview'), '', ICON_SIZE_MEDIUM)
            . '</a>';
    }
}

// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (
    api_is_allowed_to_edit(false, true) OR
    ($current_forum['allow_new_threads'] == 1 AND isset($_user['user_id'])) OR
    ($current_forum['allow_new_threads'] == 1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous'] == 1)
) {
    if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) {
        if (!api_is_anonymous() && !api_is_invitee()) {
            if ($my_forum == strval(intval($my_forum))) {
                echo '<a href="' . $forumUrl . 'newthread.php?' . api_get_cidreq() . '&forum='
                    . Security::remove_XSS($my_forum) . $origin_string . '">'
                    . Display::return_icon('new_thread.png', get_lang('NewTopic'), '', ICON_SIZE_MEDIUM)
                    . '</a>';
            } else {
                $my_forum = strval(intval($my_forum));
                echo '<a href="' . $forumUrl . 'newthread.php?' . api_get_cidreq()
                    . '&forum=' . $my_forum . $origin_string . '">'
                    . Display::return_icon('new_thread.png', get_lang('NewTopic'), '', ICON_SIZE_MEDIUM)
                    . '</a>';
            }
        }
    } else {
        echo get_lang('ForumLocked');
    }
}
echo '</div>';


/* Display */
$titleForum = $current_forum['forum_title'];
$descriptionForum = $current_forum['forum_comment'];
$iconForum = Display::return_icon(
    'forum_yellow.png',
    get_lang('Forum'),
    null,
    ICON_SIZE_MEDIUM
);
$html = '';
$html .= '<div class="topic-forum">';
// The current forum
if ($origin != 'learnpath') {
    $html .= Display::tag(
        'h3',
        $iconForum .' '. $titleForum,
        array(
            'class' => 'title-forum')
    );

    if (!empty($descriptionForum)) {
        $html .= Display::tag(
            'p',
            strip_tags($descriptionForum),
            array(
                'class' => 'description',
            )
        );
    }
}

$html .= '</div>';
echo $html;

// Getting al the threads
$threads = get_threads($my_forum);

$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;

$course_id = api_get_course_int_id();

echo '<div class="forum_display">';
if (is_array($threads)) {
    $html = '';
    $count = 1;
    foreach ($threads as $row) {
        // Thread who have no replies yet and the only post is invisible should not be displayed to students.
        if (api_is_allowed_to_edit(false, true) ||
            !($row['thread_replies'] == '0' AND $row['visibility'] == '0')
        ) {

            $my_whatsnew_post_info = null;

            if (isset($whatsnew_post_info[$my_forum][$row['thread_id']])) {
                $my_whatsnew_post_info = $whatsnew_post_info[$my_forum][$row['thread_id']];
            }

            if (is_array($my_whatsnew_post_info) && !empty($my_whatsnew_post_info)) {
                $newPost = ' ' . Display::return_icon('alert.png', get_lang('Forum'), null, ICON_SIZE_SMALL);
            } else {
                $newPost = '';
            }

            if ($row['thread_sticky'] == 1) {
                //$sticky = Display::return_icon('exclamation.gif');
            }

            $name = api_get_person_name($row['firstname'], $row['lastname']);
            $linkPostForum = '<a href="viewthread.php?' . api_get_cidreq() . '&forum=' . Security::remove_XSS($my_forum)
                . "&origin=$origin&thread={$row['thread_id']}$origin_string&search="
                . Security::remove_XSS(urlencode($my_search)) . '">'
                . $row['thread_title'] . '</a>';
            $html = '';
            $html .= '<div class="panel panel-default forum '.($row['thread_sticky']?'sticky':'').'">';
            $html .= '<div class="panel-body">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-6">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-2">';

            // display the author name
            $tab_poster_info = api_get_user_info($row['user_id']);
            $poster_username = sprintf(get_lang('LoginX'), $tab_poster_info['username']);
            $authorName = '';

            if ($origin != 'learnpath') {
                $authorName = display_user_link(
                    $row['user_id'],
                    api_get_person_name($row['firstname'],
                    $row['lastname']),
                    '',
                    $poster_username
                );
            } else {
                $authorName = Display::tag(
                    'span',
                    api_get_person_name(
                        $row['firstname'],
                        $row['lastname']
                    ),
                    array(
                        "title" => api_htmlentities($poster_username, ENT_QUOTES)
                    )
                );
            }

            $html .= '<div class="thumbnail">' . display_user_image($row['user_id'], $name) . '</div>';
            $html .= '</div>';
            $html .= '<div class="col-md-10">';
            $html .= Display::tag(
                'h3',
                $linkPostForum,
                array(
                    'class' => 'title'
                )
            );
            $html .= '<p>'. get_lang('By') .' ' .$authorName.'</p>';
            $html .= '<p>' . api_convert_and_format_date($row['insert_date']) . '</p>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
            $html .= '<div class="col-md-6">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-4">'
                . Display::return_icon('post-forum.png', null, null, ICON_SIZE_SMALL)
                . " {$row['thread_replies']} " . get_lang('Replies') . '<br>';
            $html .=  Display::return_icon(
                    'post-forum.png',
                    null,
                    null,
                    ICON_SIZE_SMALL
                ) . ' ' . $row['thread_views'] . ' ' . get_lang('Views') . '<br>' . $newPost;
            $html .= '</div>';

            $last_post_info = get_last_post_by_thread(
                $row['c_id'],
                $row['thread_id'],
                $row['forum_id'],
                api_is_allowed_to_edit()
            );
            $last_post = null;

            if ($last_post_info) {
                $poster_info = api_get_user_info($last_post_info['poster_id']);
                $post_date = api_convert_and_format_date($last_post_info['post_date']);
                $last_post = $post_date . '<br>' . get_lang('By') . ' ' . display_user_link(
                    $last_post_info['poster_id'],
                    $poster_info['complete_name'],
                    '',
                    $poster_info['username']
                );
            }

            $html .= '<div class="col-md-5">'
                . Display::return_icon('post-item.png', null, null, ICON_SIZE_TINY)
                . ' ' . $last_post;
            $html .= '</div>';

            /*
            if ($row['last_poster_user_id'] == '0') {
                $name = $row['poster_name'];
                $last_poster_username = "";
            } else {
                $name = api_get_person_name($row['last_poster_firstname'], $row['last_poster_lastname']);
                $tab_last_poster_info = api_get_user_info($row['last_poster_user_id']);
                $last_poster_username = sprintf(get_lang('LoginX'), $tab_last_poster_info['username']);
            }
            // If the last post is invisible and it is not the teacher who is looking then we have to find the last visible post of the thread.
            if (($row['visible'] == '1' OR api_is_allowed_to_edit(false, true)) && $origin != 'learnpath') {
                $last_post = $post_date.' '.get_lang('By').' '.display_user_link($row['last_poster_user_id'], $name, '', $last_poster_username);
            } elseif ($origin != 'learnpath') {
                $last_post_sql = "SELECT post.*, user.firstname, user.lastname, user.username FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' AND post.c_id=".api_get_course_int_id()." ORDER BY post_id DESC";
                $last_post_result = Database::query($last_post_sql);
                $last_post_row = Database::fetch_array($last_post_result);
                $name = api_get_person_name($last_post_row['firstname'], $last_post_row['lastname']);
                $last_post_info_username = sprintf(get_lang('LoginX'), $last_post_row['username']);
                $last_post = api_convert_and_format_date($last_post_row['post_date']).' '.get_lang('By').' '.display_user_link($last_post_row['poster_id'], $name, '', $last_post_info_username);
            } else {
                $last_post_sql = "SELECT post.*, user.firstname, user.lastname, user.username FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' AND post.c_id=".api_get_course_int_id()." ORDER BY post_id DESC";
                $last_post_result = Database::query($last_post_sql);
                $last_post_row = Database::fetch_array($last_post_result);
                $last_post_info_username = sprintf(get_lang('LoginX'), $last_post_row['username']);
                $name = api_get_person_name($last_post_row['firstname'], $last_post_row['lastname']);
                $last_post = api_convert_and_format_date($last_post_row['post_date']).' '.get_lang('By').' '.Display::tag('span', $name, array("title"=>api_htmlentities($last_post_info_username, ENT_QUOTES)));
            }*/


            $html .= '<div class="col-md-3">';
            // Get attachment id.
            if (isset($row['post_id'])) {
                $attachment_list = get_attachment($row['post_id']);
            }
            $id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';

            $sql = "SELECT post_id
                    FROM $table_posts
                    WHERE
                        c_id = $course_id AND
                        post_title='" . Database::escape_string($row['thread_title']) . "' AND
                        thread_id = ".$row['thread_id']."
            ";

            $result_post_id = Database::query($sql);
            $row_post_id = Database::fetch_array($result_post_id);
            $iconsEdit = '';
            if ($origin != 'learnpath') {
                if (api_is_allowed_to_edit(false, true) &&
                    !(api_is_course_coach() && $current_forum['session_id'] != $sessionId)
                ) {
                    $iconsEdit .= '<a href="' . $forumUrl . 'editpost.php?' . api_get_cidreq()
                        . '&forum=' . Security::remove_XSS($my_forum) . '&thread='
                        . Security::remove_XSS($row['thread_id']) . '&post=' . $row_post_id['post_id']
                        . '&id_attach=' . $id_attach . '">'
                        . Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL) . '</a>';

                    if (api_resource_is_locked_by_gradebook($row['thread_id'], LINK_FORUM_THREAD)) {
                        $iconsEdit .= Display::return_icon(
                            'delete_na.png',
                            get_lang('ResourceLockedByGradebook'),
                            array(),
                            ICON_SIZE_SMALL
                        );
                    } else {
                        $iconsEdit.= '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&forum='
                            . Security::remove_XSS($my_forum) . '&action=delete&content=thread&id='
                            . $row['thread_id'] . $origin_string
                            . "\" onclick=\"javascript:if(!confirm('"
                            . addslashes(api_htmlentities(get_lang('DeleteCompleteThread'), ENT_QUOTES))
                            . "')) return false;\">"
                            . Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL) . '</a>';
                    }

                    $iconsEdit .= return_visible_invisible_icon(
                        'thread',
                        $row['thread_id'],
                        $row['visibility'],
                        array(
                            'forum' => $my_forum,
                            'origin' => $origin,
                            'gidReq' => $groupId
                        )
                    );
                    $iconsEdit .= return_lock_unlock_icon(
                        'thread',
                        $row['thread_id'],
                        $row['locked'],
                        array(
                            'forum' => $my_forum,
                            'origin' => $origin,
                            'gidReq' => api_get_group_id()
                        )
                    );
                    $iconsEdit .= '<a href="viewforum.php?' . api_get_cidreq() . '&forum='
                        . Security::remove_XSS($my_forum)
                        . '&action=move&thread=' . $row['thread_id'] . $origin_string . '">'
                        . Display::return_icon('move.png', get_lang('MoveThread'), array(), ICON_SIZE_SMALL)
                        . '</a>';
                }
            }
            $iconnotify = 'notification_mail_na.png';
            if (
                is_array(
                    isset($_SESSION['forum_notification']['thread']) ? $_SESSION['forum_notification']['thread'] : null
                )
            ) {
                if (in_array($row['thread_id'], $_SESSION['forum_notification']['thread'])) {
                    $iconnotify = 'notification_mail.png';
                }
            }
            $icon_liststd = 'user.png';
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                $iconsEdit .= '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&forum='
                    . Security::remove_XSS($my_forum)
                    . "&origin=$origin&action=notify&content=thread&id={$row['thread_id']}"
                    . '">' . Display::return_icon($iconnotify, get_lang('NotifyMe')) . '</a>';
            }

            if (api_is_allowed_to_edit(null,true) && $origin != 'learnpath') {
                $iconsEdit .= '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&forum='
                    . Security::remove_XSS($my_forum)
                    . "&origin=$origin&action=liststd&content=thread&id={$row['thread_id']}"
                    . '">' . Display::return_icon($icon_liststd, get_lang('StudentList'), array(), ICON_SIZE_SMALL)
                    . '</a>';
            }
            $html .= $iconsEdit;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            echo $html;
        }
        $count++;
    }
}

echo '</div>';
echo isset($table_list) ? $table_list : '';

/* FOOTER */

if ($origin != 'learnpath') {
    Display::display_footer();
}
