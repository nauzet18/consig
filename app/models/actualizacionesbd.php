<?php
/*
 * Copyright 2011 Jorge López Pérez <jorgelp@us.es>
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

class Actualizacionesbd extends Model {
	function Actualizacionesbd() {
		parent::Model();
	}

	/**
	 * Lee las actualizaciones que hay que aplicar para una determinada
	 * versión de la base de datos.
	 *
	 * @param $version		Número de versión de la base de datos
	 * @return				Array asociativo con las actualizaciones, FALSE
	 * 						si no se encontró el fichero de actualizaciones
	 */
	function leer($version) {
		// Sólo números
		$version = preg_replace('[^0-9]', '', $version);
		$ruta = APPPATH . 'libraries/Bd_' . $version . '.php';
		
		if (!file_exists($ruta)) {
			log_message('error', 'Intento de recoger actualizaciones '
					.'del esquema para versión inexistente ('.$version.')');
			return FALSE;
		}
	}
}
