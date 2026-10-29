# MMIRedmine — Redmine : synchronisation des projets, tickets et temps.

> Module Dolibarr **MMIRedmine** (version 1.0), branche `20.0`.

## Fonctionnalités

### Fonctionnalités principales

- Synchronisation des projets, tickets et temps Redmine.
- Configuration API Redmine.

### Écrans et menus

- **Connecteur Redmine** — `/mmiredmine/mmiredmineindex.php` (menu mmiredmine, droit `1`)

### Pages

- `admin/about.php`
- `admin/setup.php`
- `list.php`
- `mmiredmineindex.php`
- `sync.php`
- `sync_activities.php`

### Configuration

| Constante | Type | Rôle |
|---|---|---|
| `MMI_REDMINE_AUTH` | separator | Authentification API |
| `MMI_REDMINE_URL` | string | URL Redmine |
| `MMI_REDMINE_TOKEN` | securekey | Token Redmine |
| `MMI_REDMINE_PROJECTS` | string | Projets synchronisés |
| `MMI_REDMINE_PROJECT_AUTOCREATE` | yesno | Création automatique de projet synchronisé |
| `MMI_REDMINE_CRON` | separator | Tâche CRON |
| `MMI_REDMINE_CRON_FREQUENCY` | yesno | Fréquence de la tâche CRON |

### Classes et composants

- `class/mmi_redmine.class.php`
- `lib/mmiredmine.lib.php`

## Prérequis

- Dolibarr >= 11.0 (branche `20.0` : Dolibarr 20.0).
- Modules MMI : `mmicommon`. **`mmicommon` doit être activé en premier.**
- Modules cœur : `projet`.

## Installation

1. Copier le module dans `htdocs/custom/mmiredmine` :

   ```sh
   cd htdocs/custom
   git clone <dépôt> mmiredmine
   ```

2. Dans Dolibarr : **Configuration → Modules**, activer d'abord `mmicommon`, puis ce module.
3. Vérifier les permissions dans la fiche des utilisateurs concernés.

## Licence

GPLv3 ou ultérieure — voir `COPYING`. Documentation sous GFDL.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) (évolutions datées).
