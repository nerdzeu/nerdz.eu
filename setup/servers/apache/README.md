Apache configuration
====================

This is the apache configuration sample with GnuTLS support.

You have to replace the %VARIABLE% with valid values, like: 
- DOMAIN_ADMIN_MAIL: something@example.com
- NERDZ_PATH: /srv/http/nerdz
- DOMAIN_NAME: www.example.com
- PATH_TO_ERROR_LOG: /var/log/apache/nerdz-error_log
- PATH_TO_ACCESS_LOG: /var/log/apache/nerdz-access_log
- DOMAIN_NAME_WITHOUT_WWW: example.com
- DOMAIN_NAME_WITH_WWW: %DOMAIN_NAME%
- PATH_TO_SSL_ERROR_LOG: /var/log/apache/nerdz-ssl-error_log
- PATH_TO_SSL_TRANSFER_LOG: /var/log/apache/nerdz-ssl-transfer_log
- PATH_TO_CERT: /etc/ssl/certs/nerdz.crt
- PATH_TO_CERT_KEY: /etc/ssl/private/nerdz.key
