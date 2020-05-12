# Shaarli REST API

## Requirements
* PHP >= 5.4.4
* MySQL or Sqlite3
* PDO
* Apache RewriteEngine or Nginx

## Installation
```bash
cd /var/www
# Clone repo
git clone https://github.com/oros42/shaarli-api.git
# Create mysql database (not need for sqlite)
mysqladmin create shaarli-api -p
cd shaarli-api
# Copy `config.php.dist` into `config.php` and setup your own settings.
cp config.php.dist config.php
nano config.php
# blacklist of dead shaarlis
cp blacklist.txt.dist blacklist.txt
# Run composer install
php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install
```
In Apache or Nginx set the root folder to /public/.  
  
Example of conf for nginx :  
```
# https://example.com/shaarli-api/
# /var/www/shaarli-api/public
server {
    # your server conf
    # [...] 
    location /shaarli-api { # adapt this
        alias /var/www/shaarli-api/public; # adapt this
        try_files $uri /index.php$uri$is_args$args; # the most important part !
    }
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock; # adapt this
        include snippets/fastcgi-php.conf;
    }
}
```
or  
```
# https://shaarli-api.example.com/
# /var/www/shaarli-api/public
server {
    # your server conf
    # [...] 
    server_name shaarli-api.example.com; # adapt this
    root /var/www/shaarli-api/public/; # adapt this
    index index.html index.htm index.php;
    location / {
        try_files $uri /index.php$uri$is_args$args; # the most important part !
    }
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock; # adapt this
        include snippets/fastcgi-php.conf;
    }
```
  
## Run
```bash
$ php cron.php -h
php cron.php [Options]
Options
-c, --check   : check the DB
-d, --daemon  : run in daemon. Fetch all feeds in loop
-h, --help    : this help
-s, --sync    : synchronize the list of feeds
-v, --verbose : increase verbosity

If no option, fetch 1 time all feeds.
Examples :
php cron.php --check
php cron.php --verbose
php cron.php -d -v
php cron.php --daemon&
php cron.php --sync --verbose
```

Check the database :  
```bash
php cron.php --check
```

If you use sqlite :  
```bash
sudo chown -R $USER:www-data database/
```

Run cron, for initialization we recommend using the argument --verbose (or -v) to be sure everything working fine
```bash
php cron.php --verbose
```
If no everything work. Run the cron in daemon :  
```bash
php cron.php --daemon&
```

## Sync

Update the list of shaarli.
```bash
php cron.php --sync --verbose
```
or if ```define('ALLOW_WEB_SYNC', true);``` in config.php :
```bash
https://exemple.com/syncfeeds
```

## Update your installation
* Update your installation via Git (`git update origin master`) or the [archive file](archive/master.zip).
* Check if there was any changes in [config file](blob/master/config.php.dist), and add settings if necessary.
* Update external libraries with [Composer](https://getcomposer.org/download/). Run: `composer update`.
* Run cron the finalize the update: `php cron.php --verbose`.

## API Usage
* /feeds La liste des shaarlis
* /latest Les derniers billets
* /top Les liens les plus partagés
* /search Rechercher dans les billets
* /discussion Rechercher une discussion
* /syncfeeds Synchroniser la liste des shaarlis

## Options
* &format=json
* &pretty=true

## Samples
* Obtenir la liste des flux actifs: https://exemple.com/shaarli-api/feeds?pretty=1
* Obtenir la liste complète des flux: https://exemple.com/shaarli-api/feeds?full=1&pretty=1
* Obtenir le nombre de flux actifs: https://exemple.com/shaarli-api/feeds?count=1&pretty=1
* Obtenir les billets d'un seul flux: https://exemple.com/shaarli-api/feed?id=1&pretty=1
* Obtenir les derniers billets https://exemple.com/shaarli-api/latest?pretty=1
* Obtenir le top des liens partagés depuis 48h: https://exemple.com/shaarli-api/top?interval=48h&pretty=1
* Faire une recherche sur php: https://exemple.com/shaarli-api/search?q=php&pretty=1
* Rechercher une discution sur un lien: https://exemple.com/shaarli-api/discussion?url=https://exemple.com/shaarli-river/index.php&pretty=1
