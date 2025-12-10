FROM php:8.2-apache

# PHP-Erweiterungen installieren
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Apache mod_rewrite aktivieren
RUN a2enmod rewrite

# Apache-Konfiguration anpassen
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Arbeitsverzeichnis setzen
WORKDIR /var/www/html

# Verzeichnisse erstellen und Berechtigungen setzen
RUN mkdir -p /var/www/html/logs /var/www/html/uploads /var/www/html/credentials && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Port freigeben
EXPOSE 80

# Apache starten
CMD ["apache2-foreground"]
