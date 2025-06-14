# CHANGELOG MMIREDMINE FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

Redmine to Dolibarr Connector for Projectsn tasks and time entries 

## 1.0

Initial version

Synchronisation from Redmine to Dolibarr :
* Time spend activities
* Projects
* Tasks
* Time entries

User mapping :
* Map users between Redmine and Dolibarr

Time spent activities synchronisation :
* Map activities to product ID using dictionnary (will change soon)

Project synchronisation :
* Map multiple Redmine projects to one Dolibarr Project before synchronisation
* Specify if Synchronisation should create projects if they are not mapped.

Task Synchronisation :
* Creates and updates if needed
  
Time entries :
* Creates, attach to tasks (use specific NOTASK task if there is no task associated to redmine time entry), updates
  
Possibility to create invoices as usual.