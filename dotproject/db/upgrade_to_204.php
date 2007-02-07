<?php

if (! defined('DP_BASE_DIR')) {
	die("You must not call this file directly, it is run automatically on install/upgrade");
}
include_once DP_BASE_DIR."/includes/config.php";
include_once DP_BASE_DIR."/includes/main_functions.php";
require_once DP_BASE_DIR."/includes/db_adodb.php";
include_once DP_BASE_DIR."/includes/db_connect.php";
include_once DP_BASE_DIR."/install/install.inc.php";

/**
 * DEVELOPERS PLEASE NOTE:
 *
 * For the new upgrader/installer to work, this code must be structured
 * correctly.  In general if there is a difference between the from
 * version and the to version, then all updates should be performed.
 * If the $last_udpated is set, then a partial update is required as this
 * is a CVS update.  Make sure you create a new case block for any updates
 * that you require, and set $latest_update to the date of the change.
 *
 * Each case statement should fall through to the next, so that the
 * complete update is run if the last_updated is not set.
 */
function dPupgrade($from_version, $to_version, $last_updated)
{

	$latest_update = '20060624'; // Set to the latest upgrade date.

	/**
	 *  This segment will extract all the project/department and project/contact relational info and populate the project_departments and project_contacts tables.
	 **/
	if ($from_version != $to_version || $last_updated == '')
		$last_updated = '00000000';

	switch ($last_updated) {
		case '00000000':
			$sql = "SELECT project_id, project_departments, project_contacts FROM projects";
			$projects = db_loadList( $sql );

			//split out related departments and store them seperatly.
			$sql = 'DELETE FROM project_departments';
			db_exec( $sql );
			//split out related contacts and store them seperatly.
			$sql = 'DELETE FROM project_contacts';
			db_exec( $sql );

			foreach ($projects as $project){
                $p_id = (($project['project_id'])?$project['project_id']:'0');
				$departments = explode(',',$project['project_departments']);
				foreach($departments as $department){
					$sql = 'INSERT INTO project_departments (project_id, department_id) values ('.$p_id.', '.$department.')';
                    if ($p_id && $department) {
                        db_exec( $sql );
                    }
				}

				$contacts = explode(',',$project['project_contacts']);
				foreach($contacts as $contact){
					$sql = 'INSERT INTO project_contacts (project_id, contact_id) values ('.$p_id.', '.$contact.')';
                    if ($p_id && $contact) {
                        db_exec( $sql );
                    }
				}
			}

			/**
			 *  This segment will extract all the task/department and task/contact relational info and populate the task_departments and task_contacts tables.
			 **/

			$sql = "SELECT task_id, task_departments, task_contacts FROM tasks";
			$tasks = db_loadList( $sql );

			//split out related departments and store them seperatly.
			$sql = 'DELETE FROM task_departments';
			db_exec( $sql );
			//split out related contacts and store them seperatly.
			$sql = 'DELETE FROM task_contacts';
			db_exec( $sql );

			foreach ($tasks as $task){
				$departments = explode(',',$task['task_departments']);
				foreach($departments as $department){
					if ($department) {
						$sql = 'INSERT INTO task_departments (task_id, department_id) values ('.$task['task_id'].', '.$department.')';
						db_exec( $sql );
					}
				}

				$contacts = explode(',',$task['task_contacts']);
				foreach($contacts as $contact){
					if ($contact) {
						$sql = 'INSERT INTO task_contacts (task_id, contact_id) values ('.$task['task_id'].', '.$contact.')';
						db_exec( $sql );
					}
				}
			}
            
            $sql = "ALTER TABLE `projects` ADD `project_active` TINYINT(4) DEFAULT 1";
            db_exec( $sql );
            
			include DP_BASE_DIR."/db/upgrade_contacts.php";
			include DP_BASE_DIR."/db/upgrade_permissions.php";

			// Fallthrough
		case '20050314':
			// Add the permissions for task_log
			dPmsg("Adding Task Log permissions");
			$perms =& new dPacl;
			$perms->add_object('app', 'Task Logs', 'task_log', 11, 0, 'axo');
			$all_mods = $perms->get_group_id('all', null, 'axo');
			$nonadmin = $perms->get_group_id('non_admin', null, 'axo');
			$perms->add_group_object($all_mods, 'app', 'task_log', 'axo');
			$perms->add_group_object($nonadmin, 'app', 'task_log', 'axo');
		case '20050316':
			include DP_BASE_DIR."/db/upgrade_contacts_company.php";
		default:
			break;
	}
	
	return $latest_update;
}
 
?>