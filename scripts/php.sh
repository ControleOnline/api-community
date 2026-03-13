sudo apt install composer -y

sudo chmod o+x /home/MYDOMAIN
sudo tee /etc/nginx/sites-available/MYDOMAIN.com.conf > /dev/null <<'EOF'
server {
    listen 80;
    server_name MYDOMAIN.com;

    root /home/MYDOMAIN/public;
    index index.php;

    client_max_body_size 2G;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;

        include fastcgi.conf;

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;

        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/MYDOMAIN_error.log;
    access_log /var/log/nginx/MYDOMAIN_access.log;
}
EOF

sudo ln -s /etc/nginx/sites-available/MYDOMAIN.com.conf /etc/nginx/sites-enabled/

sudo systemctl reload nginx