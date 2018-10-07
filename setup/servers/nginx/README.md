# nginx configuration

## Setup

1. Install a nginx version compiled with the perl support (you can build your own nginx using this script: https://github.com/nerdzeu/docker-nginx/blob/9d6c23ab501c922b80a87f6ab6fe1aa00511d60d/builder)
2. Install php-fpm and make it listen on 127.0.0.1:9000
3. https support works using https://github.com/galeone/letsencrypt-lighttpd (it's not limited to lighttpd)

Assuming a normal configuration of `nginx` in `/etc/nginx`:

```sh
cp nginx.conf /etc/nginx/
cp mime.type /etc/nginx/
cp nerdz.eu.conf /etc/nginx/sites-available/
cp -R conf.d/* /etc/nginx/conf.d/
ln -s /etc/nginx/sites-available/nerdz.eu.conf /etc/nginx/sites-enabled/nerdz.eu.conf
```

Adjust as needed. (change the `user` parameter of `nginx.conf` in order to run nginx with the correct permissions - update the paths - update the ssl certificate location).

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
# disable ssl: DO IT MANUALLY - remove the redirects from nerdz.eu.conf
# fix the server name
sed -i 's/nerdz\.eu/localhost/g' nerdz.eu.conf
# use the default error log
sed -i 's/error_log.*//g' nerdz.eu.conf
```
