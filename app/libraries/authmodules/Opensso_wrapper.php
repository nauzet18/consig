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

require_once 'libopensso-php/OpenSSO.php';

class Opensso_wrapper {
	var $CI;
	private $o;

	function Opensso_wrapper() {
		$this->CI =& get_instance();
		$this->o = new OpenSSO();
	}

	/**
	 * Comprueba si un usuario puede autenticarse con el nombre de
	 * usuario y la contraseña dados
	 *
	 * @return	FALSE si falla, o el identificador de usuario final si todo
	 *          es correcto
	 */
	function login_action(&$err, &$id) {
		// Leemos las cabeceras de OpenSSO
		$res = $this->o->check_and_force_sso();
		if ($res === FALSE) {
			// De esta manera se permite la redirección
			return 0;
		} else {
			$id = $this->o->attribute('uid');
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

		// Lectura de cabeceras
		$datos = array(
				'id' => $this->o->attribute('uid'),
				'name' => ucwords(strtolower($this->o->attribute('cn'))),
				'mail' => $this->o->attribute('mail'),
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
		$this->o->logout(FALSE);
	}

	/**
	 * Condiciones para la autenticación automática
	 */

	function check_conditions() {
		return $this->o->check_sso();
	}
}

?>
