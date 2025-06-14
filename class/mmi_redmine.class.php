<?php

use Sabre\VObject\Property\Time;

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/timespent.class.php';

class mmi_redmine
{

	protected static $api_url;
	protected static $api_token;

	protected static $_activities = [];
	protected static $_users = [];

	protected static $_sync_id;

	public static function __init()
	{
		static::$api_url = getDolGlobalString('MMI_REDMINE_URL');
		static::$api_token = getDolGlobalString('MMI_REDMINE_TOKEN');

		static::_activities_load();
		static::_users_load();
	}

	// Metadata

	/**
	 * Load activities mapping
	 */
	public static function _activities_load()
	{
		global $db, $user;

		// Retrieve Dolibarr activities
		$sql = 'SELECT *'
			.' FROM `'.MAIN_DB_PREFIX.'mmi_redmine_time_entry_activities`';
		$q = $db->query($sql);
		//var_dump($sql, $q);
		//static::$_activities = [];
		if ($q) {
			while($res = $q->fetch_object()) {
				static::$_activities[$res->redmine_id] = $res;
			}
		}
		else {
			dol_print_error($db);
			return false;
		}
	}

	/**
	 * Load user mapping
	 */
	public static function _users_load()
	{
		global $db, $user;

		// Retrieve Dolibarr activities
		$sql = 'SELECT fk_object AS rowid, redmine_id'
			.' FROM `'.MAIN_DB_PREFIX.'user_extrafields`'
			.' WHERE redmine_id IS NOT NULL AND redmine_id != ""';
		$q = $db->query($sql);
		//var_dump($sql, $q);
		//static::$_activities = [];
		if ($q) {
			while($res = $q->fetch_object()) {
				static::$_users[$res->redmine_id] = $res->rowid;
			}
		}
		else {
			dol_print_error($db);
			return false;
		}
	}

	/**
	 * Load issues mapping for a specific project
	 */
	public static function _issues_map($project_id)
	{
		global $db, $user;
		$issues_map = [];
		$sql = 'SELECT `rowid`, `import_key`'
			.' FROM `'.MAIN_DB_PREFIX.'projet_task`'
			.' WHERE `import_key` LIKE "redmine_%"'
			.' AND `fk_projet`='.$project_id;
		$q = $db->query($sql);
		if ($q) {
			while($res = $q->fetch_object()) {
				//var_dump($res);
				if (preg_match('/^redmine_(\d+)$/', $res->import_key, $matches)) {
					$issues_map[$matches[1]] = $res->rowid;
				}
				elseif (preg_match('/^redmine_(\d+)_notask$/', $res->import_key, $matches)) {
					$issues_map['notask'] = $res->rowid;
				}
			}
		}
		return $issues_map;
	}

	// Sync	

	/**
	 * Set a sync entry in the database
	 * @param bool $reset If true, reset the sync entry
	 * @return bool True on success, false on failure
	 */
	public static function _sync_set($reset=true)
	{
		global $db, $user;

		if (!empty(static::$_sync_id) && !$reset) {
			// Already set
			return true;
		}

		// Create sync entry
		$sql = 'INSERT INTO `'.MAIN_DB_PREFIX.'mmi_redmine_sync`'
			.' (`datec`, `fk_user_create`)'
			.' VALUES (NOW(), '.$user->id.')';
		if (!$db->query($sql)) {
			dol_print_error($db, 'Error creating sync entry: '.$db->lasterror());
			return false;
		}
		static::$_sync_id = $db->last_insert_id(MAIN_DB_PREFIX.'mmi_redmine_sync');
		
		return true;
	}

