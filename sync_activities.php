<?php

require_once('env.inc.php');
require_once('main_load.inc.php');

dol_include_once('mmiredmine/class/mmi_redmine.class.php');

mmi_redmine::sync_activities();
