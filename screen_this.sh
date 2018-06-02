#!/bin/bash
cd webroot
while :
do
php cron.php >> ../cron.log
sleep 6h
done