	public static function sync_activities()
	{
		global $db, $user;

		// Retrieve Redmine activities
		$r_activities = static::api_time_entry_activities();
		var_dump($r_activities);
		if (!is_array($r_activities)) {
			dol_print_error($db, 'Error in Redmine API response: '.json_encode($r_activities));
			return false;
		}
		elseif (empty($r_activities)) {
			dol_print_error($db, 'No time entry activities found in Redmine');
			//return;
		}

		// Sync activities
		$r_activities_ids = [];
		foreach($r_activities as $r_activity) {
			var_dump($r_activity);
			$r_activities_ids[] = $r_activity->id;
			if (!isset(static::$_activities[$r_activity->id])) {
				// Create activity
				$sql = 'INSERT INTO `'.MAIN_DB_PREFIX.'mmi_redmine_time_entry_activities`'
					.' (`code`, `redmine_id`, `label`, `active`)'
					.' VALUES ("RA'.$r_activity->id.'", '.$r_activity->id.', "'.$r_activity->name.'", '.$r_activity->active.')';
				var_dump($sql);
				if (!$db->query($sql)) {
					dol_print_error($db, 'Error creating activity: '.$db->lasterror());
				}
			}
			else {
				// Update activity
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.'mmi_redmine_time_entry_activities`'
					.' SET `label`="'.$r_activity->name.'", active='.$r_activity->active
					.' WHERE `redmine_id`='.$r_activity->id;
				if (!$db->query($sql)) {
					dol_print_error($db, 'Error updating activity: '.$db->lasterror());
				}
			}
		}
		// Remove activities
		foreach(static::$_activities as $activity) {
			if (!in_array($activity->redmine_id, $r_activities_ids)) {
				// Disable activity
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.'mmi_redmine_time_entry_activities`'
					.' SET `active`=0'
					.' WHERE `redmine_id`='.$activity->redmine_id.' AND `active`=1';
				if (!$db->query($sql)) {
					dol_print_error($db, 'Error disabling activity: '.$db->lasterror());
				}
			}
		}
		var_dump('DONE');

		// Reload activities
		static::_activities_load();
	}

	/**
	 * Synchronise Redmine projects with Dolibarr, from last sync date
	 */
	public static function sync_last($project_ids=NULL)
	{
		global $db, $user;

		// Choper la date de denière synchro

		if (is_numeric($project_ids))
			$project_ids = [$project_ids];
		elseif (!is_array($project_ids) && $project_ids !== NULL) {
			dol_print_error($db, 'No project ids provided for synchronization');
			return;
		}

		// If no projects provided,
		// Sync all projects already synced
		if ($project_ids === NULL) {
			// Retrieve all projects
			$sql = 'SELECT p2.fk_object'
				.' FROM `'.MAIN_DB_PREFIX.'projet_extrafields` p2'
				.' WHERE p2.redmine_id IS NOT NULL AND p2.redmine_id != ""';
			$q = $db->query($sql);
			if ($q) {
				$project_ids = [];
				while($res = $q->fetch_object()) {
					$project_ids[] = $res->fk_object;
				}
			}
			else {
				dol_print_error($db);
				return;
			}
		}

		static::_sync_set();

		foreach($project_ids as $project_id) {
			/**
			 * Retrieve last synchronization date for a specific project
			 */
			$sql = 'SELECT rp.`fk_projet`, r.`datec`, p2.`redmine_id`'
				.' FROM `'.MAIN_DB_PREFIX.'projet_extrafields` p2'
				.' LEFT JOIN `'.MAIN_DB_PREFIX.'mmi_redmine_sync_project` rp ON rp.`fk_projet`=p2.`fk_object`'
				.' LEFT JOIN `'.MAIN_DB_PREFIX.'mmi_redmine_sync` r ON r.`rowid`=rp.`fk_mmi_redmine_sync`'
				.' WHERE p2.fk_object='.$project_id;
			$q = $db->query($sql);
			if ($q) {
				$res = $q->fetch_object();
				if ($res) {
					// Project already synchronized
					if (!empty($res->redmine_id)) {
						//var_dump($res->redmine_id);
						static::sync_r_project($res->redmine_id, $res->datec);
					}
					else {
						dol_print_error($db, 'Project id '.$project_id.' not synchronised from Redmine');
					}
				}
				else {
					// Pas de date de dernière synchro
					dol_print_error($db, 'Project id '.$project_id.' not found in Dolibarr');
				}
			}
			else {
				dol_print_error($db);
			}
		}
	}

