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

class Clamav extends Avengine {
	// Ruta hacia el ejecutable usado junto a parámetros
	private $rutayparam;

	/**
	 * Este módulo necesita las siguientes opciones:
	 *
	 *  'rutayparam': ruta completa al ejecutable de clamscan/clamdscan,
	 *                junto a los argumentos pasados. Se recomienda
	 *                usar los parámetros: -i --no-summary
	 */

	function __construct($config) {
		$this->rutayparam = $config['rutayparam'];
	}

	/**
	 * Analiza un fichero y devuelve un array construido como sigue:
	 *
	 * [c, extra]
	 *
	 * Donde c es un código que indica:
	 *
	 *  0: limpio
	 *  1: infectado
	 *  2: error
	 *
	 */
	function scan($path) {
		$orden = $this->rutayparam . ' ' . $path;

		$salida = $this->my_exec($orden);
		if ($salida['return'] != 0 && $salida['return'] != 1) {
			// Error pasando clamav
			$result = array(2, 'No se pudo ejecutar clamdscan: ' 
					. $salida['stderr']);
		} elseif (!empty($salida['stdout'])) {
			// Infectado
			$ts = preg_split('/ /', $salida['stdout']);
			$virus = $ts[1];
			$result = array(1, $virus);
		} else {
			// Limpio
			$result = array(0, '');
		}

		return $result;
	}

	/**
	 * Ejecuta una orden
	 *
	 * Extraída de los comentarios de http://es2.php.net/system,
	 * de kexianin at diyism dot com
	 */

	private function my_exec($cmd, $input='') {
		$proc=proc_open($cmd, array(
					0=>array('pipe', 'r'), 
					1=>array('pipe', 'w'), 
					2=>array('pipe', 'w')), $pipes);


		// Entrada
		fwrite($pipes[0], $input);
		fclose($pipes[0]);

		// Salida
		$stdout=stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// Salida de errores
		$stderr=stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$rtn=proc_close($proc);
		return array(
				'stdout'=>$stdout,
				'stderr'=>$stderr,
				'return'=>$rtn
				);
	}
}
