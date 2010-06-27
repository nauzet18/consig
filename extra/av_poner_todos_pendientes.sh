#!/bin/bash
#
# Lee las tablas de ficheros y antivirus, y todos los ficheros que no estén 
# en la tabla antivirus los añade con estado PENDIENTE y timestamp 0. 
#
# En la siguiente llamada a '/cron' se encolarán para su análisis.

UMYSQL=$1
PMYSQL=$2
TMYSQL=$3

echo "SELECT ficheros.fid FROM ficheros LEFT OUTER JOIN antivirus ON ficheros.fid = antivirus.fid WHERE antivirus.estado IS NULL"| mysql \
	--batch --skip-column-names -u${UMYSQL} -p${PMYSQL} $TMYSQL | while read i; do

	echo "INSERT INTO antivirus(fid,estado,timestamp) VALUES ('${i}', 'PENDIENTE', '0');"
	
done