	/**
	 * Synchronise all redmine projects in Dolibarr and create Projects (in Dolibarr) if not already synced
	 */
	public static function sync_all($date_from=NULL)
	{
		global $db, $user;

		$r_projects = static::api_projects();

		if (!is_array($r_projects)) {
			dol_print_error($db, 'Error in Redmine API response: '.json_encode($r_projects));
			return false;
		}
		elseif (empty($r_projects)) {
			dol_print_error($db, 'No projects found in Redmine');
			return;
		}

		static::_sync_set();

		// On boucle sur les projets
		foreach($r_projects as $r_project) {
			// On synchronise le projet
			static::sync_project($r_project->id, $date_from);
		}
	}

	/**
	 * Synchronize Redmine projects, issues and time entries with Dolibarr.
	 *
	 * @param []int|int|NULL $project_ids List of project IDs to filter (optional).
	 *                           If NULL, all projects will be synchronized.
	 *                           If an array, only the specified projects will be synchronized.
	 *                           If an integer, only the specified project will be synchronized.
	 * @param date $date_from (optional).
	 * @return void
	 */
	public static function sync_project($project_id, $date_from=NULL)
	{
		global $db, $user;

		$project = new Project($db);
		$project->fetch($project_id);
		//var_dump($project);
		// On récupère l'id du projet Redmine
		if (empty($project->array_options['options_redmine_id']))
			return false;

		// Redmine Project
		$r_project = static::api_project($project->array_options['options_redmine_id']);
		//var_dump($r_project);
		if (empty($r_project))
			return false;

		static::sync_r_project($r_project, $date_from);
	}

