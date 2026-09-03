
sudo apt install php-fpm php-mysql php-mbstring php-zip php-gd php-json php-curl php-cli -y
sudo apt install mysql-server phpmyadmin -y
sudo ln -s /usr/share/phpmyadmin /var/www/html/phpmyadmin
sudo chown -R www-data:www-data /usr/share/phpmyadmin
sudo systemctl enable mysql
sudo systemctl start mysql

sudo rm -f /etc/nginx/sites-enabled/default
sudo tee /etc/nginx/conf.d/phpmyadmin.conf > /dev/null <<'EOF'
server {
    listen 80 default_server;
    server_name _;
    root /var/www/html;
    index index.php index.html index.htm;

    location /phpmyadmin {
        root /usr/share;
        index index.php;
        try_files $uri $uri/ =404;

        location ~ ^/phpmyadmin/(.+\.php)$ {
            try_files $uri =404;
            root /usr/share;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }

        location ~* ^/phpmyadmin/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))$ {
            root /usr/share;
            expires max;
            log_not_found off;
        }
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
EOF

mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql
sudo nginx -t && sudo systemctl restart nginx