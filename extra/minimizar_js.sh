#!/bin/bash

DIRJS=../public/js

for i in {interfaz,jquery.{blockUI_2.31,cluetip,hoverIntent,timers}}.js; do
	echo Minimizando $i...
	NUEVOFICH=`echo "$i"|sed 's_\.js$_.min.js_g'`
	java -jar yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar \
		--type js -v -o $DIRJS/$NUEVOFICH $DIRJS/$i

done
