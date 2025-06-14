# MMIREDMINE FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

Redmine to Dolibarr Connector for Projectsn tasks and time entries 

## Features

Synchronisation from Redmine to Dolibarr :
* Time spent activities
* Projects
* Tasks
* Time entries

User mapping :
* Map users between Redmine and Dolibarr (extrafield redmine ID in user)

Time spent activities synchronisation :
* Map activities to product ID using dictionnary (with field redmine ID)
* Will probably use another class in ther future

Project synchronisation :
* Can Map Redmine projects to Dolibarr Project before synchronisation (using extrafield redmine ID in project)
* Specify if Synchronisation should create in Dolibarr new Redmine projects which are not already mapped

Task Synchronisation :
* Creates and updates if needed
  
Time entries :
* Creates, attach to tasks and updates
* Create and use a specific "NOTASK" task if there is no task associated to redmine a time entry
  
Possibility to create invoices as usual from time entries.

<!--
![Screenshot mmiredmine](img/screenshot_mmiredmine.png?raw=true "MMIRedmine"){imgmd}
-->

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files into directories *langs*.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

If the module is a ready to deploy zip file, so with a name module_xxx-version.zip (like when downloading it from a market place like [Dolistore](https://www.dolistore.com)),
go into menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you that there is no "custom" directory, check that your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

Clone the repository in ```$dolibarr_main_document_root_alt/mmiredmine```

```sh
cd ....../custom
git clone git@github.com:gitlogin/mmiredmine.git mmiredmine
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
