#!/bin/bash
#
# Lee la tabla de ficheros, y los añade todos con estado PENDIENTE en la
# tabla de antivirus. En la siguiente llamada a '/cron' se encolarán
# para su análisis.

UMYSQL=$1
PMYSQL=$2
TMYSQL=$3

echo "SELECT fid FROM ficheros"| mysql \
	--batch --skip-column-names -u${UMYSQL} -p${PMYSQL} $TMYSQL | while read i; do

	echo "INSERT INTO antivirus(fid,estado,timestamp) VALUES ('${i}', 'PENDIENTE', '0');"
	
done

