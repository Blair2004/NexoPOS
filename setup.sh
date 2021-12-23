# PHP
sudo apt update
sudo apt install apache2 libapache2-mod-fcgid
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php7.4 php7.4-fpm php7.4-xml php7.4-gd php7.4-zip php7.4-mbstring php7.4-curl php7.4-mysql php7.4-bcmath
sudo a2enmod actions fcgid alias proxy_fcgi
sudo a2enmod rewrite
sudo systemctl restart apache2
# Composer 2
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
# Maria DB
sudo apt update
sudo apt install mariadb-server
sudo mysql_secure_installation
# NVM
sudo apt install curl
curl https://raw.githubusercontent.com/creationix/nvm/master/install.sh | bash
source ~/.profile
nvm install 14

# Apache config
<VirtualHost *:80>
    ServerName pos.nexopos.com
    ServerAlias pos.nexopos.com
    DocumentRoot /var/www/html/pos.nexopos.com
 
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
    </Directory>
 
    <FilesMatch \.php$>
        # 2.4.10+ can proxy to unix socket
        SetHandler "proxy:unix:/var/run/php/php7.4-fpm.sock|fcgi://localhost"
    </FilesMatch>
 
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

GRANT ALL ON *.* TO '*****'@'localhost' IDENTIFIED BY '**********' WITH GRANT OPTION;

# Supervisor
[program:app-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/html/app/worker.log
stopwaitsecs=3600