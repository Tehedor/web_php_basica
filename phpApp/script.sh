# sudo apt install php8.3-cli
sudo apt-get install php-mysql

sudo apt update
sudo apt install apache2

sudo cp -r /webPHP/* /var/www/html/


sudo echo "
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
" > /etc/apache2/sites-available/webPHP.conf



sudo a2ensite webPHP.conf
sudo systemctl restart apache2



# php -S     localhost:8000