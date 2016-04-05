<?php
/* For license terms, see /license.txt */
/**
 * Errors management for the Buy Courses plugin - Redirects to course_catalog.php or service_catalog.php
 * @package chamilo.plugin.buycourses
 */
/**
 * Config
 */

if ($_SESSION['bc_service_sale_id']) {
   unset($_SESSION['bc_service_sale_id']);
   header('Location: service_catalog.php');
}

if ($_SESSION['bc_sale_id']) {
   unset($_SESSION['bc_sale_id']);
   header('Location: course_catalog.php');
}