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
* \>=PHP7.1 (tested only on PHP7.2)
* \>=MYSQL5.6

## Yet another Framework?
simply, if you need a simple php aplication like a OnePager with Forms, API, or other simple Tasks.

## Template "Engine"
Supports currently 2 Commands:
* Replace a placeholder with a assigned value ```{@replacePlaceholder}``` 
* Base Template will be overwritten by template itself ```{@base:/Resources/Template/base.html}```

## CLI
CLI are speerated from the Webservice calls (own routing/ no html output / no ViewLoading)    
If you want to use cli calls like:

``` php www/mining/cli.php --key K3Pgjt6794A47qe43y8X --cmd fancy-cronjob-stuff ```

Add the cmd `fancy-cronjob-stuff` to the Config.php

```
    private static $cliRouting = [
        'fancy-cronjob-stuff' => [
            self::CONTROLLER => 'Src\CLI\Test\Controller\UpdateStuffController',
            self::METHOD => 'updateAction'
        ]
    ];
```

Use CliController as BaseController for your Src-Controller for better Performance

## vhost

```FUYUKAI_ENV``` can be "dev" or "prod" (prod is default if nothing is set)      
Rewirte condition for Resources is kinda important ;)

```
<VirtualHost *:80>
        ServerName fuyukai.local
        ServerAdmin rannow@emerise.de
        DocumentRoot /Users/benjamin/www/fuyukaiFramework

        SetEnv FUYUKAI_ENV "dev"

    <Directory /Users/benjamin/www/fuyukaiFramework>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride None
        Require all granted

        DirectoryIndex index.php
        RewriteEngine On

        RewriteCond %{REQUEST_URI} !^/Resources/(js|css|img)/
        RewriteRule ^(.*)$ index.php [QSA,L]

        Order allow,deny
        allow from all
        EnableMMAP Off
        EnableSendfile Off
    </Directory>

        ErrorLog /usr/local/var/log/httpd/ff/error.log
        CustomLog /usr/local/var/log/httpd/ff/access.log combined
</VirtualHost>
```
