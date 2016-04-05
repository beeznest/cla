<?php
/* For licensing terms, see /license.txt */

/**
 * Class GroupPortalManager
 * @deprecated use UserGroup functions.
 * Include/require it in your code to use its functionality.
 * @author Julio Montoya <gugli100@gmail.com>
 * @package chamilo.library
 */
class GroupPortalManager
{
    /**
     * Creates a new group
     *
     * @author Julio Montoya <gugli100@gmail.com>,
     *
     * @param	string	$name The URL of the site
     * @param   string  $description The description of the site
     * @param   string  $url
     * @param	int		$visibility is active or not
     * @param   string  $picture
     *
     * @return boolean if success
     */
    public static function add($name, $description, $url, $visibility, $picture = '')
    {
        $now = api_get_utc_datetime();
        $table = Database :: get_main_table(TABLE_MAIN_GROUP);
        $sql = "INSERT INTO $table
                   SET name 	= '".Database::escape_string($name)."',
                   description = '".Database::escape_string($description)."',
                   picture_uri = '".Database::escape_string($picture)."',
                   url 		= '".Database::escape_string($url)."',
                   visibility 	= '".Database::escape_string($visibility)."',
                   created_on = '".$now."',
                   updated_on = '".$now."'";
        Database::query($sql);
        $id = Database::insert_id();
        if ($id) {
            Event::addEvent(LOG_GROUP_PORTAL_CREATED, LOG_GROUP_PORTAL_ID, $id);

            return $id;
        }

        return false;
    }

    /**
     * Updates a group
     * @author Julio Montoya <gugli100@gmail.com>,
     *
     * @param int $group_id The id
     * @param string $name The description of the site
     * @param string $description
     * @param string $url
     * @param int $visibility
     * @param string $picture_uri
     * @param bool $allowMemberGroupToLeave
     * @return bool if success
     */
    public static function update($group_id, $name, $description, $url, $visibility, $picture_uri, $allowMemberGroupToLeave = null)
    {
        $group_id = intval($group_id);
        $table = Database::get_main_table(TABLE_MAIN_GROUP);
        $now = api_get_utc_datetime();
        $groupLeaveCondition = null;
        if (isset($allowMemberGroupToLeave)) {
            $allowMemberGroupToLeave = $allowMemberGroupToLeave == true ? 1 : 0;
            $groupLeaveCondition = " allow_members_leave_group = $allowMemberGroupToLeave , ";
        }
        $sql = "UPDATE $table SET
                    name 	= '".Database::escape_string($name)."',
                    description = '".Database::escape_string($description)."',
                    picture_uri = '".Database::escape_string($picture_uri)."',
                    url 		= '".Database::escape_string($url)."',
                    visibility 	= '".Database::escape_string($visibility)."',
                    $groupLeaveCondition
                    updated_on 	= '".$now."'
                WHERE id = '$group_id'";
        $result = Database::query($sql);

        return $result;
    }

    /**
     * Deletes a group
     * @author Julio Montoya
     * @param int $id
     * @return boolean true if success
     * */
    public static function delete($id)
    {
        $id = intval($id);
        $table = Database :: get_main_table(TABLE_MAIN_GROUP);
        $sql = "DELETE FROM $table WHERE id = ".intval($id);
        $result = Database::query($sql);
        // Deleting all relationship with users and groups
        self::delete_users($id);
        // Delete group image
        self::delete_group_picture($id);
        Event::addEvent(LOG_GROUP_PORTAL_DELETED, LOG_GROUP_PORTAL_ID, $id);

        return $result;
    }

    /**
     * Gets data of all groups
     * @author Julio Montoya
     * @param int	$visibility
     * @param int	$from which record the results will begin (use for pagination)
     * @param int	$number_of_items
     *
     * @return array
     * */
    public static function get_all_group_data($visibility = GROUP_PERMISSION_OPEN, $from = 0, $number_of_items = 10)
    {
        $table = Database :: get_main_table(TABLE_MAIN_GROUP);
        $visibility = intval($visibility);
        $sql = "SELECT * FROM $table WHERE visibility = $visibility ";
        $res = Database::query($sql);
        $data = array();
        while ($item = Database::fetch_array($res)) {
            $data[] = $item;
        }

        return $data;
    }

    /**
     * Gets a list of all group
     * @param int $without_this_one id of a group not to include (i.e. to exclude)
     *
     * @return array : id => name
     * */
    public static function get_groups_list($without_this_one = NULL)
    {
        $where = '';
        if (isset($without_this_one) && (intval($without_this_one) == $without_this_one)) {
            $where = "WHERE id <> $without_this_one";
        }
        $table = Database :: get_main_table(TABLE_MAIN_GROUP);
        $sql = "SELECT id, name FROM $table $where order by name";
        $res = Database::query($sql);
        $list = array();
        while ($item = Database::fetch_assoc($res)) {
            $list[$item['id']] = $item['name'];
        }

        return $list;
    }

    /**
     * Gets the group data
     * @param int $group_id
     *
     * @return array
     */
    public static function get_group_data($group_id)
    {
        $table = Database :: get_main_table(TABLE_MAIN_GROUP);
        $group_id = intval($group_id);
        $sql = "SELECT * FROM $table WHERE id = $group_id ";
        $res = Database::query($sql);
        $item = array();
        if (Database::num_rows($res) > 0) {
            $item = Database::fetch_array($res, 'ASSOC');
        }

        return $item;
    }

