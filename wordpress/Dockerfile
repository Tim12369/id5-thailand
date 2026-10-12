# WordPress for the ID5 editors, built to run on a host with no lasting disk
# (Render's free plan). Everything that must survive a restart lives in the
# MySQL database: the content, and — through the id5-cms plugin — the uploaded
# files too. See docs/free-hosting.md.
#
# Build context: this `wordpress/` folder.
FROM wordpress:7.1.3-php8.2-apache

# Only what the editors use. Plugins and themes cannot be installed from the
# dashboard on this host (the disk is wiped), so the image is the only source.
RUN rm -rf /usr/src/wordpress/wp-content/plugins/akismet \
           /usr/src/wordpress/wp-content/plugins/hello.php

COPY plugins/id5-cms /usr/src/wordpress/wp-content/plugins/id5-cms
COPY hosting/wp-config.php /usr/src/wordpress/wp-config.php
COPY hosting/boot.php /usr/local/share/id5/boot.php
COPY hosting/php.ini /usr/local/etc/php/conf.d/zz-id5.ini
COPY hosting/apache.conf /etc/apache2/conf-enabled/zz-id5.conf
COPY hosting/entrypoint.sh /usr/local/bin/id5-entrypoint

RUN chmod +x /usr/local/bin/id5-entrypoint \
    && chown -R www-data:www-data /usr/src/wordpress/wp-content/plugins/id5-cms /usr/src/wordpress/wp-config.php

EXPOSE 80
ENTRYPOINT ["id5-entrypoint"]
