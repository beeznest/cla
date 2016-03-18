<?php
/* For licensing terms, see /license.txt */

/**
 * Include file with functions for the announcements module.
 * @author jmontoya
 * @package chamilo.announcements
 * @todo use OOP
 */
class AnnouncementManager
{
    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * @return array
     */
    public static function get_tags()
    {
        return array(
            '((user_name))',
            '((user_firstname))',
            '((user_lastname))',
            '((teacher_name))',
            '((teacher_email))',
            '((course_title))',
            '((course_link))',
            '((official_code))',
        );
    }

    /**
     * @param int       $userId
     * @param string    $content
     * @param string    $course_code
     * @param int       $session_id
     *
     * @return mixed
     */
    public static function parse_content($userId, $content, $course_code, $session_id = 0)
    {
        $readerInfo = api_get_user_info($userId);
        $courseInfo = api_get_course_info($course_code);
        $teacher_list = CourseManager::get_teacher_list_from_course_code($courseInfo['code']);

        $teacher_name = '';
        if (!empty($teacher_list)) {
            foreach ($teacher_list as $teacher_data) {
                $teacher_name = api_get_person_name($teacher_data['firstname'], $teacher_data['lastname']);
                $teacher_email = $teacher_data['email'];
                break;
            }
        }

        $courseLink = api_get_course_url($course_code, $session_id);

        $data['user_name'] = $readerInfo['username'];
        $data['user_firstname'] = $readerInfo['firstname'];
        $data['user_lastname'] = $readerInfo['lastname'];
        $data['teacher_name'] = $teacher_name;
        $data['teacher_email'] = $teacher_email;
        $data['course_title'] = $courseInfo['name'];
        $data['course_link'] = Display::url($courseLink, $courseLink);
        $data['official_code'] = $readerInfo['official_code'];

        $content = str_replace(self::get_tags(), $data, $content);

        return $content;
    }

    /**
     * Gets all announcements from a course
     * @param	array $course_info
     * @param	int $session_id
     * @return	array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_course($course_info, $session_id = 0)
    {
        $session_id = intval($session_id);
        $course_id = $course_info['real_id'];

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT announcement.id, announcement.title, announcement.content
				FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
				WHERE
				    announcement.id = toolitemproperties.ref AND
                    toolitemproperties.tool='announcement' AND
                    announcement.session_id  = '$session_id' AND
                    announcement.c_id = $course_id AND
                    toolitemproperties.c_id = $course_id
				ORDER BY display_order DESC";
        $rs = Database::query($sql);
        $num_rows = Database::num_rows($rs);
        if ($num_rows > 0) {
            $list = array();
            while ($row = Database::fetch_array($rs)) {
                $list[] = $row;
            }

            return $list;
        }

        return false;
    }

    /**
     * This functions switches the visibility a course resource
     * using the visibility field in 'item_property'
     * @param    array	$_course
     * @param    int     $id ID of the element of the corresponding type
     * @return   bool    False on failure, True on success
     */
    public static function change_visibility_announcement($_course, $id)
    {
        $session_id = api_get_session_id();
        $item_visibility = api_get_item_visibility(
            $_course,
            TOOL_ANNOUNCEMENT,
            $id,
            $session_id
        );
        if ($item_visibility == '1') {
            api_item_property_update(
                $_course,
                TOOL_ANNOUNCEMENT,
                $id,
                'invisible',
                api_get_user_id()
            );
        } else {
            api_item_property_update(
                $_course,
                TOOL_ANNOUNCEMENT,
                $id,
                'visible',
                api_get_user_id()
            );
        }

        return true;
    }