    /**
     * Set a parent group
     * @param int $group_id
     * @param int $parent_group_id if 0, we delete the parent_group association
     * @param int $relation_type
     * @return resource
     **/
    public static function set_parent_group($group_id, $parent_group_id, $relation_type = 1)
    {
        $table = Database :: get_main_table(TABLE_MAIN_GROUP_REL_GROUP);
        $group_id = intval($group_id);
        $parent_group_id = intval($parent_group_id);
        if ($parent_group_id == 0) {
            $sql = "DELETE FROM $table WHERE subgroup_id = $group_id";
        } else {
            $sql = "SELECT group_id FROM $table WHERE subgroup_id = $group_id";
            $res = Database::query($sql);
            if (Database::num_rows($res) == 0) {
                $sql = "INSERT INTO $table SET group_id = $parent_group_id, subgroup_id = $group_id, relation_type = $relation_type";
            } else {
                $sql = "UPDATE $table SET group_id = $parent_group_id, relation_type = $relation_type
                        WHERE subgroup_id = $group_id";
            }
        }
        $res = Database::query($sql);
        return $res;
    }

    /**
     * Get the parent group
     * @param int $group_id
     * @param int $relation_type
     * @return int parent_group_id or false
     * */
    public static function get_parent_group($group_id, $relation_type = 1)
    {
        $table = Database :: get_main_table(TABLE_MAIN_GROUP_REL_GROUP);
        $group_id = intval($group_id);
        $sql = "SELECT group_id FROM $table WHERE subgroup_id = $group_id";
        $res = Database::query($sql);
        if (Database::num_rows($res) == 0) {
            return 0;
        } else {
            $arr = Database::fetch_assoc($res);
            return $arr['group_id'];
        }
    }

    /**
     * Get the subgroups ID from a group.
     * The default $levels value is 10 considering it as a extensive level of depth
     * @param int $groupId The parent group ID
     * @param int $levels The depth levels
     * @return array The list of ID
     */
    public static function getGroupsByDepthLevel($groupId, $levels = 10)
    {
        $groups = array();
        $groupId = intval($groupId);

        $groupTable = Database::get_main_table(TABLE_MAIN_GROUP);
        $groupRelGroupTable = Database :: get_main_table(TABLE_MAIN_GROUP_REL_GROUP);

        $select = "SELECT ";
        $from = "FROM $groupTable g1 ";

        for ($i = 1; $i <= $levels; $i++) {
            $tableIndexNumber = $i;
            $tableIndexJoinNumber = $i - 1;

            $select .= "g$i.id as id_$i ";

            $select .= ($i != $levels ? ", " : null);

            if ($i == 1) {
                $from .= "INNER JOIN $groupRelGroupTable gg0 ON g1.id = gg0.subgroup_id and gg0.group_id = $groupId ";
            } else {
                $from .= "LEFT JOIN $groupRelGroupTable gg$tableIndexJoinNumber ";
                $from .= " ON g$tableIndexJoinNumber.id = gg$tableIndexJoinNumber.group_id ";
                $from .= "LEFT JOIN $groupTable g$tableIndexNumber ";
                $from .= " ON gg$tableIndexJoinNumber.subgroup_id = g$tableIndexNumber.id ";
            }
        }

        $result = Database::query("$select $from");

        while ($item = Database::fetch_assoc($result)) {
            foreach ($item as $groupId) {
                if (!empty($groupId)) {
                    $groups[] = $groupId;
                }
            }
        }

        return array_map('intval', $groups);
    }

    /**
     * @param int $root
     * @param int $level
     * @return array
     */
    public static function get_subgroups($root, $level)
    {
        $t_group = Database::get_main_table(TABLE_MAIN_GROUP);
        $t_rel_group = Database :: get_main_table(TABLE_MAIN_GROUP_REL_GROUP);
        $select_part = "SELECT ";
        $cond_part = '';
        for ($i = 1; $i <= $level; $i++) {
            $g_number = $i;
            $rg_number = $i - 1;
            if ($i == $level) {
                $select_part .= "g$i.id as id_$i, g$i.name as name_$i ";
            } else {
                $select_part .= "g$i.id as id_$i, g$i.name name_$i, ";
            }
            if ($i == 1) {
                $cond_part .= "FROM $t_group g1 JOIN $t_rel_group rg0 on g1.id = rg0.subgroup_id and rg0.group_id = $root ";
            } else {
                $cond_part .= "LEFT JOIN $t_rel_group rg$rg_number on g$rg_number.id = rg$rg_number.group_id ";
                $cond_part .= "LEFT JOIN $t_group g$g_number on rg$rg_number.subgroup_id = g$g_number.id ";
            }
        }
        $sql = $select_part.' '.$cond_part;
        $res = Database::query($sql);
        $toReturn = array();

        while ($item = Database::fetch_assoc($res)) {
            foreach ($item as $key => $value) {
                if ($key == 'id_1') {
                    $toReturn[$value]['name'] = $item['name_1'];
                } else {
                    $temp = explode('_', $key);
                    $indexKey = $temp[1];
                    $stringKey = $temp[0];
                    $previousKey = $stringKey.'_'.$indexKey - 1;
                    if ($stringKey == 'id' && isset($item[$key])) {
                        $toReturn[$item[$previousKey]]['hrms'][$indexKey]['name'] = $item['name_'.$indexKey];
                    }
                }
            }
        }
        return $toReturn;
    }

