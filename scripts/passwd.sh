sudo apt install apache2-utils -y
sudo htpasswd -cb /etc/nginx/.htpasswd admin senha123

sudo tee /etc/nginx/conf.d/auth-all.conf > /dev/null <<'EOF'
# Configuração de Autenticação Global
auth_basic "Área protegida";
auth_basic_user_file /etc/nginx/.htpasswd;
EOF

sudo nginx -t
sudo systemctl restart nginx