    /**
     * Deletes an announcement
     * @param array $_course the course array
     * @param int 	$id the announcement id
     */
    public static function delete_announcement($_course, $id)
    {
        api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'delete', api_get_user_id());
    }

    /**
     * Deletes all announcements by course
     * @param array $_course the course array
     */
    public static function delete_all_announcements($_course)
    {
        $announcements = self::get_all_annoucement_by_course($_course, api_get_session_id());
        if (!empty($announcements)) {
            foreach ($announcements as $annon) {
                api_item_property_update(
                    $_course,
                    TOOL_ANNOUNCEMENT,
                    $annon['id'],
                    'delete',
                    api_get_user_id()
                );
            }
        }
    }

    /**
     * Displays one specific announcement
     * @param int $announcement_id, the id of the announcement you want to display
     */
    public static function display_announcement($announcement_id)
    {
        if ($announcement_id != strval(intval($announcement_id))) {
            return null;
        }

        global $charset;
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $course_id = api_get_course_int_id();

        if (api_is_allowed_to_edit(false, true) || (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
            $sql = "SELECT announcement.*, toolitemproperties.*
                    FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
                    WHERE
                        announcement.id = toolitemproperties.ref AND
                        announcement.id = '$announcement_id' AND
                        toolitemproperties.tool='announcement' AND
                        announcement.c_id = $course_id AND
                        toolitemproperties.c_id = $course_id
                    ORDER BY display_order DESC";
        } else {
            $group_list = GroupManager::get_group_ids($course_id, api_get_user_id());
            if (empty($group_list)) {
                $group_list[] = 0;
            }
            if (api_get_user_id() != 0) {
                $sql = "SELECT announcement.*, toolitemproperties.*
                        FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
                        WHERE
                            announcement.id = toolitemproperties.ref AND
                            announcement.id = '$announcement_id' AND
                            toolitemproperties.tool='announcement' AND
                            (
                                toolitemproperties.to_user_id='" . api_get_user_id() . "' OR
                                toolitemproperties.to_group_id IN ('0', '" . implode("', '", $group_list) . "') OR
                                toolitemproperties.to_group_id IS NULL
                            ) AND
                            toolitemproperties.visibility='1' AND
                            announcement.c_id = $course_id AND
                            toolitemproperties.c_id = $course_id
                        ORDER BY display_order DESC";
            } else {
                $sql = "SELECT announcement.*, toolitemproperties.*
                        FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
                        WHERE
                            announcement.id = toolitemproperties.ref AND
                            announcement.id = '$announcement_id' AND
                            toolitemproperties.tool='announcement' AND
                            (toolitemproperties.to_group_id='0' OR toolitemproperties.to_group_id IS NULL) AND
                            toolitemproperties.visibility='1' AND
                            announcement.c_id = $course_id AND
                            toolitemproperties.c_id = $course_id
                        ";
            }
        }

        $sql_result = Database::query($sql);
        $html = null;
        if (Database::num_rows($sql_result) > 0) {
            $result = Database::fetch_array($sql_result, 'ASSOC');
            $title = $result['title'];
            $content = $result['content'];
            $html .= "<table height=\"100\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" class=\"data_table\">";
            $html .= "<tr><td><h2>" . $title . "</h2></td></tr>";

            if (api_is_allowed_to_edit(false, true) ||
                (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
            ) {
                $modify_icons = "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&action=modify&id=" . $announcement_id . "\">" .
                    Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL) . "</a>";
                if ($result['visibility'] == 1) {
                    $image_visibility = "visible";
                    $alt_visibility = get_lang('Hide');
                } else {
                    $image_visibility = "invisible";
                    $alt_visibility = get_lang('Visible');
                }
                global $stok;

                $modify_icons .= "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&origin=" . (!empty($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '') . "&action=showhide&id=" . $announcement_id . "&sec_token=" . $stok . "\">" .
                    Display::return_icon($image_visibility . '.png', $alt_visibility, '', ICON_SIZE_SMALL) . "</a>";

                if (api_is_allowed_to_edit(false, true)) {
                    $modify_icons .= "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&action=delete&id=" . $announcement_id . "&sec_token=" . $stok . "\" onclick=\"javascript:if(!confirm('" . addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)) . "')) return false;\">" .
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL) .
                        "</a>";
                }
                $html .= "<tr><th style='text-align:right'>$modify_icons</th></tr>";
            }

            $content = self::parse_content($result['to_user_id'], $content, api_get_course_id(), api_get_session_id());

            $html .= "<tr><td>$content</td></tr>";
            $html .= "<tr><td class=\"announcements_datum\">" . get_lang('LastUpdateDate') . " : " . api_convert_and_format_date($result['insert_date'], DATE_TIME_FORMAT_LONG) . "</td></tr>";

            // User or group icon
            $sent_to_icon = '';
            if ($result['to_group_id'] !== '0' and $result['to_group_id'] !== 'NULL') {
                $sent_to_icon = Display::return_icon('group.gif', get_lang('AnnounceSentToUserSelection'));
            }
            if (api_is_allowed_to_edit(false, true)) {
                $sent_to = self::sent_to('announcement', $announcement_id);
                $sent_to_form = self::sent_to_form($sent_to);
                $html .= Display::tag(
                    'td',
                    get_lang('SentTo') . ' : ' . $sent_to_form,
                    array('class' => 'announcements_datum')
                );
            }
            $attachment_list = self::get_attachment($announcement_id);

            if (count($attachment_list) > 0) {
                $html .= "<tr><td>";
                $realname = $attachment_list['path'];
                $user_filename = $attachment_list['filename'];
                $full_file_name = 'download.php?'.api_get_cidreq().'&file=' . $realname;
                $html .= '<br/>';
                $html .= Display::return_icon('attachment.gif', get_lang('Attachment'));
                $html .= '<a href="' . $full_file_name . ' "> ' . $user_filename . ' </a>';
                $html .= ' - <span class="forum_attach_comment" >' . $attachment_list['comment'] . '</span>';
                if (api_is_allowed_to_edit(false, true)) {
                    $html .= Display::url(
                        Display::return_icon('delete.png', get_lang('Delete'), '', 16),
                        api_get_self() . "?" . api_get_cidreq() . "&action=delete_attachment&id_attach=" . $attachment_list['id'] . "&sec_token=" . $stok
                    );
                }
                $html .= '</td></tr>';
            }
            $html .= "</table>";

            return $html;
        }

        return null;
    }

    /**
     * @return int
     */
    public static function get_last_announcement_order()
    {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $course_id = api_get_course_int_id();
        $sql = "SELECT MAX(display_order)
                FROM $tbl_announcement
                WHERE c_id = $course_id ";
        $res_max = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res_max)) {
            $row_max = Database::fetch_array($res_max);
            $order = intval($row_max[0])+1;
        }

        return $order;
    }

    /**
     * Store an announcement in the database (including its attached file if any)
     * @param string    Announcement title (pure text)
     * @param string    Content of the announcement (can be HTML)
     * @param int       Display order in the list of announcements
     * @param array     Array of users and groups to send the announcement to
     * @param array	    uploaded file $_FILES
     * @param string    Comment describing the attachment
     * @param bool  $sendToUsersInSession
     * @return int      false on failure, ID of the announcement on success
     */
    public static function add_announcement(
        $emailTitle,
        $newContent,
        $sentTo,
        $file = array(),
        $file_comment = null,
        $end_date = null,
        $sendToUsersInSession = false
    ) {
        $_course = api_get_course_info();
        $course_id = api_get_course_int_id();

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        if (empty($end_date)) {
            $end_date = api_get_utc_datetime();
        }

        $order = self::get_last_announcement_order();

        // store in the table announcement
        $params = array(
            'c_id' => $course_id,
            'content' => $newContent,
            'title' => $emailTitle,
            'end_date' => $end_date,
            'display_order' => $order,
            'session_id' => api_get_session_id()
        );

        $last_id = Database::insert($tbl_announcement, $params);

        if (empty($last_id)) {
            return false;
        } else {
            $sql = "UPDATE $tbl_announcement SET id = iid WHERE iid = $last_id";
            Database::query($sql);

            if (!empty($file)) {
                self::add_announcement_attachment_file(
                    $last_id,
                    $file_comment,
                    $_FILES['user_upload']
                );
            }

            // store in item_property (first the groups, then the users
            if (empty($sentTo) || !empty($sentTo) &&
                isset($sentTo[0]) && $sentTo[0] == 'everyone'
            ) {
                // The message is sent to EVERYONE, so we set the group to 0
                api_item_property_update(
                    $_course,
                    TOOL_ANNOUNCEMENT,
                    $last_id,
                    "AnnouncementAdded",
                    api_get_user_id(),
                    '0'
                );
            } else {
                $send_to = CourseManager::separateUsersGroups($sentTo);

                // Storing the selected groups
                if (is_array($send_to['groups']) && !empty($send_to['groups'])) {
                    foreach ($send_to['groups'] as $group) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            "AnnouncementAdded",
                            api_get_user_id(),
                            $group
                        );
                    }
                }

                // Storing the selected users
                if (is_array($send_to['users'])) {
                    foreach ($send_to['users'] as $user) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            "AnnouncementAdded",
                            api_get_user_id(),
                            '',
                            $user
                        );
                    }
                }
            }

            if ($sendToUsersInSession) {
                self::addAnnouncementToAllUsersInSessions($last_id);
            }

            return $last_id;
        }
    }

    /**
     * @param $emailTitle
     * @param $newContent
     * @param $to
     * @param $to_users
     * @param array $file
     * @param string $file_comment
     * @param bool $sendToUsersInSession
     *
     * @return bool|int
     */
    public static function add_group_announcement(
        $emailTitle,
        $newContent,
        $to,
        $to_users,
        $file = array(),
        $file_comment = '',
        $sendToUsersInSession = false
    ) {
        $_course = api_get_course_info();

        // Database definitions
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $order = self::get_last_announcement_order();

        $now = api_get_utc_datetime();
        $course_id = api_get_course_int_id();

        // store in the table announcement
        $params = [
            'c_id' => $course_id,
            'content' => $newContent,
            'title' => $emailTitle,
            'end_date' => $now,
            'display_order' => $order,
            'session_id' => api_get_session_id(),
        ];

        $last_id = Database::insert($tbl_announcement, $params);

        // Store the attach file
        if ($last_id) {
            $sql = "UPDATE $tbl_announcement SET id = iid WHERE iid = $last_id";
            Database::query($sql);

            if (!empty($file)) {
                self::add_announcement_attachment_file(
                    $last_id,
                    $file_comment,
                    $file
                );
            }

            // Store in item_property (first the groups, then the users

            if (!isset($to_users)) {
                // when no user is selected we send it to everyone
                $send_to = CourseManager::separateUsersGroups($to);
                // storing the selected groups
                if (is_array($send_to['groups'])) {
                    foreach ($send_to['groups'] as $group) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            "AnnouncementAdded",
                            api_get_user_id(),
                            $group
                        );
                    }
                }
            } else {
                // the message is sent to everyone, so we set the group to 0
                // storing the selected users
                if (is_array($to_users)) {
                    foreach ($to_users as $user) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            "AnnouncementAdded",
                            api_get_user_id(),
                            '',
                            $user
                        );
                    }
                }
            }

            if ($sendToUsersInSession) {
                self::addAnnouncementToAllUsersInSessions($last_id);
            }
        }
        return $last_id;
    }

    /**
     * This function stores the announcement item in the announcement table
     * and updates the item_property table
     *
     * @param int 	id of the announcement
     * @param string email
     * @param string content
     * @param array 	users that will receive the announcement
     * @param mixed 	attachment
     * @param string file comment
     */
    public static function edit_announcement(
        $id,
        $emailTitle,
        $newContent,
        $to,
        $file = array(),
        $file_comment = '',
        $sendToUsersInSession = false
    ) {
        $_course = api_get_course_info();
        $course_id = api_get_course_int_id();
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $id = intval($id);

        $params = [
            'title' => $emailTitle,
            'content' => $newContent
        ];

        Database::update(
            $tbl_announcement,
            $params,
            ['c_id = ? AND id = ?' => [$course_id, $id]]
        );

        // save attachment file
        $row_attach = self::get_attachment($id);

        $id_attach = 0;
        if ($row_attach) {
            $id_attach = intval($row_attach['id']);
        }

        if (!empty($file)) {
            if (empty($id_attach)) {
                self::add_announcement_attachment_file($id, $file_comment, $file);
            } else {
                self::edit_announcement_attachment_file($id_attach, $file, $file_comment);
            }
        }

        // we remove everything from item_property for this
        $sql = "DELETE FROM $tbl_item_property
                WHERE c_id = $course_id AND ref='$id' AND tool='announcement'";
        Database::query($sql);

        if ($sendToUsersInSession) {
            self::addAnnouncementToAllUsersInSessions($id);
        }

        // store in item_property (first the groups, then the users

        if (!is_null($to)) {
            // !is_null($to): when no user is selected we send it to everyone

            $send_to = CourseManager::separateUsersGroups($to);

            // storing the selected groups
            if (is_array($send_to['groups'])) {
                foreach ($send_to['groups'] as $group) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $id,
                        "AnnouncementUpdated",
                        api_get_user_id(),
                        $group
                    );
                }
            }

            // storing the selected users
            if (is_array($send_to['users'])) {
                foreach ($send_to['users'] as $user) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $id,
                        "AnnouncementUpdated",
                        api_get_user_id(),
                        0,
                        $user
                    );
                }
            }
        } else {
            // the message is sent to everyone, so we set the group to 0
            api_item_property_update(
                $_course,
                TOOL_ANNOUNCEMENT,
                $id,
                "AnnouncementUpdated",
                api_get_user_id(),
                '0'
            );
        }
    }

    /**
     * @param int $announcementId
     */
    public static function addAnnouncementToAllUsersInSessions($announcementId)
    {
        $courseCode = api_get_course_id();
        $_course = api_get_course_info();

        $sessionList = SessionManager::get_session_by_course(api_get_course_int_id());

        if (!empty($sessionList)) {
            foreach ($sessionList as $sessionInfo) {
                $sessionId = $sessionInfo['id'];
                $userList = CourseManager::get_user_list_from_course_code(
                    $courseCode,
                    $sessionId
                );

                if (!empty($userList)) {
                    foreach ($userList as $user) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $announcementId,
                            "AnnouncementUpdated",
                            api_get_user_id(),
                            0,
                            $user['user_id'],
                            0,
                            0,
                            $sessionId
                        );
                    }
                }
            }
        }
    }

    /**
     * @param int $insert_id
     * @return bool
     */
    public static function update_mail_sent($insert_id)
    {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        if ($insert_id != strval(intval($insert_id))) {
            return false;
        }
        $insert_id = intval($insert_id);
        $course_id = api_get_course_int_id();
        // store the modifications in the table tbl_annoucement
        $sql = "UPDATE $tbl_announcement SET email_sent='1'
                WHERE c_id = $course_id AND id = $insert_id";
        Database::query($sql);
    }

    /**
     * Gets all announcements from a user by course
     * @param	string course db
     * @param	int user id
     * @return	array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_user_course($course_code, $user_id)
    {
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        if (empty($user_id)) {
            return false;
        }
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        if (!empty($user_id) && is_numeric($user_id)) {
            $user_id = intval($user_id);
            $sql = "SELECT DISTINCT announcement.title, announcement.content
					FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
					WHERE
						announcement.c_id = $course_id AND
						toolitemproperties.c_id = $course_id AND
						announcement.id = toolitemproperties.ref AND
						toolitemproperties.tool='announcement' AND
						(
						  toolitemproperties.insert_user_id='$user_id' AND
						  (toolitemproperties.to_group_id='0' OR toolitemproperties.to_group_id IS NULL)
						)
						AND toolitemproperties.visibility='1'
						AND announcement.session_id  = 0
					ORDER BY display_order DESC";
            $rs = Database::query($sql);
            $num_rows = Database::num_rows($rs);
            $content = '';
            $i = 0;
            $result = array();
            if ($num_rows > 0) {
                while ($myrow = Database::fetch_array($rs)) {
                    $content.= '<strong>' . $myrow['title'] . '</strong><br /><br />';
                    $content.= $myrow['content'];
                    $i++;
                }
                $result['content'] = $content;
                $result['count'] = $i;

                return $result;
            }

            return false;
        }

        return false;
    }

    /**
     * This function shows the form for sending a message to a specific group or user.
     * @param $to_already_selected
     */
    public static function show_to_form($to_already_selected)
    {
        $userList = self::get_course_users();
        $groupList = self::get_course_groups();

        if ($to_already_selected == '' || $to_already_selected == 'everyone') {
            $to_already_selected = array();
        }

        echo "<table id=\"recipient_list\" >";
        echo '<tr>';
        echo '<td>';
        echo '<label><input type="checkbox" id="send_to_all_users">'.get_lang('SendToAllUsers') . "</label>";
        echo "</td>";
        echo '</tr>';
        echo '<tr>';


        // the form containing all the groups and all the users of the course
        echo '<td>';
        echo "<strong>" . get_lang('Users') . "</strong><br />";

        self::construct_not_selected_select_form($groupList, $userList, $to_already_selected);
        echo "</td>";

        // the buttons for adding or removing groups/users
        echo '<td valign="middle">';
        echo '<button class="btn btn-default" type="button" onClick="javascript: move(this.form.elements[1], this.form.elements[4])" onClick="javascript: move(this.form.elements[1], this.form.elements[4])"><em class="fa fa-arrow-right"></em></button>';
        echo '<br /> <br />';
        echo '<button class="btn btn-default" type="button" onClick="javascript: move(this.form.elements[4], this.form.elements[1])" onClick="javascript: move(this.form.elements[4], this.form.elements[1])"><em class="fa fa-arrow-left"></em></button>';
        echo "</td>";

        echo "<td>";

        // the form containing the selected groups and users
        echo "<strong>" . get_lang('DestinationUsers') . "</strong><br />";
        self::construct_selected_select_form($groupList, $userList, $to_already_selected);
        echo "</td>";
        echo "</tr>";
        echo "</table>";
    }

    /**
     * this function shows the form for sending a message to a specific group or user.
     */
    public static function show_to_form_group($group_id)
    {
        echo "<table id=\"recipient_list\" >";
        echo "<tr>";
        echo "<td>";
        echo "<select id=\"not_selected_form\" name=\"not_selected_form[]\" size=5 style=\"width:200px\" multiple>";
        $group_users = GroupManager::getStudentsAndTutors($group_id);
        foreach ($group_users as $user) {
            echo '<option value="' . $user['user_id'] . '" title="' . sprintf(get_lang('LoginX'), $user['username']) . '" >' .
                api_get_person_name($user['firstname'], $user['lastname']) .
                '</option>';
        }
        echo '</select>';
        echo "</td>";

        // the buttons for adding or removing groups/users
        echo "<td valign=\"middle\">";
        echo '<button class="btn btn-default" type="button" onClick="javascript: move(this.form.elements[1], this.form.elements[4])" onClick="javascript: move(this.form.elements[1], this.form.elements[4])"><em class="fa fa-arrow-right"></em></button>';
        echo '<br /> <br />';
        echo '<button class="btn btn-default" type="button" onClick="javascript: move(this.form.elements[4], this.form.elements[1])" onClick="javascript: move(this.form.elements[4], this.form.elements[1])"><em class="fa fa-arrow-left"></em></button>';
        echo "</td>";
        echo "<td>";

        echo "<select id=\"selectedform\" name=\"selectedform[]\" size=5 style=\"width:200px\" multiple>";
        echo '</select>';

        echo "</td>";
        echo "</tr>";
        echo "</table>";
    }

    /**
     * Shows the form for sending a message to a specific group or user.
     * @param array $groupList
     * @param array $userList
     * @param array $to_already_selected
     */
    public static function construct_not_selected_select_form(
        $groupList = array(),
        $userList = array(),
        $to_already_selected = array()
    ) {
        echo '<select id="not_selected_form" name="not_selected_form[]" size="7" class="form-control" multiple>';
        // adding the groups to the select form
        if (!empty($groupList)) {
            foreach ($groupList as $this_group) {
                if (is_array($to_already_selected)) {
                    if (!in_array("GROUP:" . $this_group['id'], $to_already_selected)) {
                        // $to_already_selected is the array containing the groups (and users) that are already selected
                        $user_label = ($this_group['userNb'] > 0) ? get_lang('Users') : get_lang('LowerCaseUser') ;
                        $user_disabled = ($this_group['userNb'] > 0) ? "" : "disabled=disabled" ;
                        echo "<option $user_disabled value=\"GROUP:" . $this_group['id'] . "\">",
                        "G: ", $this_group['name'], " - " . $this_group['userNb'] . " " . $user_label .
                            "</option>";
                    }
                }
            }
            // a divider
            echo "<option value=\"\">---------------------------------------------------------</option>";
        }

        // adding the individual users to the select form

        if (!empty($userList)) {
            foreach ($userList as $user) {
                if (is_array($to_already_selected)) {
                    if (!in_array("USER:" . $user['user_id'], $to_already_selected)) {
                        // $to_already_selected is the array containing the users (and groups) that are already selected
                        echo "<option value=\"USER:" . $user['user_id'] . "\" title='" . sprintf(get_lang('LoginX'), $user['username']) . "'>",
                        "", api_get_person_name($user['firstname'], $user['lastname']),
                        "</option>";

                        if (isset($user['drh_list']) && !empty($user['drh_list'])) {
                            foreach ($user['drh_list'] as $drh) {
                                echo "<option value=\"USER:" . $drh['user_id'] . "\" title='" . sprintf(get_lang('LoginX'), $drh['username']) . "'>&nbsp;&nbsp;&nbsp;&nbsp;",
                                "", api_get_person_name($drh['firstname'], $drh['lastname']),
                                "</option>";
                            }
                        }
                    }
                }
            }
        }
        echo "</select>";
    }

    /**
     * this function shows the form for sending a message to a specific group or user.
     */
    /**
     * @param null $groupList
     * @param null $userList
     * @param $to_already_selected
     */
    public static function construct_selected_select_form($groupList = null, $userList = null, $to_already_selected = array())
    {
        // we load all the groups and all the users into a reference array that we use to search the name of the group / user
        $ref_array_groups = self::get_course_groups();
        $ref_array_users = self::get_course_users();

        // we construct the form of the already selected groups / users
        echo '<select id="selectedform" name="selectedform[]" size="7" multiple class="form-control">';
        if (is_array($to_already_selected)) {
            foreach ($to_already_selected as $groupuser) {
                list($type, $id) = explode(":", $groupuser);
                if ($type == "GROUP") {
                    echo "<option value=\"" . $groupuser . "\">G: " . $ref_array_groups[$id]['name'] . "</option>";
                } else {
                    foreach ($ref_array_users as $key => $value) {
                        if ($value['user_id'] == $id) {
                            echo "<option value=\"" . $groupuser . "\" title='" . sprintf(get_lang('LoginX'), $value['username']) . "'>" .
                                api_get_person_name($value['firstname'], $value['lastname']) . "</option>";

                            if (isset($value['drh_list']) && !empty($value['drh_list'])) {
                                foreach ($value['drh_list'] as $drh) {
                                    echo "<option value=\"USER:" . $drh['user_id'] . "\" title='" . sprintf(get_lang('LoginX'), $drh['username']) . "'>&nbsp;&nbsp;&nbsp;&nbsp;",
                                    "", api_get_person_name($drh['firstname'], $drh['lastname']),
                                    "</option>";
                                }
                            }
                            break;
                        }
                    }
                }
            }
        } else {
            if ($to_already_selected == 'everyone') {
                // adding the groups to the select form
                if (is_array($ref_array_groups)) {
                    foreach ($ref_array_groups as $this_group) {
                        //api_display_normal_message("group " . $thisGroup[id] . $thisGroup[name]);
                        if (!is_array($to_already_selected) || !in_array("GROUP:" . $this_group['id'], $to_already_selected)) { // $to_already_selected is the array containing the groups (and users) that are already selected
                            echo "<option value=\"GROUP:" . $this_group['id'] . "\">",
                            "G: ", $this_group['name'], " &ndash; " . $this_group['userNb'] . " " . get_lang('Users') .
                                "</option>";
                        }
                    }
                }
                // adding the individual users to the select form
                foreach ($ref_array_users as $this_user) {
                    if (!is_array($to_already_selected) || !in_array("USER:" . $this_user['user_id'], $to_already_selected)) { // $to_already_selected is the array containing the users (and groups) that are already selected
                        echo "<option value=\"USER:", $this_user['user_id'], "\"  title='" . sprintf(get_lang('LoginX'), $this_user['username']) . "'>",
                        "", api_get_person_name($this_user['firstname'], $this_user['lastname']),
                        "</option>";
                    }
                }
            }
        }
        echo "</select>";
    }

    /**
     * Returns announcement info from its id
     *
     * @param int $course_id
     * @param int $annoucement_id
     * @return array
     */
    public static function get_by_id($course_id, $annoucement_id)
    {
        $annoucement_id = intval($annoucement_id);
        $course_id = $course_id ? intval($course_id) : api_get_course_int_id();

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT announcement.id, announcement.title, announcement.content
               FROM $tbl_announcement announcement
               INNER JOIN $tbl_item_property toolitemproperties
               ON
                    announcement.id = toolitemproperties.ref AND
                    announcement.c_id = $course_id AND
                    toolitemproperties.c_id = $course_id
               WHERE
                toolitemproperties.tool='announcement' AND
                announcement.id = $annoucement_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::fetch_array($result);
        }
        return array();
    }

    /**
     * this function gets all the users of the course,
     * including users from linked courses
     * @deprecate use CourseManager class
     */
    public static function get_course_users()
    {
        //this would return only the users from real courses:
        $session_id = api_get_session_id();
        if ($session_id != 0) {
            $userList = CourseManager::get_real_and_linked_user_list(
                api_get_course_id(),
                true,
                $session_id,
                true
            );
        } else {
            $userList = CourseManager::get_real_and_linked_user_list(
                api_get_course_id(),
                false,
                0,
                true
            );
        }

        return $userList;
    }

    /**
     * this function gets all the groups of the course,
     * not including linked courses
     */
    public static function get_course_groups()
    {
        $session_id = api_get_session_id();
        if ($session_id != 0) {
            $new_group_list = CourseManager::get_group_list_of_course(
                api_get_course_id(),
                $session_id,
                1
            );
        } else {
            $new_group_list = CourseManager::get_group_list_of_course(
                api_get_course_id(),
                0,
                1
            );
        }
        return $new_group_list;
    }

    /**
     * This tools loads all the users and all the groups who have received
     * a specific item (in this case an announcement item)
     */
    public static function load_edit_users($tool, $id)
    {
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tool = Database::escape_string($tool);
        $id = intval($id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $tbl_item_property
                WHERE c_id = $course_id AND tool='$tool' AND ref = $id";
        $result = Database::query($sql);
        $to = array();
        while ($row = Database::fetch_array($result)) {
            $to_group = $row['to_group_id'];
            switch ($to_group) {
                // it was send to one specific user
                case null:
                    $to[] = "USER:" . $row['to_user_id'];
                    break;
                // it was sent to everyone
                case 0:
                    return "everyone";
                    break;
                default:
                    $to[] = "GROUP:" . $row['to_group_id'];
            }
        }
        return $to;
    }

    /**
     * returns the javascript for setting a filter
     * this goes into the $htmlHeadXtra[] array
     */
    public static function user_group_filter_javascript()
    {
        return "<script language=\"JavaScript\" type=\"text/JavaScript\">
		<!--
		function jumpMenu(targ,selObj,restore)
		{
		  eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
		  if (restore) selObj.selectedIndex=0;
		}
		//-->
		</script>";
    }

    /**
     * constructs the form to display all the groups and users the message has been sent to
     * input: 	$sent_to_array is a 2 dimensional array containing the groups and the users
     * 			the first level is a distinction between groups and users:
     * 			$sent_to_array['groups'] * and $sent_to_array['users']
     * 			$sent_to_array['groups'] (resp. $sent_to_array['users']) is also an array
     * 			containing all the id's of the groups (resp. users) who have received this message.
     * @author Patrick Cool <patrick.cool@>
     */
    public static function sent_to_form($sent_to_array)
    {
        // we find all the names of the groups
        $group_names = self::get_course_groups();

        // we count the number of users and the number of groups
        if (isset($sent_to_array['users'])) {
            $number_users = count($sent_to_array['users']);
        } else {
            $number_users = 0;
        }
        if (isset($sent_to_array['groups'])) {
            $number_groups = count($sent_to_array['groups']);
        } else {
            $number_groups = 0;
        }
        $total_numbers = $number_users + $number_groups;

        // starting the form if there is more than one user/group
        $output = array();
        if ($total_numbers > 1) {
            //$output.="<option>".get_lang("SentTo")."</option>";
            // outputting the name of the groups
            if (is_array($sent_to_array['groups'])) {
                foreach ($sent_to_array['groups'] as $group_id) {
                    $output[] = $group_names[$group_id]['name'];
                }
            }

            if (isset($sent_to_array['users'])) {
                if (is_array($sent_to_array['users'])) {
                    foreach ($sent_to_array['users'] as $user_id) {
                        $user_info = api_get_user_info($user_id);
                        $output[] = $user_info['complete_name_with_username'];
                    }
                }
            }
        } else {
            // there is only one user/group
            if (isset($sent_to_array['users']) and is_array($sent_to_array['users'])) {
                $user_info = api_get_user_info($sent_to_array['users'][0]);
                $output[] = api_get_person_name($user_info['firstname'], $user_info['lastname']);
            }
            if (isset($sent_to_array['groups']) and
                is_array($sent_to_array['groups']) and
                isset($sent_to_array['groups'][0]) and
                $sent_to_array['groups'][0] !== 0
            ) {
                $group_id = $sent_to_array['groups'][0];
                $output[] = "&nbsp;" . $group_names[$group_id]['name'];
            }
            if (empty($sent_to_array['groups']) and empty($sent_to_array['users'])) {
                $output[] = "&nbsp;" . get_lang('Everybody');
            }
        }

        if (!empty($output)) {
            $output = array_filter($output);
            if (count($output) > 0) {
                $output = implode(', ', $output);
            }
            return $output;
        }
    }



    /**
     * Returns all the users and all the groups a specific announcement item
     * has been sent to
     * @param    string  The tool (announcement, agenda, ...)
     * @param    int     ID of the element of the corresponding type
     * @return   array   Array of users and groups to whom the element has been sent
     */
    public static function sent_to($tool, $id)
    {
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $tool = Database::escape_string($tool);
        $id = intval($id);

        $sent_to_group = array();
        $sent_to = array();
        $course_id = api_get_course_int_id();

        $sql = "SELECT to_group_id, to_user_id
                FROM $tbl_item_property
                WHERE c_id = $course_id AND tool = '$tool' AND ref=" . $id;
        $result = Database::query($sql);

        while ($row = Database::fetch_array($result)) {
            // if to_group_id is null then it is sent to a specific user
            // if to_group_id = 0 then it is sent to everybody
            if ($row['to_group_id'] != 0) {
                $sent_to_group[] = $row['to_group_id'];
            }
            // if to_user_id <> 0 then it is sent to a specific user
            if ($row['to_user_id'] <> 0) {
                $sent_to_user[] = $row['to_user_id'];
            }
        }

        if (isset($sent_to_group)) {
            $sent_to['groups'] = $sent_to_group;
        }

        if (isset($sent_to_user)) {
            $sent_to['users'] = $sent_to_user;
        }

        return $sent_to;
    }

    /**
     * Show a list with all the attachments according to the post's id
     * @param int announcement id
     * @return array with the post info
     * @author Arthur Portugal
     * @version November 2009, dokeos 1.8.6.2
     */
    public static function get_attachment($announcement_id)
    {
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $announcement_id = intval($announcement_id);
        $course_id = api_get_course_int_id();
        $row = array();
        $sql = 'SELECT id, path, filename, comment FROM ' . $tbl_announcement_attachment . '
				WHERE c_id = ' . $course_id . ' AND announcement_id = ' . $announcement_id . '';
        $result = Database::query($sql);
        if (Database::num_rows($result) != 0) {
            $row = Database::fetch_array($result, 'ASSOC');
        }
        return $row;
    }

    /**
     * This function add a attachment file into announcement
     * @param int  announcement id
     * @param string file comment
     * @param array  uploaded file $_FILES
     * @return int  -1 if failed, 0 if unknown (should not happen), 1 if success
     */
    public static function add_announcement_attachment_file($announcement_id, $file_comment, $file)
    {
        $_course = api_get_course_info();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $announcement_id = intval($announcement_id);
        $course_id = api_get_course_int_id();

        if (is_array($file) && $file['error'] == 0) {
            // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $courseDir = $_course['path'] . '/upload/announcements';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path . $courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file['name'];

            if (!filter_extension($new_file_name)) {
                $return = -1;
                Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir . '/' . $new_file_name;
                move_uploaded_file($file['tmp_name'], $new_path);

                $params = [
                    'c_id' => $course_id,
                    'filename' => $file_name,
                    'comment' => $file_comment,
                    'path' => $new_file_name,
                    'announcement_id' => $announcement_id,
                    'size' => intval($file['size']),
                ];

                $insertId = Database::insert($tbl_announcement_attachment, $params);
                if ($insertId) {
                    $sql = "UPDATE $tbl_announcement_attachment SET id = iid WHERE iid = $insertId";
                    Database::query($sql);
                }

                $return = 1;
            }
        }

        return $return;
    }

    /**
     * This function edit a attachment file into announcement
     * @param int attach id
     * @param array uploaded file $_FILES
     * @param string file comment
     * @return int
     */
    public static function edit_announcement_attachment_file($id_attach, $file, $file_comment)
    {
        $_course = api_get_course_info();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $course_id = api_get_course_int_id();

        if (is_array($file) && $file['error'] == 0) {
            // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $courseDir = $_course['path'] . '/upload/announcements';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path . $courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file ['name'];

            if (!filter_extension($new_file_name)) {
                $return = -1;
                Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir . '/' . $new_file_name;
                @move_uploaded_file($file['tmp_name'], $new_path);
                $safe_file_comment = Database::escape_string($file_comment);
                $safe_file_name = Database::escape_string($file_name);
                $safe_new_file_name = Database::escape_string($new_file_name);
                $id_attach = intval($id_attach);
                $sql = "UPDATE $tbl_announcement_attachment SET filename = '$safe_file_name', comment = '$safe_file_comment', path = '$safe_new_file_name', size ='" . intval($file['size']) . "'
					 	WHERE c_id = $course_id AND id = '$id_attach'";
                $result = Database::query($sql);
                if ($result === false) {
                    $return = -1;
                    Display :: display_error_message(get_lang('UplUnableToSaveFile'));
                } else {
                    $return = 1;
                }
            }
        }
        return $return;
    }

    /**
     * This function delete a attachment file by id
     * @param integer $id attachment file Id
     *
     */
    public static function delete_announcement_attachment_file($id)
    {
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $id = intval($id);
        $course_id = api_get_course_int_id();
        $sql = "DELETE FROM $tbl_announcement_attachment
                WHERE c_id = $course_id AND id = $id";

        Database::query($sql);
    }

    /**
     * @param int $id
     * @param bool $sendToUsersInSession
     * @param bool $sendToDrhUsers
     */
    public static function send_email($id, $sendToUsersInSession = false, $sendToDrhUsers = false)
    {
        $email = AnnouncementEmail::create(null, $id);
        $email->send($sendToUsersInSession, $sendToDrhUsers);
    }

    /**
     * @param $stok
     * @param $announcement_number
     * @param bool $getCount
     * @param null $start
     * @param null $limit
     * @param string $sidx
     * @param string $sord
     * @param string $titleToSearch
     * @param int $userIdToSearch
     *
     * @return array
     */
    public static function getAnnouncements(
        $stok,
        $announcement_number,
        $getCount = false,
        $start = null,
        $limit = null,
        $sidx = '',
        $sord = '',
        $titleToSearch = '',
        $userIdToSearch = 0
    ) {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $user_id = api_get_user_id();
        $group_id = api_get_group_id();
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id, true, true, 'announcement.session_id');
        $course_id = api_get_course_int_id();
        $_course = api_get_course_info();

        $group_memberships = GroupManager::get_group_ids($course_id, api_get_user_id());
        $allowUserEditSetting = api_get_course_setting('allow_user_edit_announcement');

        $select = ' DISTINCT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date';
        if ($getCount) {
            $select = ' COUNT(announcement.iid) count';
        }

        $searchCondition = '';
        if (!empty($titleToSearch)) {
            $titleToSearch = Database::escape_string($titleToSearch);
            $searchCondition .= " AND (title LIKE '%$titleToSearch%')";
        }

        if (!empty($userIdToSearch)) {
            $userIdToSearch = intval($userIdToSearch);
            $searchCondition .= " AND (ip.insert_user_id = $userIdToSearch)";
        }

        if (api_is_allowed_to_edit(false, true) ||
            ($allowUserEditSetting && !api_is_anonymous())
        ) {
            // A.1. you are a course admin with a USER filter
            // => see only the messages of this specific user + the messages of the group (s)he is member of.

            //if (!empty($user_id)) {
            if (0) {
                if (is_array($group_memberships) && count($group_memberships) > 0 ) {
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement, $tbl_item_property ip
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND
                                announcement.id = ip.ref AND
                                ip.tool = 'announcement' AND
                                (
                                    ip.to_user_id = $user_id OR
                                    ip.to_group_id IS NULL OR
                                    ip.to_group_id IN (0, ".implode(", ", $group_memberships).")
                                ) AND
                                ip.visibility IN ('1', '0')
                                $condition_session
                                $searchCondition
                            ORDER BY display_order DESC";
                } else {
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement, $tbl_item_property ip
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND
                                announcement.id = ip.ref AND
                                ip.tool ='announcement' AND
                                (ip.to_user_id = $user_id OR ip.to_group_id='0' OR ip.to_group_id IS NULL) AND
                                ip.visibility IN ('1', '0')
                            $condition_session
                            $searchCondition
                            ORDER BY display_order DESC";
                }
            } elseif ($group_id != 0) {
                // A.2. you are a course admin with a GROUP filter
                // => see only the messages of this specific group
                $sql = "SELECT $select
                        FROM $tbl_announcement announcement INNER JOIN $tbl_item_property ip
                        ON (announcement.id = ip.ref AND ip.tool='announcement')
                        WHERE
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND
                            ip.visibility<>'2' AND
                            (ip.to_group_id = $group_id OR ip.to_group_id='0' OR ip.to_group_id IS NULL)
                            $condition_session
                            $searchCondition
                        ORDER BY display_order DESC";
                //GROUP BY ip.ref
            } else {

                // A.3 you are a course admin without any group or user filter
                // A.3.a you are a course admin without user or group filter but WITH studentview
                // => see all the messages of all the users and groups without editing possibilities
                if (isset($isStudentView) && $isStudentView == "true") {
                    $sql = "SELECT $select
                        FROM $tbl_announcement announcement INNER JOIN $tbl_item_property ip
                        ON (announcement.id = ip.ref AND ip.tool='announcement')
                        WHERE
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND
                            ip.tool='announcement' AND
                            ip.visibility='1'
                            $condition_session
                            $searchCondition
                        ORDER BY display_order DESC";

                    //GROUP BY ip.ref
                } else {
                    // A.3.a you are a course admin without user or group filter and WTIHOUT studentview (= the normal course admin view)
                    // => see all the messages of all the users and groups with editing possibilities
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND ip.tool='announcement')
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id  AND
                                (ip.visibility='0' or ip.visibility='1')
                                $condition_session
                                $searchCondition
                            ORDER BY display_order DESC";
                    //GROUP BY ip.ref
                }
            }
        } else {
            // STUDENT
            if (is_array($group_memberships) && count($group_memberships) > 0) {
                if ($allowUserEditSetting && !api_is_anonymous()) {
                    if ($group_id == 0) {
                        // No group
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$user_id."' OR (
                                ip.to_user_id='".$user_id."' OR
                                (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))
                            )
                        ) ";
                    } else {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$user_id."' OR ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".$group_id.")
                        )";
                    }
                } else {
                    if ($group_id == 0) {
                        $cond_user_id = " AND (
                            ip.to_user_id = $user_id AND (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))
                        ) ";
                    } else {
                       $cond_user_id = " AND (
                            ip.to_user_id = $user_id AND (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".$group_id."))
                        )";
                    }
                }

                $sql = "SELECT $select
                        FROM $tbl_announcement announcement,
                        $tbl_item_property ip
                        WHERE
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND
                            announcement.id = ip.ref
                            AND ip.tool='announcement'
                            $cond_user_id
                            $condition_session
                            $searchCondition
                            AND ip.visibility='1'
                        ORDER BY display_order DESC";
            } else {

                if ($user_id) {
                    if ($allowUserEditSetting && !api_is_anonymous()) {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".api_get_user_id()."' OR
                            (ip.to_user_id='".$user_id."' AND (ip.to_group_id='0' OR ip.to_group_id IS NULL))
                        ) ";
                    } else {
                        $cond_user_id = " AND (ip.to_user_id='".$user_id."' AND (ip.to_group_id='0' OR ip.to_group_id IS NULL) ) ";
                    }

                    $sql = "SELECT $select
						FROM $tbl_announcement announcement, $tbl_item_property ip
						WHERE
    						announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
    						announcement.id = ip.ref AND
    						ip.tool='announcement'
    						$cond_user_id
    						$condition_session
    						$searchCondition
    						AND ip.visibility='1'
    						AND announcement.session_id IN(0, ".$session_id.")
						ORDER BY display_order DESC";

                } else {
                    if (($allowUserEditSetting && !api_is_anonymous())) {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$user_id."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL
                        )";
                    } else {
                        $cond_user_id = " AND ip.to_group_id='0' OR ip.to_group_id IS NULL ";
                    }

                    $sql = "SELECT $select
						FROM $tbl_announcement announcement, $tbl_item_property ip
						WHERE
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND
                            announcement.id = ip.ref AND
                            ip.tool='announcement'
                            $cond_user_id
                            $condition_session
                            $searchCondition
                            AND
                            ip.visibility='1' AND
                            announcement.session_id IN ( 0,".api_get_session_id().")";
                }
            }
        }

        if (!is_null($start) && !is_null($limit)) {
            $start = intval($start);
            $limit = intval($limit);
            $sql .= " LIMIT $start, $limit";
        }

        $result = Database::query($sql);
        if ($getCount) {
            $result = Database::fetch_array($result, 'ASSOC');

            return $result['count'];
        }

        $iterator = 1;
        $bottomAnnouncement = $announcement_number;
        $origin = null;

        $displayed = [];
        $results = [];
        $actionUrl = api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq();
        while ($myrow = Database::fetch_array($result, 'ASSOC')) {
            if (!in_array($myrow['id'], $displayed)) {
                $sent_to_icon = '';
                // the email icon
                if ($myrow['email_sent'] == '1') {
                    $sent_to_icon = ' '.Display::return_icon('email.gif', get_lang('AnnounceSentByEmail'));
                }

                $title = $myrow['title'].$sent_to_icon;
                $item_visibility = api_get_item_visibility($_course, TOOL_ANNOUNCEMENT, $myrow['id'], $session_id);
                $myrow['visibility'] = $item_visibility;

                // show attachment list
                $attachment_list = AnnouncementManager::get_attachment($myrow['id']);

                $attachment_icon = '';
                if (count($attachment_list)>0) {
                    $attachment_icon = ' '.Display::return_icon('attachment.gif',get_lang('Attachment'));
                }

                /* TITLE */
                $user_info = api_get_user_info($myrow['insert_user_id']);
                $username = sprintf(get_lang("LoginX"), $user_info['username']);
                $username_span = Display::tag('span', api_get_person_name($user_info['firstName'], $user_info['lastName']), array('title'=>$username));
                $title = Display::url($title.$attachment_icon, $actionUrl.'&action=view&id='.$myrow['id']);
                //$html .= Display::tag('td', $username_span, array('class' => 'announcements-list-line-by-user'));
                //$html .= Display::tag('td', api_convert_and_format_date($myrow['insert_date'], DATE_TIME_FORMAT_LONG), array('class' => 'announcements-list-line-datetime'));

                $modify_icons = '';
                // we can edit if : we are the teacher OR the element belongs to
                // the session we are coaching OR the option to allow users to edit is on
                if (api_is_allowed_to_edit(false, true) ||
                    (api_is_course_coach() && api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $myrow['id']))
                    || (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
                ) {
                    $modify_icons = "<a href=\"".$actionUrl."&action=modify&id=".$myrow['id']."\">".
                        Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL)."</a>";
                    if ($myrow['visibility']==1) {
                        $image_visibility="visible";
                        $alt_visibility=get_lang('Hide');
                    } else {
                        $image_visibility="invisible";
                        $alt_visibility=get_lang('Visible');
                    }
                    $modify_icons .=  "<a href=\"".$actionUrl."&origin=".$origin."&action=showhide&id=".$myrow['id']."&sec_token=".$stok."\">".
                        Display::return_icon($image_visibility.'.png', $alt_visibility,'',ICON_SIZE_SMALL)."</a>";

                    // DISPLAY MOVE UP COMMAND only if it is not the top announcement
                    if ($iterator != 1) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=move&up=".$myrow["id"]."&sec_token=".$stok."\">".
                            Display::return_icon('up.gif', get_lang('Up'))."</a>";
                    } else {
                        $modify_icons .= Display::return_icon('up_na.gif', get_lang('Up'));
                    }
                    if ($iterator < $bottomAnnouncement) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=move&down=".$myrow["id"]."&sec_token=".$stok."\">".
                            Display::return_icon('down.gif', get_lang('Down'))."</a>";
                    } else {
                        $modify_icons .= Display::return_icon('down_na.gif', get_lang('Down'));
                    }
                    if (api_is_allowed_to_edit(false,true)) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=delete&id=".$myrow['id']."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,api_get_system_encoding()))."')) return false;\">".
                            Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).
                            "</a>";
                    }
                    $iterator ++;
                } else {
                    $modify_icons = Display::url(
                        Display::return_icon('default.png'),
                        $actionUrl.'&action=view&id='.$myrow['id']
                    );
                }

                $announcement = [
                    'id' => $myrow["id"],
                    'title' => $title,
                    'username' => $username_span,
                    'insert_date' => api_convert_and_format_date($myrow['insert_date'], DATE_TIME_FORMAT_LONG),
                    'actions' => $modify_icons
                ];

                $results[] = $announcement;
            }
            $displayed[] = $myrow['id'];
        }

        return $results;
    }

    /**
     * @return int
     */
    public static function getNumberAnnouncements()
    {
        // Maximum title messages to display
        $maximum 	= '12';
        // Database Table Definitions
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $session_id = api_get_session_id();
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $userId = api_get_user_id();
        $condition_session = api_get_session_condition($session_id, true, true, 'announcement.session_id');

        if (api_is_allowed_to_edit(false,true))  {
            // check teacher status
            if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {

                if (api_get_group_id() == 0) {
                    $group_condition = "";
                } else {
                    $group_condition = " AND (ip.to_group_id='".api_get_group_id()."' OR ip.to_group_id = 0 OR ip.to_group_id IS NULL)";
                }
                $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
				FROM $tbl_announcement announcement, $tbl_item_property ip
				WHERE
				    announcement.c_id   = $course_id AND
                    ip.c_id             = $course_id AND
                    announcement.id     = ip.ref AND
                    ip.tool             = 'announcement' AND
                    ip.visibility       <> '2'
                    $group_condition
                    $condition_session
				GROUP BY ip.ref
				ORDER BY display_order DESC
				LIMIT 0,$maximum";
            }
        } else {
            // students only get to see the visible announcements
            if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
                $group_memberships = GroupManager::get_group_ids($_course['real_id'], $userId);

                if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {

                    if (api_get_group_id() == 0) {
                        $cond_user_id = " AND (
                        ip.lastedit_user_id = '".$userId."' OR (
                            ip.to_user_id='".$userId."' OR
                            ip.to_group_id IN (0, ".implode(", ", $group_memberships).") OR
                            ip.to_group_id IS NULL
                            )
                        )
                        ";
                    } else {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$userId."'OR
                            ip.to_group_id IN (0, ".api_get_group_id().") OR
                            ip.to_group_id IS NULL
                        )";
                    }
                } else {
                    if (api_get_group_id() == 0) {
                        $cond_user_id = " AND (
                            ip.to_user_id='".$userId."' OR
                            ip.to_group_id IN (0, ".implode(", ", $group_memberships).") OR
                            ip.to_group_id IS NULL
                        ) ";
                    } else {
                        $cond_user_id = " AND (
                            ip.to_user_id='".$userId."' OR
                            ip.to_group_id IN (0, ".api_get_group_id().") OR
                            ip.to_group_id IS NULL
                        ) ";
                    }
                }

                // the user is member of several groups => display personal announcements AND his group announcements AND the general announcements
                if (is_array($group_memberships) && count($group_memberships)>0) {
                    $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                    FROM $tbl_announcement announcement, $tbl_item_property ip
                    WHERE
                        announcement.c_id = $course_id AND
                        ip.c_id = $course_id AND
                        announcement.id = ip.ref AND
                        ip.tool='announcement'
                        AND ip.visibility='1'
                        $cond_user_id
                        $condition_session
                    GROUP BY ip.ref
                    ORDER BY display_order DESC
                    LIMIT 0, $maximum";
                } else {
                    // the user is not member of any group
                    // this is an identified user => show the general announcements AND his personal announcements
                    if ($userId) {
                        if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
                            $cond_user_id = " AND (
                                ip.lastedit_user_id = '".$userId."' OR
                                ( ip.to_user_id='".$userId."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL)
                            ) ";
                        } else {
                            $cond_user_id = " AND ( ip.to_user_id='".$userId."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL) ";
                        }
                        $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                            FROM $tbl_announcement announcement, $tbl_item_property ip
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND
                                announcement.id = ip.ref
                                AND ip.tool='announcement'
                                AND ip.visibility='1'
                                $cond_user_id
                                $condition_session
                            GROUP BY ip.ref
                            ORDER BY display_order DESC
                            LIMIT 0, $maximum";
                    } else {

                        if (api_get_course_setting('allow_user_edit_announcement')) {
                            $cond_user_id = " AND (
                                ip.lastedit_user_id = '".api_get_user_id()."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL
                            ) ";
                        } else {
                            $cond_user_id = " AND ip.to_group_id='0' ";
                        }

                        // the user is not identiefied => show only the general announcements
                        $sql="SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                                FROM $tbl_announcement announcement, $tbl_item_property ip
                                WHERE
                                    announcement.c_id = $course_id AND
                                    ip.c_id = $course_id AND
                                    announcement.id = ip.ref
                                    AND ip.tool='announcement'
                                    AND ip.visibility='1'
                                    AND ip.to_group_id='0'
                                    $condition_session
                                GROUP BY ip.ref
                                ORDER BY display_order DESC
                                LIMIT 0, $maximum";
                    }
                }
            }
        }

        $result = Database::query($sql);

        return Database::num_rows($result);
    }
}