    /**
     * @param int $group_id
     * @return array
     */
    public static function get_parent_groups($group_id)
    {
        $t_rel_group = Database :: get_main_table(TABLE_MAIN_GROUP_REL_GROUP);
        $max_level = 10;
        $select_part = "SELECT ";
        $cond_part = '';
        for ($i = 1; $i <= $max_level; $i++) {
            $g_number = $i;
            $rg_number = $i - 1;
            if ($i == $max_level) {
                $select_part .= "rg$rg_number.group_id as id_$rg_number ";
            } else {
                $select_part .="rg$rg_number.group_id as id_$rg_number, ";
            }
            if ($i == 1) {
                $cond_part .= "FROM $t_rel_group rg0 LEFT JOIN $t_rel_group rg$i on rg$rg_number.group_id = rg$i.subgroup_id ";
            } else {
                $cond_part .= " LEFT JOIN $t_rel_group rg$i on rg$rg_number.group_id = rg$i.subgroup_id ";
            }
        }
        $sql = $select_part.' '.$cond_part."WHERE rg0.subgroup_id='$group_id'";
        $res = Database::query($sql);
        $temp_arr = Database::fetch_array($res, 'NUM');
        $toReturn = array();
        if (is_array($temp_arr)) {
            foreach ($temp_arr as $elt) {
                if (isset($elt)) {
                    $toReturn[] = $elt;
                }
            }
        }

        return $toReturn;
    }

