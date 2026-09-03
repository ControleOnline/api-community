sudo apt install ufw -y
sudo ufw --force reset
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw limit 22/tcp
sudo ufw allow 80
sudo ufw allow 443
sudo ufw --force enable