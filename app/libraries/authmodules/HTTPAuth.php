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

class HTTPAuth {
	var $CI;

	function HTTPAuth() {
		$this->CI =& get_instance();
	}

	/**
	 * Comprueba si un usuario puede autenticarse con el nombre de
	 * usuario y la contraseña dados
	 *
	 * @return	FALSE si falla, o el identificador de usuario final si todo
	 *          es correcto
	 */
	function login_action(&$err, &$id) {
		// Leemos cabeceras HTTP
		$res = isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] 
			: FALSE;
		if ($res === FALSE) {
			// Algo falló
			$err = "&iquest;Está configurado el servidor para"
				." requerir autenticación?";
			return -1;
		} else {
			$id = $res;
			return 1;
		}

	}

	/**
	 * Extrae la información asociada a un identificador de usuario
	 *
	 * @param 	string dn 
	 * @return	FALSE si falla, o un array con los datos del usuario
	 */

	function get_user_data($id) {
		// Datos ficticios
		$datos = array(
				'id' => $_SERVER['REMOTE_USER'],
				'name' => $_SERVER['REMOTE_USER'],
				'mail' => "-",
				'timestamp' => time(),
		);

		return $datos;
	}

	/**
	 * Expira las entradas en la caché
	 */
	function cache_expiration() {
		// No queremos expiración
	}

	/**
	 * Form fields
	 */

	function has_form() {
		return FALSE;
	}

	/**
	 * Logout
	 */
	function logout() {
		// Nada
	}

	/**
	 * Condiciones para la autenticación automática
	 */

	function check_conditions() {
		return isset($_SERVER['REMOTE_USER']);
	}
}

?>
