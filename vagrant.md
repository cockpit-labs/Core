## Vagrant setup (old style)

### Projects sources mounting

Be sure to have the **vagrant bindfs plugin** installed on host. If not, install it:

````shell script
vagrant plugin install vagrant-bindfs
````

To access project sources, use NFS mounting. In vagrant directory (directory `cockpitce` with Vagrantfile from `dev-vagrant`project), create a `Vagranfile.local containing this:

```shell script

Vagrant.configure("2") do |config|
  config.vm.synced_folder "/Users/ze4/sentinelo/CockpitCE/", "/cockpitce-nfs", nfs: true
  config.bindfs.bind_folder "/cockpitce-nfs", LOCALPROJECT,
    force_user:   'www-data',
    force_group:  'sentinelo',
    perms:        'u=rwX:g=rX:o=rD'
end

```

### PHP

add extensions:

````shell script
sudo apt-get install php-intl
````

### Install timecop 

***⚠️ to be run in Vagrant ⚠️***

`sudo pecl install timecop-beta`

`echo "extension=timecop.so" | cat - /etc/php/current/apache2/php.ini | sudo tee /etc/php/current/apache2/php.ini`


### Install [Gotenberg](https://github.com/thecodingmachine/gotenberg)

Gotenberg is a Docker-powered stateless API for converting HTML, Markdown and Office documents to PDF.
 
`docker run -dti -p 3000:3000 --name gotenberg thecodingmachine/gotenberg:6`


### Init Core (**run always after getting new release**)

***⚠️ to be run locally ⚠️***

```bash
composer install
./setCoreEnv-dev.sh
```


[See Injecting demo data](#injecting-demo-data)

### Deployment in Vagrant

***⚠️ to be run locally ⚠️***

ansible-playbook -i Deployment/inventory --extra-vars "target=vagrant" Core/ansible/deploy.yml --extra-vars "globalvars=`pwd`/Deployment/global.yml"

### Get KeyCloak tokens 

To get a View `access_token`, run (in `CockpitCE/Core folder`):

```bash
bin/console demo:token:generate --username kallie.dibbert --client cockpitview
```

for a admin token : 
```bash
bin/console demo:token:generate --username audie.fritsch --client cockpitadmin
```



### Injecting demo data

***⚠️ to be run in Vagrant ⚠️***


There's a symfony command for demo injection :

`bin/console demo:load`

For injecting `keycloak` datas, use :

`sudo -u www-data bin/console demo:load --keycloak demo/keycloak.json --kcadminpwd '<pwd>'`

For injecting `templates`, use :

`sudo -u www-data bin/console demo:load --template demo/Templates.json --clean`

To restore Keycloak keys, use :

`sudo -u www-data bin/console demo:load --kcrebuild --kcadminpwd '<pwd>'`

To reset DB and rebuild, use :

`sudo -u www-data bin/console demo:load --rebuild`

To clean DB (empty DB tables), use :

`sudo -u www-data bin/console demo:load --clean`

To generate fake data (filled folders), use :

`sudo -u www-data bin/console demo:load --fakedata`

Some parameters of this can be combined:

`sudo -u www-data bin/console demo:load --template demo/Templates.json --rebuild --fakedata`

Avoid injecting Keycloak datas and templates/fakedata in the same time.

### Restore keycloak and all data

***⚠️ to be run in Vagrant ⚠️***

Clean all, everywhere:
```shell script
sudo service apache2 restart&& sudo rm -rf /tmp/CockpitCoreCE/dev && sudo rm -rf /tmp/CockpitCoreCE/test && sudo rm -rf /tmp/CockpitCoreCE/prod && sudo rm -rf var/log/*
```

Use keycloak script:

`./keycloak.sh --drop` or `./keycloak.sh --delete`

`--delete`: delete keycloak tables in keycloak database
`--drop`: drop keycloak database and user, then create the database and the user.

For `--drop`, you need to know the mysql root password!

To check keycloak init:

```shell script
docker logs centraladmin --follow
```

Keycloak is ready when you can see a log line like that:

    WFLYSRV0025: Keycloak 9.0.3 (WildFly Core 10.0.3.Final) started in 15283ms
 
So, renegerate everything:

`sudo -u www-data bin/console demo:load --keycloak demo/keycloak.json --kcadminpwd '<pwd>' --kcrebuild --template=demo/TemplatesRetail.json --rebuild --fakedata`

