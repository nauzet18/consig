#!/bin/bash
#
# Comprueba si falta algún icono en el directorio 128x128 que sí esté en
# 32x32

for i in ../public/img/tipos/32x32/*.png; do 
	F=`basename $i`
	if [ ! -f ../public/img/tipos/128x128/$F ]; then 
		echo Falta $F en 128x128
	fi
done

