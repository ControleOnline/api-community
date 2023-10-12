cd ~/sistemas/api
/usr/local/bin/php bin/console app:fix pending-logistic

/usr/local/bin/php bin/console app:order-notifier create_logistic_invoice


/usr/local/bin/php bin/console app:fix order-status-by-contract
