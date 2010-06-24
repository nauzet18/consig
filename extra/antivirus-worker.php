#!/usr/bin/php
<?php
/*
 * Copyright 2010 Jorge López Pérez <jorgelp@us.es>
 *
 *    This file is part of Consigna.
 *
 *    Consigna is free software: you can redistribute it and/or modify it
 *    under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    Consigna is distributed in the hope that it will be useful, but
 *    WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public
 *    License along with Consigna.  If not, see
 *    <http://www.gnu.org/licenses/>.
 */


/*
 * Este script está ideado para ser llamado desde la línea de órdenes de
 * la siguiente forma:
 *
 * php /..../antivirus-worker.php -c /ruta/a/raíz/de/consigna/ [-d]
 */

$short_opt =
		'd' . // debug
		'c:' . // directorio de consigna
		'h'  // ayuda
		;
$options = getopt($short_opt);


// Ayuda
if (isset($options['h']) && $options['h'] === FALSE) {
	show_help();
	exit(0);
} 

if (!isset($options['c'])) {
	show_help(); // TODO: seguir
	exit(1);
}

if (isset($options['d']) && $options['d'] === FALSE) {
	define("DEBUG_WORKER", TRUE);
}



$ruta = $options['c'];
$ruta = preg_replace('/([^\/])$/', '${1}/', $ruta); // Barra final

// Cargamos configuración
$check = @opendir($ruta);
if ($check === FALSE) {
	echo "No se puede abrir " . $ruta . "\n";
	exit(1);
}

define('BASEPATH', $ruta);
$config = array();
require $ruta . "/config/config.php";

if (!$config['activar_antivirus']) {
	echo "El antivirus no está activo\n";
	exit(1);
}

// Biblioteca pheanstalk
require_once($ruta . 'app/libraries/pheanstalk/pheanstalk_init.php');

// Analizador
require_once($ruta . 'app/libraries/Avengine.php');
require_once($ruta . 'app/libraries/avmodules/' . $config['avmodule']
		. '.php');

$avconfig= $config['avconfig'];

$av = new $config['avmodule']($avconfig);

try {
	$pheanstalk = new Pheanstalk($config['beanstalkd_host'],
			$config['beanstalkd_port']);


	while (1) {
		$job = $pheanstalk
			->watch($config['beanstalkd_tube'])
			->ignore('default')
			->reserve();

		if ($job === FALSE) {
			debug("Reintentando en unos segundos...");
			sleep(5);
		} else {
			$exito = FALSE;
			debug($job->getData());
			$trozos = split(' ', $job->getData());

			if ($trozos[0] == 'SCAN') {
				$fid = $trozos[1];
				// Procesado con el antivirus
				$path = $config['directorio_ficheros'] .'/'
					. $fid;
				debug('Escaneando ' . $fid);
				$resav = $av->scan($path);

				if ($resav[0] == 2) {
					// Error pasando clamav
					debug("Error con " . $fid . ": " . $resav[1]);
					$exito = ws($fid, 'ERROR', $resav[1]);
					$pheanstalk->bury($job);
				} elseif ($resav[0] == 1) {
					// Infectado
					debug("Fichero " . $fid . " infectado: " . $resav[1]);
					$exito = ws($fid, 'INFECTADO', $resav[1]);
				} else {
					// Limpio
					debug("Fichero " . $fid . " limpio ");
					$exito = ws($fid, 'LIMPIO', '');
				}
			}

			// Liberamos... o esperamos
			if ($exito === TRUE) {
				$pheanstalk->delete($job);
			} else {
				$pheanstalk->release($job);
				debug("No funcionó bien " . $job->getData() 
						. ".  Esperando.");
				sleep(10);
			}
		}
	}
} catch (Exception $e) {
	echo "Error!: " . var_export($e, TRUE) . "\n";
	exit(1);
}



/**
 * Llamada al servicio web para establecer el estado de un fichero
 */

function ws($fid, $estado, $extra) {
	global $config;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $config['base_url'] 
			. 'ficheros/avws/' . $config['antivirus_ws_pass']);
	curl_setopt($ch, CURLOPT_POST, true);
	$post_data = array(
			'fid' => $fid,
			'estado' => $estado,
			'extra' => $extra,
			);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	if ($result === FALSE) {
		echo "Error guardando estado para " . $fid . " en WS";
	}

	curl_close($ch);

	return $result === FALSE ? FALSE : TRUE;
}



/************************/

function show_help() {
	global $argv;
?>
Uso: <?php echo $argv[0] ?> -c /raíz/consigna/ [-d]

Inicia un proceso de análisis mediante antivirus que lee de la cola de
trabajos de beanstalkd especificada en el fichero de configuración de
consigna.

El directorio raíz de consigna es el que contiene los directorios app/ y
config/, entre otros.

  -d            activa los mensajes de depuración
<?php
}


function debug($mensaje) {
	if (defined("DEBUG_WORKER")) {
		echo date('[Y/m/d H:i]: ') . $mensaje . "\n";
	}
}

?>
