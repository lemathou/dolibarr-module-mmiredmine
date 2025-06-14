<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025 Mathieu MOULIN       <mathieu@iprospective.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mmiredmine/admin/setup.php
 * \ingroup mmiredmine
 * \brief   MMIRedmine setup page.
 */

// Load Dolibarr environment
require_once '../env.inc.php';
require_once '../main_load.inc.php';

$arrayofparameters = array(
	'MMI_REDMINE_AUTH'=>array('type'=>'separator', 'enabled'=>1),
	'MMI_REDMINE_URL'=>array('type'=>'string', 'enabled'=>1),
	'MMI_REDMINE_TOKEN'=>array('type'=>'securekey', 'enabled'=>1),
	'MMI_REDMINE_PROJECTS'=>array('type'=>'string', 'enabled'=>1),
	'MMI_REDMINE_PROJECT_AUTOCREATE'=>array('type'=>'yesno', 'enabled'=>1),

	'MMI_REDMINE_CRON'=>array('type'=>'separator', 'enabled'=>1),
	'MMI_REDMINE_CRON'=>array('type'=>'yesno', 'enabled'=>1),
	'MMI_REDMINE_CRON_FREQUENCY'=>array('type'=>'yesno', 'enabled'=>1),
);

require_once '../../mmicommon/admin/mmisetup_1.inc.php';
