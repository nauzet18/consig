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
 * php /..../antivirus-worker.php /ruta/a/raíz/de/consigna
 */

if (count($argv) != 2) {
	echo "Sintaxis: " . $argv[0] . " /ruta/raíz/consigna\n";
	exit(1);
}

$ruta = $argv[1];

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

try {
	$pheanstalk = new Pheanstalk($config['beanstalkd_host'],
			$config['beanstalkd_port']);

	while (1) {
		$job = $pheanstalk
			->watch('antivirus')
			->ignore('default')
			->reserve();

		if ($job === FALSE) {
			echo "TTR alcanzado\n";
		} else {
			echo $job->getData() . "\n";
			sleep(2);
			$pheanstalk->delete($job);
		}
	}
} catch (Exception $e) {
	echo "Error!: " . var_export($e, TRUE) . "\n";
	exit(1);
}


?>
