#!/bin/bash

SLAVE_DB="gestaoTechlog"
TARGET_DB="frethical_staging"
BACKUP_DIR="/backups/db"
DATE=$(date +%F)
BACKUP_FILE="$BACKUP_DIR/${SLAVE_DB}_$DATE.sql.gz"

# Criar backup do slave
mysqldump -u root --single-transaction $SLAVE_DB | gzip > $BACKUP_FILE

# Substituir o banco de destino
mysql -u root -e "DROP DATABASE IF EXISTS $TARGET_DB; CREATE DATABASE $TARGET_DB;"

# Restaurar backup no banco de destino
gunzip < $BACKUP_FILE | mysql -u root $TARGET_DB

# Apagar backups com mais de 3 dias
find $BACKUP_DIR -name "${SLAVE_DB}_*.sql.gz" -type f -mtime +3 -exec rm -f {} \;


mysql -u root -D frethical_staging -e 'UPDATE `user` SET `password` = '\''$2y$13$5cJ18CCLpNRr6B9A6p2Ote5oc/F342BgXYUh.Fe8eqPKRVY1.IxCi'\'';

cd /var/www/vhosts/staging.frethical.com/httpdocs
/opt/plesk/php/8.2/bin/php bin/console doctrine:migrations:migrate --no-interaction