	/**
	 * Synchronize all issues and time from a Redmine project, from a specific date, and create project in Dolibarr if not already synced.
	 * 
	 * @param $r_project Redmine project object or id
	 */
	public static function sync_r_project($r_project, $date_from=NULL, $autocreate=NULL)
	{
		global $user, $db;
		//var_dump($user);

		if (is_null($autocreate)) {
			$autocreate = getDolGlobalInt('MMI_REDMINE_PROJECT_AUTOCREATE');
		}

		if (is_numeric($r_project)) {
			$r_project = static::api_project($r_project);
			//var_dump($r_project);
			if (empty($r_project))
				return false;
			$r_project_id = $r_project->id;
		}
		//var_dump($r_project_id);

		// Test déjà synchronisé
		$sql = 'SELECT p.`rowid`'
			.' FROM `'.MAIN_DB_PREFIX.'projet` AS p'
			.' INNER JOIN `'.MAIN_DB_PREFIX.'projet_extrafields` AS p2 ON p2.`fk_object`=p.`rowid`'
			.' WHERE p2.`redmine_id`='.$r_project->id;
		$q = $db->query($sql);
		//var_dump($sql, $q);

		static::_sync_set(false);

		// Pas synchronisé => Créer projet
		if (!list($project_id)=$q->fetch_row()) {
			if (!$autocreate) {
				dol_print_error($db, 'Project not found in Dolibarr');
				return false;
			}

			$sql = 'SELECT MAX(`rowid`) FROM `'.MAIN_DB_PREFIX.'projet`';
			$q2 = $db->query($sql);
			if ($q2) {
				$rowid = $q2->fetch_row()[0];
				$rowid++;
			}
			else {
				$rowid = 1;
			}
			$project = new Project($db);
			$project->ref = '(PROV'.($rowid).')';
			$project->title = $r_project->name;
			$project->description = $r_project->description;
			$project->array_options['options_redmine_id'] = $r_project->id;
			$res = $project->create($user);
			$project_id = $project->id;
			//var_dump($project_id, $project->error, $res, $project);
			if ($res < 0) {
				dol_print_error($db, 'Error creating project: '.$project->error);
				return false;
			}
		}
		else {
			$project = new Project($db);
			$project->fetch($project_id);
		}
		//var_dump($task_id); die();
		//$project->fetch()
		// Test si modifié depuis dernière synchro
		$n2 = 0;
		$r_issues = static::api_issues(['project_id'=>$r_project->id, 'status_id'=>'*', 'offset'=>0, 'limit'=>1000]);
		foreach($r_issues as $r_issue) {
			$n2++;
			//var_dump($r_issue);
			// Test déjà synchronisé
			$sql = 'SELECT `rowid`'
				.' FROM `'.MAIN_DB_PREFIX.'projet_task`'
				.' WHERE `import_key`="redmine_'.$r_issue->id.'"';
			//echo $sql;
			$q = $db->query($sql);
			$task = new Task($db);

			// Pas synchronisé => Créer
			if (!list($task_id)=$q->fetch_row()) {
				//$project->ref = '(PROV'.($rowid++).')';
				$task->label = $r_issue->subject;
				$task->fk_project = $project_id;
				$task->import_key = 'redmine_'.$r_issue->id;
				$res = $task->create($user);
				if ($res < 0) {
					dol_print_error($db, 'Error creating Task: '.$task->error);
					return false;
				}
				$task_id = $task->id;
				//var_dump($task_id, $task->error, $res, $task);
				// Temp fix
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.'projet_task`'
					.' SET `import_key`="'.$task->import_key.'"'
					.' WHERE `rowid`='.$task_id;
				$res = $db->query($sql);
				//var_dump($res);
			}
			// else {
			// 	$task->fetch($task_id);
			// }
			
			// Update
			// $task->element_date = $time_entry->spent_on;
			// $task->element_datehour = $time_entry->spent_on.' 00:00:00';
			// $task->element_duration = $time_entry->hours	;
			// $res = $task->update($user);
			// var_dump($res);
		}

		$r_issues_tasks = static::_issues_map($project_id);

		//var_dump($task_id); die();
		//$project->fetch()
		// @todo : Test si modifié depuis dernière synchro
		$n2 = 0;
		$t_nb = 0;

		$time_entry = new TimeSpent($db);
		$time_entry->elementtype = 'task';

		$r_time_entries = static::api_time_entries(['project_id'=>$r_project->id, 'from'=>substr($date_from, 0, 10), 'offset'=>0, 'limit'=>1000]);
		//var_dump($r_time_entries);
		foreach($r_time_entries as $r_time_entry) {
			$n2++;

			//var_dump($r_time_entry);
			if (!empty($r_time_entry->issue->id)) {
				$task_id = $r_issues_tasks[$r_time_entry->issue->id];
				//var_dump($task_id);
			}
			else {
				if (isset($r_issues_tasks['notask'])) {
					$task_id = $r_issues_tasks['notask'];
				}
				else {
					$sql = 'SELECT `rowid`'
						.' FROM `'.MAIN_DB_PREFIX.'projet_task`'
						.' WHERE `import_key`="redmine_'.$project_id.'_notask"'
						.' AND `fk_projet`='.$project_id;
					$q = $db->query($sql);
					//var_dump($sql, $q);
					// ALREADY EXISTS
					if (list($task_id)=$q->fetch_row()) {
						// Task already exists
						$notask = new Task($db);
						$notask->fetch($task_id);
						$project->notask_id = $task_id;
					}
					else {
						// Create task NOTASK
						$notask = new Task($db);
						$notask->label = 'NOTASK';
						$notask->fk_project = $project_id;
						$notask->import_key = 'redmine_'.$project_id.'_notask';
						$res = $notask->create($user);
						if ($res < 0) {
							dol_print_error($db, 'Error creating Task for redmine time entries w/o issue: '.$task->error);
							return false;
						}
						$project->notask_id = $notask->id;
					}
					$r_issues_tasks['notask'] = $notask->id;
					$task_id = $notask->id;
				}
			}
			//var_dump($task_id);

			// Test déjà synchronisé
			$sql = 'SELECT `rowid`'
				.' FROM `'.MAIN_DB_PREFIX.'element_time`'
				.' WHERE `import_key`="redmine_'.$r_time_entry->id.'"';
			$q = $db->query($sql);
			//var_dump($sql, $q);

			// Pas synchronisé => Créer
			if (!list($time_entry_id)=$q->fetch_row()) {
				//$project->ref = '(PROV'.($rowid++).')';
				// Retrieve task_id already synced, otherwise use default task
				$time_entry->fk_element = $task_id;
				$time_entry->import_key = 'redmine_'.$r_time_entry->id;
				$time_entry->fk_user = $user->id;
				$res = $time_entry->create($user);
				if ($res < 0) {
					dol_print_error($db, 'Error creating time entry : '.$time_entry->error);
					return false;
				}
				$time_entry_id = $time_entry->id;
				//var_dump($time_entry_id, $time_entry->error, $res, $time_entry);
				// Temp fix
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.'element_time`'
					.' SET `import_key`="'.$time_entry->import_key.'"'
					.' WHERE `rowid`='.$time_entry_id;
				$res = $db->query($sql);
				//var_dump($res);
			}
			else {
				$time_entry->fetch($time_entry_id);
			}
			
			// Update If changed
			$time_entry->element_date = $r_time_entry->spent_on;
			$time_entry->element_datehour = $r_time_entry->spent_on.' 00:00:00';
			$time_entry->note = $r_time_entry->comments;
			//$time_entry->thm = 35;
			//$time_entry->array_options['options_redmine_activity_id'] = static::$_activities[$r_time_entry->activity->id]->rowid;
			$time_entry->fk_product = static::$_activities[$r_time_entry->activity->id]->fk_product;
			$time_entry->element_duration = round($r_time_entry->hours*3600); // Convert to seconds
			$time_entry->fk_element = $task_id;
			$time_entry->fk_user = static::$_users[$r_time_entry->user->id] ?: $user->id;
			//var_dump($time_entry->fk_user);
			$res = $time_entry->update($user);
			if ($res < 0) {
				dol_print_error($db, 'Error updating time entry : '.$time_entry->error);
				return false;
			}
			$t_nb++;
			var_dump($t_nb);
			//var_dump($res);
		}

		// Adds entry for project synchronisation
		$sql = 'INSERT INTO `'.MAIN_DB_PREFIX.'mmi_redmine_sync_project`'
			.' (`fk_mmi_redmine_sync`, `fk_projet`, `time_nb`)'
			.' VALUES ('.static::$_sync_id.', '.$project_id.', '.$t_nb.')';
		$res = $db->query($sql);
		if (!$res) {
			dol_print_error($db, 'Error creating sync entry for project: '.$db->lasterror());
			return false;
		}

		return true;
		//var_dump($res);

		//var_dump($sync_id);
	}

