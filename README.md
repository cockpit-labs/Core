# Cockpit Community Edition - Core API

This is the API for Cockpit CE based on [Symfony 5](https://symfony.com/) core, with [API Platform](https://api-platform.com/) bundle.

## Install Core API

Just do a `git clone`.
In the `Core` directory, initialize [composer](https://https://getcomposer.org//)

```shell script
composer install
```

⚠️ Install also the [TimeCop module](https://github.com/hnw/php-timecop), to allow fakedata generation! ⚠️

```shell script
sudo pecl install timecop-beta
```

And check that `extension="timecop.so"` is present in your php.ini files (for cli, webserver, etc).

## Install Keycloak and mysql

You need a Keycloak instance. You can use un [docker container](https://hub.docker.com/r/jboss/keycloak) or a local or remote installation. 
You need also a mysql DB (or any DB like mariaDB, etc). You can use docker container for [MySQL](https://hub.docker.com/_/mysql) or [MariaDB](https://hub.docker.com/_/mariadb), or another compatible DB.

Don't forget to set-up the .env file in `Core`!

Please, read [documentation](docs/README.md)!