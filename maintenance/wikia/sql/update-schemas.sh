#!/bin/bash
DATABASES='archive dataware portability_db specials statsdb wikicities'

for DATABASE in $DATABASES
do
	echo "Updating schema of tables in $DATABASE database ..."

	DB_PARAMS=`dbparams.pl --type slave --name $DATABASE`
	mysqldump --no-data $DB_PARAMS | sed 's/\/\*\!50100/\/*/g' | grep --invert-match --regexp='^\/\*\!' | sed 's/AUTO_INCREMENT=[0-9]* //g' > ${DATABASE}-schema.sql
done
