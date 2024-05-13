#!/bin/bash
#加入crontab */1 * * * * /data/script/check_service.sh
count=`ps -fe |grep "inspect-" | grep -v "grep" | grep "master" | wc -l` 
manager=`ps -fe |grep "inspect-" | grep -v "grep" | grep "manager" | wc -l`
if [ $count -lt 1 ] || [ $manager -lt 1 ];
then
	count=`ps -eaf |grep "inspect-" | grep -v "grep"| awk '{print $2}' | wc -l`
	if [ $count -gt 0 ]; then
		ps -eaf |grep "inspect-" | grep -v "grep"| awk '{print $2}'|xargs kill -9
		sleep 2
	fi
ulimit -c unlimited
/usr/local/webserver/php/bin/php /data/www/inspect-service/service
echo "restart";
echo $(date +%Y-%m-%d_%H:%M:%S) >/data/www/inspect-service/log/restart.log
fi