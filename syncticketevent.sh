#!/bin/bash
step=15 #间隔的秒数
for (( i = 0; i < 60; i=(i+step) )); do
    /usr/bin/php /www/wwwroot/sealake_admin/artisan command:SyncTicketEvent
    sleep $step
done
exit 0