	/* API Queries */

	public static function api_query($url, $action='GET', $params=[])
	{
		// @todo PHP Curl
		$url_params = [];
		foreach($params as $n=>$v)
			$url_params[] = $n.'='.$v;
		$cmd = 'curl -v -H "Content-Type: application/json" -X '.$action.' -H "X-Redmine-API-Key: '.static::$api_token.'" "'.static::$api_url.'/'.$url.'.json'.(!empty($url_params) ?'?'.implode('&', $url_params) :'').'"';
		echo $cmd;
		$a = exec($cmd, $res);
		$data = json_decode($res[0]);
		return $data;
	}

	// Projects

	public static function api_time_entry_activities($params=[])
	{
		return static::api_query('enumerations/time_entry_activities', 'GET')->time_entry_activities;
	}

	public static function api_projects($params=[])
	{
		return static::api_query('projects', 'GET')->projects;
	}

	public static function api_project($id)
	{
		if (!empty($res=static::api_query('projects/'.$id, 'GET')))
			return $res->project;
		else
			return false;
	}

	// Issues

	public static function api_issues($params=[])
	{
		return static::api_query('issues', 'GET', $params)->issues;
	}

	public static function api_issue($id)
	{
		return static::api_query('issue/'.$id, 'GET');
	}

	// Time

	public static function api_time_entries($params=[])
	{
		return static::api_query('time_entries', 'GET', $params)->time_entries;
	}

	public static function api_time_entry($id)
	{
		return static::api_query('time_entries/'.$id, 'GET');
	}
}

mmi_redmine::__init();