    /**
     * Gets the tags from a given group
     * @param int $group_id
     * @param bool $show_tag_links show group links or not
     *
     */
    public static function get_group_tags($group_id, $show_tag_links = true)
    {
        $tag = Database :: get_main_table(TABLE_MAIN_TAG);
        $table_group_rel_tag = Database :: get_main_table(TABLE_MAIN_GROUP_REL_TAG);
        $group_id = intval($group_id);

        $sql = "SELECT tag FROM $tag t
                INNER JOIN $table_group_rel_tag gt
                ON (gt.tag_id= t.id)
                WHERE
                    gt.group_id = $group_id ";
        $res = Database::query($sql);
        $tags = array();
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $tags[] = $row;
            }
        }

        if ($show_tag_links) {
            if (is_array($tags) && count($tags) > 0) {
                foreach ($tags as $tag) {
                    $tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tag['tag'].'">'.$tag['tag'].'</a>';
                }
                if (is_array($tags) && count($tags) > 0) {
                    $tags = implode(', ', $tag_tmp);
                }
            } else {
                $tags = '';
            }
        }
        return $tags;
    }

    /**
     * Gets the inner join from users and group table
     * @param string $user_id
     * @param string $relation_type
     * @param bool $with_image
     * @return array Database::store_result of the result
     * @author Julio Montoya
     **/
    public static function get_groups_by_user($user_id = '', $relation_type = GROUP_USER_PERMISSION_READER, $with_image = false)
    {
        $table_group_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $tbl_group = Database::get_main_table(TABLE_MAIN_GROUP);
        $user_id = intval($user_id);

        if ($relation_type == 0) {
            $where_relation_condition = '';
        } else {
            $relation_type = intval($relation_type);
            $where_relation_condition = "AND gu.relation_type = $relation_type ";
        }

        $sql = "SELECT g.picture_uri, g.name, g.description, g.id, gu.relation_type
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.group_id = g.id
				WHERE
				    gu.user_id = $user_id $where_relation_condition
				ORDER BY created_on desc ";

        $result = Database::query($sql);
        $array = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($with_image) {
                    $picture = self::get_picture_group($row['id'], $row['picture_uri'], 80);
                    $img = '<img src="'.$picture['file'].'" />';
                    $row['picture_uri'] = $img;
                }
                $array[$row['id']] = $row;
            }
        }
        return $array;
    }

    /** Gets the inner join of users and group table
     * @param int  quantity of records
     * @param bool show groups with image or not
     * @return array  with group content
     * @author Julio Montoya
     * */
    public static function get_groups_by_popularity($num = 6, $with_image = true)
    {
        $table_group_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $tbl_group = Database::get_main_table(TABLE_MAIN_GROUP);
        if (empty($num)) {
            $num = 6;
        } else {
            $num = intval($num);
        }
        // only show admins and readers
        $where_relation_condition = " WHERE gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";
        $sql = "SELECT DISTINCT count(user_id) as count, g.picture_uri, g.name, g.description, g.id
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.group_id = g.id $where_relation_condition
				GROUP BY g.id
				ORDER BY count DESC
				LIMIT $num";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = self::get_picture_group($row['id'], $row['picture_uri'], 80);
                $img = '<img src="'.$picture['file'].'" />';
                $row['picture_uri'] = $img;
            }
            if (empty($row['id'])) {
                continue;
            }
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /**
     * Gets the last groups created
     * @param int  quantity of records
     * @param bool show groups with image or not
     * @return array  with group content
     * @author Julio Montoya
     * */
    public static function get_groups_by_age($num = 6, $with_image = true)
    {
        $table_group_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $tbl_group = Database::get_main_table(TABLE_MAIN_GROUP);

        if (empty($num)) {
            $num = 6;
        } else {
            $num = intval($num);
        }
        $where_relation_condition = " WHERE gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";
        $sql = "SELECT DISTINCT count(user_id) as count, g.picture_uri, g.name, g.description, g.id
                FROM $tbl_group g INNER JOIN $table_group_rel_user gu
                ON gu.group_id = g.id
                $where_relation_condition
                GROUP BY g.id
                ORDER BY created_on DESC
                LIMIT $num ";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = self::get_picture_group($row['id'], $row['picture_uri'], 80);
                $img = '<img src="'.$picture['file'].'" />';
                $row['picture_uri'] = $img;
            }
            if (empty($row['id'])) {
                continue;
            }
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /**
     * Gets the group's members
     * @param int group id
     * @param bool show image or not of the group
     * @param array list of relation type use constants
     * @param int from value
     * @param int limit
     * @param array image configuration, i.e array('height'=>'20px', 'size'=> '20px')
     * @return array list of users in a group
     */
    public static function get_users_by_group(
        $group_id,
        $with_image = false,
        $relation_type = array(),
        $from = null,
        $limit = null,
        $image_conf = array('size' => USER_IMAGE_SIZE_MEDIUM, 'height' => 80)
    ) {
        $table_group_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = intval($group_id);

        if (empty($group_id)) {
            return array();
        }

        $limit_text = '';
        if (isset($from) && isset($limit)) {
            $from = intval($from);
            $limit = intval($limit);
            $limit_text = "LIMIT $from, $limit";
        }

        if (count($relation_type) == 0) {
            $where_relation_condition = '';
        } else {
            $new_relation_type = array();
            foreach ($relation_type as $rel) {
                $rel = intval($rel);
                $new_relation_type[] = "'$rel'";
            }
            $relation_type = implode(',', $new_relation_type);
            if (!empty($relation_type))
                $where_relation_condition = "AND gu.relation_type IN ($relation_type) ";
        }

        $sql = "SELECT
                    picture_uri as image,
                    u.id,
                    u.firstname,
                    u.lastname,
                    relation_type
    		    FROM $tbl_user u INNER JOIN $table_group_rel_user gu
    			ON (gu.user_id = u.id)
    			WHERE
    			    gu.group_id= $group_id
    			    $where_relation_condition
    			ORDER BY relation_type, firstname $limit_text";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = UserManager::getUserPicture($row['id']);
                $row['image'] = '<img src="'.$picture.'" />';
            }
            $array[$row['id']] = $row;
        }

        return $array;
    }

    /**
     * Gets all the members of a group no matter the relationship for more specifications use get_users_by_group
     * @param int group id
     * @return array
     */
    public static function get_all_users_by_group($group_id)
    {
        $table_group_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = intval($group_id);

        if (empty($group_id)) {
            return array();
        }
        $sql = "SELECT u.id, u.firstname, u.lastname, relation_type
                FROM $tbl_user u
                INNER JOIN $table_group_rel_user gu
                ON (gu.user_id = u.id)
                WHERE gu.group_id= $group_id
                ORDER BY relation_type, firstname";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /**
     * Gets the relationship between a group and a User
     * @author Julio Montoya
     * @param int user id
     * @param int group_id
     * @return int 0 if there are not relationship otherwise returns the user group
     * */
    public static function get_user_group_role($user_id, $group_id)
    {
        $em = Database::getManager();

        $result = $em
            ->getRepository('ChamiloCoreBundle:UsergroupRelUser')
            ->findOneBy([
                'usergroup' => intval($group_id),
                'user' => intval($user_id)
            ]);

        if (!$result) {
            return 0;
        }

        return $result->getRelationType();
    }

    /**
     * Add a user into a group
     * @author Julio Montoya
     * @param  int user_id
     * @param  int url_id
     * @return boolean true if success
     **/
    public static function add_user_to_group($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $result = false;

        if (empty($user_id) || empty($group_id)) {
            return false;
        }

        $em = Database::getManager();

        $user = $em->find('ChamiloCoreBundle:User', $user_id);
        $usergroup = $em->find('ChamiloCoreBundle:Usergroup', $group_id);
        $role = self::get_user_group_role($user_id, $group_id);

        if ($role == 0) {
            $usergroupRelUser = new \Chamilo\CoreBundle\Entity\UsergroupRelUser();
            $usergroupRelUser
                ->setUser($user)
                ->setUsergroup($usergroup)
                ->setRelationType($relation_type);

            $em->persist($usergroupRelUser);
            $em->flush();

            Event::addEvent(
                LOG_GROUP_PORTAL_USER_SUBSCRIBED,
                LOG_GROUP_PORTAL_REL_USER_ARRAY,
                array(
                    'user_id' => $user_id,
                    'group_id' => $group_id,
                    'relation_type' => $relation_type,
                )
            );

            return true;
        } else if ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
            // If somebody already invited me I can be added
            self::update_user_role(
                $user_id,
                $group_id,
                GROUP_USER_PERMISSION_READER
            );

            return true;
        }

        return $result;
    }

    /**
     * Add a group of users into a group of URLs
     * @author Julio Montoya
     * @param  array $user_list of user_ids
     * @param  array $group_list of url_ids
     * @param string $relation_type
     **/
    public static function add_users_to_groups($user_list, $group_list, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $result_array = array();
        $relation_type = intval($relation_type);

        if (is_array($user_list) && is_array($group_list)) {
            foreach ($group_list as $group_id) {
                foreach ($user_list as $user_id) {
                    $result = self::add_user_to_group($user_id, $group_id, $relation_type);
                    if ($result) {
                        $result_array[$group_id][$user_id] = 1;
                    } else {
                        $result_array[$group_id][$user_id] = 0;
                    }
                }
            }
        }
        return $result_array;
    }

    /**
     * Deletes a group and user relationship
     * @author Julio Montoya
     * @param int $group_id
     * @param int $relation_type (optional)
     * @return boolean true if success
     * */
    public static function delete_users($group_id, $relation_type = null)
    {
        $table = Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $condition_relation = "";
        if (!empty($relation_type)) {
            $relation_type = intval($relation_type);
            $condition_relation = " AND relation_type = '$relation_type'";
        }
        $sql = "DELETE FROM $table
                WHERE group_id = ".intval($group_id).$condition_relation;
        $result = Database::query($sql);

        Event::addEvent(
            LOG_GROUP_PORTAL_USER_DELETE_ALL,
            LOG_GROUP_PORTAL_REL_USER_ARRAY,
            array('group_id' => $group_id, 'relation_type' => $relation_type)
        );

        return $result;
    }

    /**
     * Deletes an url and session relationship
     * @author Julio Montoya
     * @param  int $user_id
     * @param  int $group_id
     * @return boolean true if success
     * */
    public static function delete_user_rel_group($user_id, $group_id)
    {
        $em = Database::getManager();

        $result = $em
            ->getRepository('ChamiloCoreBundle:UsergroupRelUser')
            ->findOneBy([
                'usergroup' => intval($group_id),
                'user' => intval($user_id)
            ]);

        if (!$result) {
            return false;
        }

        $em->remove($result);
        $em->flush();

        Event::addEvent(
            LOG_GROUP_PORTAL_USER_UNSUBSCRIBED,
            LOG_GROUP_PORTAL_REL_USER_ARRAY,
            array('user_id' => $user_id, 'group_id' => $group_id)
        );

        return true;
    }

    /**
     * Updates the group_rel_user table  with a given user and group ids
     * @author Julio Montoya
     * @param int  $user_id
     * @param int  $group_id
     * @param int  $relation_type
     *
     * @return bool
     **/
    public static function update_user_role($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        if (empty($user_id) || empty($group_id) || empty($relation_type)) {
            return false;
        }

        $em = Database::getManager();
        $group_id = intval($group_id);
        $user_id = intval($user_id);

        $usergroupUser = $em
            ->getRepository('ChamiloCoreBundle:UsergroupRelUser')
            ->findOneBy([
                'user' => $user_id,
                'usergroup' => $group_id
            ]);

        if (!$usergroupUser) {
            return false;
        }

        $usergroupUser->setRelationType($relation_type);

        $em->merge($usergroupUser);
        $em->flush();

        Event::addEvent(
            LOG_GROUP_PORTAL_USER_UPDATE_ROLE,
            LOG_GROUP_PORTAL_REL_USER_ARRAY,
            array('user_id' => $user_id, 'group_id' => $group_id, 'relation_type' => $relation_type)
        );
        return true;

    }

    /**
     * @param int $user_id
     * @param int $group_id
     */
    public static function get_group_admin_list($user_id, $group_id)
    {
        $table_group_rel_user = Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
        $group_id = intval($group_id);
        $user_id = intval($user_id);

        $sql = "SELECT user_id FROM  $table_group_rel_user
                WHERE
                    relation_type = ".GROUP_USER_PERMISSION_ADMIN." AND
                    user_id = $user_id AND
                    group_id = $group_id";
        Database::query($sql);
    }

    /**
     * @param string $tag
     * @param int $from
     * @param int $number_of_items
     * @param bool $getCount
     * @return array
     */
    public static function get_all_group_tags($tag, $from = 0, $number_of_items = 10, $getCount = false)
    {
        // Database table definition
        $group_table = Database::get_main_table(TABLE_MAIN_GROUP);
        $table_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_group_tag_values = Database::get_main_table(TABLE_MAIN_GROUP_REL_TAG);
        $field_id = 5;
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        // all the information of the field
        if ($getCount) {
            $select = "SELECT count(DISTINCT g.id) count";
        } else {
            $select = " SELECT DISTINCT g.id, g.name, g.description, g.picture_uri ";
        }
        $sql = " $select
                FROM $group_table g
                LEFT JOIN $table_group_tag_values tv ON (g.id AND tv.group_id)
                LEFT JOIN $table_tag t ON (tv.tag_id = t.id)
                WHERE
                    tag LIKE '$tag%' AND field_id= $field_id OR
                    (
                       g.name LIKE '".Database::escape_string('%'.$tag.'%')."' OR
                       g.description LIKE '".Database::escape_string('%'.$tag.'%')."' OR
                       g.url LIKE '".Database::escape_string('%'.$tag.'%')."'
                     )";

        $sql .= " LIMIT $from, $number_of_items";

        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            if ($getCount) {
                $row = Database::fetch_array($result, 'ASSOC');
                return $row['count'];
            }
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[$row['id']] = $row;
            }
        }

        return $return;
    }

    /**
     * Creates new group pictures in various sizes of a user, or deletes user photos.
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php
     * @param int	The group id
     * @param string $file The common file name for the newly created photos.
     * It will be checked and modified for compatibility with the file system.
     * If full name is provided, path component is ignored.
     * If an empty name is provided, then old user photos are deleted only, @see UserManager::delete_user_picture()
     * as the prefered way for deletion.
     * @param	string		$source_file The full system name of the image from which user photos will be created.
     * @return	string/bool	Returns the resulting file name of created images which usually should be stored in DB.
     * When deletion is recuested returns empty string. In case of internal error or negative validation returns FALSE.
     */
    public static function update_group_picture($group_id, $file = null, $source_file = null)
    {
        // Validation.
        if (empty($group_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where photos have to be placed.
        $path_info = self::get_group_picture_path_by_id($group_id, 'system', true);

        $path = $path_info['dir'];
        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        // The old photos (if any).
        $old_file = $path_info['file'];

        // Let us delete them.
        if (!empty($old_file)) {
            if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
                $prefix = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_';
                @rename($path.'small_'.$old_file, $path.$prefix.'small_'.$old_file);
                @rename($path.'medium_'.$old_file, $path.$prefix.'medium_'.$old_file);
                @rename($path.'big_'.$old_file, $path.$prefix.'big_'.$old_file);
                @rename($path.$old_file, $path.$prefix.$old_file);
            } else {
                @unlink($path.'small_'.$old_file);
                @unlink($path.'medium_'.$old_file);
                @unlink($path.'big_'.$old_file);
                @unlink($path.$old_file);
            }
        }

        // Exit if only deletion has been requested. Return an empty picture name.
        if ($delete) {
            return '';
        }

        // Validation 2.
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file = str_replace('\\', '/', $file);
        $filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        if (!in_array($extension, $allowed_types)) {
            return false;
        }

        // This is the common name for the new photos.
        if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && !empty($old_file)) {
            $old_extension = strtolower(substr(strrchr($old_file, '.'), 1));
            $filename = in_array($old_extension, $allowed_types) ? substr($old_file, 0, -strlen($old_extension)) : $old_file;
            $filename = (substr($filename, -1) == '.') ? $filename.$extension : $filename.'.'.$extension;
        } else {
            $filename = api_replace_dangerous_char($filename);
            if (PREFIX_IMAGE_FILENAME_WITH_UID) {
                $filename = uniqid('').'_'.$filename;
            }
            // We always prefix user photos with user ids, so on setting
            // api_get_setting('split_users_upload_directory') === 'true'
            // the correspondent directories to be found successfully.
            $filename = $group_id.'_'.$filename;
        }

        // Storing the new photos in 4 versions with various sizes.

        $small = self::resize_picture($source_file, 22);
        $medium = self::resize_picture($source_file, 85);
        $normal = self::resize_picture($source_file, 200);

        $big = new Image($source_file); // This is the original picture.
        $ok = $small && $small->send_image($path.'small_'.$filename)
            && $medium && $medium->send_image($path.'medium_'.$filename)
            && $normal && $normal->send_image($path.'big_'.$filename)
            && $big && $big->send_image($path.$filename);

        return $ok ? $filename : false;
    }

    /**
     * Gets the group picture URL or path from group ID (returns an array).
     * The return format is a complete path, enabling recovery of the directory
     * with dirname() or the file with basename(). This also works for the
     * functions dealing with the user's productions, as they are located in
     * the same directory.
     * @internal Don't delete this function
     * @param	integer	$id
     * @param	string	$type Type of path to return (can be 'system', 'web')
     * @param	bool	$preview Whether we want to have the directory name returned 'as if' there was a file or not
     * (in the case we want to know which directory to create - otherwise no file means no split subdir)
     * @param	bool	$anonymous If we want that the function returns the /main/img/unknown.jpg image set it at true
     *
     * @return	array 	Array of 2 elements: 'dir' and 'file' which contain the dir and file as the name implies
     * if image does not exist it will return the unknow image if anonymous parameter is true if not it returns an empty
     */
    public static function get_group_picture_path_by_id($id, $type = 'web', $preview = false, $anonymous = false)
    {
        switch ($type) {
            case 'system': // Base: absolute system path.
                $base = api_get_path(SYS_UPLOAD_PATH);
                break;
            case 'web': // Base: absolute web path.
            default:
                $base = api_get_path(WEB_UPLOAD_PATH);
                break;
        }

        $noPicturePath = array('dir' => $base.'img/', 'file' => 'unknown.jpg');

        if (empty($id) || empty($type)) {
            return $anonymous ? $noPicturePath : array('dir' => '', 'file' => '');
        }

        $id = intval($id);

        $group_table = Database :: get_main_table(TABLE_MAIN_GROUP);
        $sql = "SELECT picture_uri FROM $group_table WHERE id=".$id;
        $res = Database::query($sql);

        if (!Database::num_rows($res)) {
            return $anonymous ? $noPicturePath : array('dir' => '', 'file' => '');
        }

        $user = Database::fetch_array($res);
        $picture_filename = trim($user['picture_uri']);

        if (api_get_setting('split_users_upload_directory') === 'true') {
            if (!empty($picture_filename)) {
                $dir = $base.'groups/'.substr($picture_filename, 0, 1).'/'.$id.'/';
            } elseif ($preview) {
                $dir = $base.'groups/'.substr((string) $id, 0, 1).'/'.$id.'/';
            } else {
                $dir = $base.'groups/'.$id.'/';
            }
        } else {
            $dir = $base.'groups/'.$id.'/';
        }

        if (empty($picture_filename) && $anonymous) {
            return $noPicturePath;
        }

        return array('dir' => $dir, 'file' => $picture_filename);
    }

    /**
     * Resize a picture
     *
     * @param  string file picture
     * @param  int size in pixels
     * @return obj image object
     */
    public static function resize_picture($file, $max_size_for_picture)
    {
        $temp = new Image($file);
        $picture_infos = api_getimagesize($file);
        if ($picture_infos['width'] > $max_size_for_picture) {
            $thumbwidth = $max_size_for_picture;
            if (empty($thumbwidth) or $thumbwidth == 0) {
                $thumbwidth = $max_size_for_picture;
            }
            $new_height = round(($thumbwidth / $picture_infos['width']) * $picture_infos['height']);
            if ($new_height > $max_size_for_picture)
                $new_height = $thumbwidth;
            $temp->resize($thumbwidth, $new_height, 0);
        }

        return $temp;
    }

    /**
     * Gets the current group image
     * @param string $id group id
     * @param string $picture_file picture group name
     * @param string $height
     * @param string $size_picture picture size it can be small_, medium_or big_
     * @param string $style css
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
    public static function get_picture_group(
        $id,
        $picture_file,
        $height,
        $size_picture = GROUP_IMAGE_SIZE_MEDIUM,
        $style = ''
    ) {
        $picture = array();
        $picture['style'] = $style;
        if ($picture_file == 'unknown.jpg') {
            $picture['file'] = api_get_path(WEB_CODE_PATH).'img/'.$picture_file;
            return $picture;
        }

        switch ($size_picture) {
            case GROUP_IMAGE_SIZE_ORIGINAL:
                $size_picture = '';
                break;
            case GROUP_IMAGE_SIZE_BIG:
                $size_picture = 'big_';
                break;
            case GROUP_IMAGE_SIZE_MEDIUM:
                $size_picture = 'medium_';
                break;
            case GROUP_IMAGE_SIZE_SMALL:
                $size_picture = 'small_';
                break;
            default:
                $size_picture = 'medium_';
        }

        $image_array_sys = self::get_group_picture_path_by_id($id, 'system', false, true);
        $image_array = self::get_group_picture_path_by_id($id, 'web', false, true);
        $file = $image_array_sys['dir'].$size_picture.$picture_file;
        if (file_exists($file)) {
            $picture['file'] = $image_array['dir'].$size_picture.$picture_file;
            $picture['style'] = '';
            if ($height > 0) {
                $dimension = api_getimagesize($picture['file']);
                $margin = (($height - $dimension['width']) / 2);
                //@ todo the padding-top should not be here
                $picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension['width'].'px; height:'.$dimension['height'].';" ';
            }
        } else {
            $file = $image_array_sys['dir'].$picture_file;
            if (file_exists($file) && !is_dir($file)) {
                $picture['file'] = $image_array['dir'].$picture_file;
            } else {
                $picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown_group.png';
            }
        }
        return $picture;
    }

    /**
     * @param int $group_id
     * @return string
     */
    public static function delete_group_picture($group_id)
    {
        return self::update_group_picture($group_id);
    }

    /**
     * @param int $group_id
     * @param int $user_id
     * @return bool
     */
    public static function is_group_admin($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = GroupPortalManager::get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $group_id
     * @param int $user_id
     * @return bool
     */
    public static function is_group_moderator($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = GroupPortalManager::get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $group_id
     * @param int $user_id
     * @return bool
     */
    public static function is_group_member($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = GroupPortalManager::get_user_group_role($user_id, $group_id);
        $permissions = array(
            GROUP_USER_PERMISSION_ADMIN,
            GROUP_USER_PERMISSION_MODERATOR,
            GROUP_USER_PERMISSION_READER,
            GROUP_USER_PERMISSION_HRM
        );

        if (in_array($user_role, $permissions)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Shows the left column of the group page
     * @param int $group_id
     * @param int $user_id
     *
     */
    public static function show_group_column_information($group_id, $user_id, $show = '')
    {
        global $relation_group_title, $my_group_role;
        $html = '';

        $group_info = GroupPortalManager::get_group_data($group_id);
        // My relation with the group is set here.
        $my_group_role = self::get_user_group_role($user_id, $group_id);

        //@todo this must be move to default.css for dev use only
        $html .= '<style>
				#group_members { width:270px; height:300px; overflow-x:none; overflow-y: auto;}
				.group_member_item { width:100px; height:130px; float:left; margin:5px 5px 15px 5px; }
				.group_member_picture { display:block;
					margin:0;
					overflow:hidden; };
		</style>';

        //Loading group permission

        $links = '';
        switch ($my_group_role) {
            case GROUP_USER_PERMISSION_READER:
                // I'm just a reader
                $relation_group_title = get_lang('IAmAReader');
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace' => '6')).'<span class="'.($show == 'invite_friends' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                if (GroupPortalManager::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace' => '6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_ADMIN:
                $relation_group_title = get_lang('IAmAnAdmin');
                $links .= '<li><a href="group_edit.php?id='.$group_id.'">'.Display::return_icon('group_edit.png', get_lang('EditGroup'), array('hspace' => '6')).'<span class="'.($show == 'group_edit' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('EditGroup').'</span></a></li>';
                $links .= '<li><a href="group_waiting_list.php?id='.$group_id.'">'.Display::return_icon('waiting_list.png', get_lang('WaitingList'), array('hspace' => '6')).'<span class="'.($show == 'waiting_list' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('WaitingList').'</span></a></li>';
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace' => '6')).'<span class="'.($show == 'invite_friends' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                if (GroupPortalManager::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace' => '6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION:
//				$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('YouHaveBeenInvitedJoinNow'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouHaveBeenInvitedJoinNow').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
                $relation_group_title = get_lang('WaitingForAdminResponse');
                break;
            case GROUP_USER_PERMISSION_MODERATOR:
                $relation_group_title = get_lang('IAmAModerator');
                if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
                    $links .= '<li><a href="group_waiting_list.php?id='.$group_id.'">'.Display::return_icon('waiting_list.png', get_lang('WaitingList'), array('hspace' => '6')).'<span class="'.($show == 'waiting_list' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('WaitingList').'</span></a></li>';
                }
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace' => '6')).'<span class="'.($show == 'invite_friends' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                if (GroupPortalManager::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace' => '6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_HRM:
                $relation_group_title = get_lang('IAmAHRM');
                $links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="ajax" data-size="lg" data-title="'.get_lang('ComposeMessage').' title="'.get_lang('ComposeMessage').'">'.Display::return_icon('new-message.png', get_lang('NewTopic'), array('hspace' => '6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
                $links .= '<li><a href="group_view.php?id='.$group_id.'">'.Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace' => '6')).'<span class="'.($show == 'messages_list' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace' => '6')).'<span class="'.($show == 'invite_friends' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                $links .= '<li><a href="group_members.php?id='.$group_id.'">'.Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace' => '6')).'<span class="'.($show == 'member_list' ? 'social-menu-text-active' : 'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
                $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.Display::return_icon('delete_data.gif', get_lang('LeaveGroup'), array('hspace' => '6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                break;
            default:
                //$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('JoinGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('JoinGroup').'</a></span></li>';
                break;
        }

        if (!empty($links)) {
            $html .= '<div class="well sidebar-nav"><ul class="nav nav-list">';
            if (!empty($group_info['description'])) {
                $html .= Display::tag('li', Security::remove_XSS($group_info['description'], STUDENT, true), array('class' => 'group_description'));
            }
            $html .= $links;
            $html .= '</ul></div>';
        }
        return $html;
    }

    /**
     * @param int $group_id
     * @param int $topic_id
     */
    function delete_topic($group_id, $topic_id)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $topic_id = intval($topic_id);
        $group_id = intval($group_id);
        $sql = "UPDATE $table_message SET msg_status=3
                WHERE group_id = $group_id AND (id = '$topic_id' OR parent_id = $topic_id) ";
        Database::query($sql);
    }

    /**
     * @param int  $user_id
     * @param int  $relation_type
     * @param bool $with_image
     * @return int
     */
    public static function get_groups_by_user_count($user_id = null, $relation_type = GROUP_USER_PERMISSION_READER, $with_image = false)
    {
        $table_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$tbl_group				= Database::get_main_table(TABLE_MAIN_GROUP);
		$user_id = intval($user_id);

		if ($relation_type == 0) {
			$where_relation_condition = '';
		} else {
			$relation_type 			= intval($relation_type);
			$where_relation_condition = "AND gu.relation_type = $relation_type ";
		}

		$sql = "SELECT count(g.id) as count
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.group_id = g.id WHERE gu.user_id = $user_id $where_relation_condition ";

		$result = Database::query($sql);
		if (Database::num_rows($result) > 0) {
			$row = Database::fetch_array($result, 'ASSOC');
            return $row['count'];
		}
		return 0;
    }

    /**
     * @param FormValidator $form
     * @param array
     *
     * @return FormValidator
     */
    public static function setGroupForm($form, $groupData = array())
    {
        // Name
        $form->addElement('text', 'name', get_lang('Name'), array('maxlength'=>120));
        $form->applyFilter('name', 'html_filter');
        $form->applyFilter('name', 'trim');
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        // Description
        $form->addElement(
            'textarea',
            'description',
            get_lang('Description'),
            array(
                'cols' => 58,
                'onKeyDown' => "textarea_maxlength()",
                'onKeyUp' => "textarea_maxlength()",
            )
        );
        $form->applyFilter('description', 'html_filter');
        $form->applyFilter('description', 'trim');
        $form->addRule('name', '', 'maxlength', 255);

        // Url
        $form->addElement('text', 'url', 'URL');
        $form->applyFilter('url', 'html_filter');
        $form->applyFilter('url', 'trim');

        // Picture
        $form->addElement('file', 'picture', get_lang('AddPicture'));
        $allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
        $form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

        if (!empty($groupData)) {
            if (isset($groupData['picture_uri']) && strlen($groupData['picture_uri']) > 0) {
                $form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));
            }
        }

        // Status
        $status = array();
        $status[GROUP_PERMISSION_OPEN] = get_lang('Open');
        $status[GROUP_PERMISSION_CLOSED] = get_lang('Closed');
        $form->addElement('select', 'visibility', get_lang('GroupPermissions'), $status, array());

        if (!empty($groupData)) {
            if (self::canLeaveFeatureEnabled($groupData)) {
                $form->addElement('checkbox', 'allow_members_leave_group', '', get_lang('AllowMemberLeaveGroup'));
            }
            // Set default values
            $form->setDefaults($groupData);
        }

        return $form;
    }

    /**
     * Check if the can leave feature exists.
     * @param array $groupData
     * @return bool
     */
    public static function canLeaveFeatureEnabled($groupData)
    {
        if (isset($groupData['allow_members_leave_group'])) {
            return true;
        }
        return false;
    }

    /**
     * @param array $groupData
     * @return bool
     */
    public static function canLeave($groupData)
    {
        if (self::canLeaveFeatureEnabled($groupData)) {
            return $groupData['allow_members_leave_group'] == 1 ? true : false;
        }
        return true;
    }

    /**
     * Get the group member list by a user and his group role
     * @param int $userId The user ID
     * @param int $relationType Optional. The relation type. GROUP_USER_PERMISSION_ADMIN by default
     * @param boolean $includeSubgroupsUsers Optional. Whether include the users from subgroups
     * @return array
     */
    public static function getGroupUsersByUser(
        $userId,
        $relationType = GROUP_USER_PERMISSION_ADMIN,
        $includeSubgroupsUsers = true
    )
    {
        $userId = intval($userId);

        $groups = GroupPortalManager::get_groups_by_user($userId, $relationType);

        $groupsId = array_keys($groups);
        $subgroupsId = [];
        $userIdList = [];

        if ($includeSubgroupsUsers) {
            foreach ($groupsId as $groupId) {
                $subgroupsId = array_merge($subgroupsId, GroupPortalManager::getGroupsByDepthLevel($groupId));
            }

            $groupsId = array_merge($groupsId, $subgroupsId);
        }

        $groupsId = array_unique($groupsId);

        if (empty($groupsId)) {
            return [];
        }

        foreach ($groupsId as $groupId) {
            $groupUsers = GroupPortalManager::get_users_by_group($groupId);

            if (empty($groupUsers)) {
                continue;
            }

            foreach ($groupUsers as $member) {
                if ($member['user_id'] == $userId) {
                    continue;
                }

                $userIdList[] = intval($member['user_id']);
            }
        }

        return array_unique($userIdList);
    }

}
