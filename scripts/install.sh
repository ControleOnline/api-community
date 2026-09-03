sudo apt update -y
sudo apt upgrade -y
sudo apt install nginx certbot python3-certbot-nginx openjdk-21-jre-headless -y
sh swap.sh
sh node.sh
sh firewall.sh


#sudo certbot --nginx -d mydomain.com