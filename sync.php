<?php

require_once('env.inc.php');
require_once('main_load.inc.php');

dol_include_once('mmiredmine/class/mmi_redmine.class.php');

if (GETPOST('all')) {
	mmi_redmine::sync_all();
}
elseif ($project_ids = GETPOST('project_ids')) {
	var_dump($project_ids);
	mmi_redmine::sync_last($project_ids);
}
elseif ($project_id = GETPOSTINT('project_id')) {
	var_dump($project_id);
	mmi_redmine::sync_last($project_id);
}