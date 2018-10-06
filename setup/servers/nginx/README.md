# nginx configuration

## Setup

Assuming a normal configuration of `nginx` in `/etc/nginx`:

```sh
cp nerdz.eu.conf /etc/nginx/sites-available/
cp php_handler /etc/nginx/
cp -R conf.d/* /etc/nginx/conf.d/
ln -s /etc/nginx/sites-available/nerdz.eu.conf /etc/nginx/sites-enabled/nerdz.eu.conf
```

Adjust as needed.

### Production

In production, ensure your directory hierarchy looks like this:

```
/
└── srv
    └── www
        └── nginx
            └── vhosts
                └── nerdz.eu
                    ├── logs
                    ├── certs
                    │   ├── nerdz.crt
                    │   └── nerdz.key
                    └── work
                       └── ... nerdz tree
```

### Development

In development use the following to quickly adjust the configuration:

```sh
# adjust server root
sed -i 's#/srv/www/nginx/vhosts/nerdz.eu/work/#/var/www/html/#g' nerdz.eu.conf
# disable ssl
sed -i 's#include conf.d/nerdz.eu/ssl.conf;##g' nerdz.eu.conf
# fix the server name
sed -i 's/work\.nerdz\.eu/localhost/g' nerdz.eu.conf
# use the default error log
sed -i 's/error_log.*//g' nerdz.eu.conf
```
