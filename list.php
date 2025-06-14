<?php

require_once('env.inc.php');
require_once('main_load.inc.php');

dol_include_once('mmiredmine/class/mmi_redmine.class.php');

$r_project = mmi_redmine::api_project(17);
var_dump($r_project);

$list = mmi_redmine::api_projects($filters);
var_dump($list);

// $list = mmi_redmine::issues();
// var_dump($list);
