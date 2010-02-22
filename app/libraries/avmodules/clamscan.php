<?php
/*
 * Copyright 2009 Jorge López Pérez <jorgelp@us.es>
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

class Clamscan {
	function Clamscan() {
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
		$orden = '/usr/bin/clamscan -i --no-summary ' . $path;

		$salida = system($orden, $ret);
		if ($ret != 0 && $ret != 1) {
			// Error pasando clamav
			$result = array(2, 'No se pudo ejecutar clamdscan');
		} elseif (!empty($salida)) {
			// Infectado
			$ts = split(' ', $salida);
			$virus = $ts[1];
			$result = array(1, $virus);
		} else {
			// Limpio
			$result = array(0, '');
		}

		return $result;
	}
}
