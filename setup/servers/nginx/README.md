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

Adjust as needed:

1. change the `user` parameter of `nginx.conf` in order to run nginx with the correct permissions
2. update the paths (the configuration works using the user `nessuno` and a directory for each domain and subdomain in its home)
3. ...

### Development

In development use the following to quickly adjust the configuration:

```sh
# adjust server root
# do it manually, change the `root` directive witht eh correct path
# disable ssl: DO IT MANUALLY - remove the redirects from nerdz.eu.conf
# fix the server name
sed -i 's/nerdz\.eu/localhost/g' nerdz.eu.conf
# use the default error log
sed -i 's/error_log.*//g' nerdz.eu.conf
```
