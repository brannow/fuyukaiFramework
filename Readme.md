# Fuyukai Framework
FuyukaiPHP aims for Fast but secure PHP framework

* Rewirte Routing
* Login
* Database Handling
* Encryption/Decryption
* PHP Model <-> Database (autofilling)
* API JsonViews
* CLI accessable; for example cronjobs (see: cli.php)
* Simple Template Engine

## What do you need
* Apache/nginx (NOTE: currently only apache tested... if u have trouble, check index.php)
* mod_rewirte
* >=PHP7.1 (tested only on PHP7.2)
* >=MYSQL5.6

## Yet another Framework?
simply, if you need a simple php aplication like a OnePager with Forms, API, or other simple Tasks.

# Template "Engine"
Supports currently 2 Commands:
* Replace a placeholder with a assigned value ```{@replacePlaceholder}``` 
* Base Template will be overwritten by template itself ```{@base:/Resources/Template/base.html}```
