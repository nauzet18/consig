#!/bin/bash
#
# Lee la tabla de ficheros, deja únicamente los uids (en lugar del DN) y
# carga en usercache todo lo relativo a ellos
#
# Uso interno. Contacte con jorgelp@us.es para conseguir extrae.pl

UMYSQL=$1
PMYSQL=$2
TMYSQL=$3

# Limpieza de usercache
echo "DELETE FROM usercache;"

echo "SELECT DISTINCT(remitente) FROM ficheros WHERE remitente != ''"| mysql \
	--batch --skip-column-names -u${UMYSQL} -p${PMYSQL} $TMYSQL | while read i; do

	######################################
	# 1. Remitentes de ficheros
	######################################

	UIDREMITENTE=`echo "$i" | cut -f 2 | cut -f 1 -d ","|cut -f 2 -d "="`
	echo "UPDATE ficheros SET remitente='${UIDREMITENTE}' WHERE remitente='${i}';"
	
	######################################
	# 2. Caché de usuarios
	######################################

	DATOSLDAP=`extrae.pl -f uid=${UIDREMITENTE} -a cn -a mail -nodn`
	CN=`echo "$DATOSLDAP"|cut -f 1 -d '|'`
	MAIL_=`echo "$DATOSLDAP"|cut -f 2 -d '|'`

	# Mayúsculas en la primera letra de cada palabra
	# (http://www.unix.com/shell-programming-scripting/122000-convert-first-character-each-word-upper-case.html)
	NOMBRE=`echo $CN | tr " " "\n" | nawk ' { out = out" "toupper(substr($0,1,1))tolower(substr($0,2)) } END{ print substr(out,2) } '`


	echo -n "INSERT INTO usercache VALUES ('${UIDREMITENTE}' ,"
	echo "'${NOMBRE}', '${MAIL_}', '`date +%s`');"


done

