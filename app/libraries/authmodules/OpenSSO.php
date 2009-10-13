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

class OpenSSO {
	var $CI;

	function OpenSSO() {
		$this->CI =& get_instance();
	}

	/**
	 * Comprueba si un usuario puede autenticarse con el nombre de
	 * usuario y la contraseña dados
	 *
	 * @return	FALSE si falla, o el identificador de usuario final si todo
	 *          es correcto
	 */
	function login_action(&$err) {
		// Leemos las cabeceras de OpenSSO
		$uid = FALSE;
		if (isset($_SERVER['REMOTE_USER'])) {
			$uid = $_SERVER['REMOTE_USER'];
		} elseif (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
			$uid = $_SERVER['REDIRECT_REMOTE_USER'];
		}

		if ($uid === FALSE) {
			$err = "Hay algún problema interno de autenticación. Contacte "
				."con ". $this->CI->config->item('texto_contacto');
		}

		return $uid;
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
				'id' => $id,
				'name' => isset($_SERVER['HTTP_CN']) ?
					ucwords(strtolower($_SERVER['HTTP_CN'])) :
					$_SERVER['REMOTE_USER'],
				'mail' => isset($_SERVER['HTTP_MAIL']) ?
				$_SERVER['HTTP_MAIL'] : "",
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
		// TODO: reenviar a opensso-logout
		redirect('https://opensso-pre.us.es/opensso/UI/Logout');
	}

	/**
	 * Condiciones para la autenticación automática
	 */

	function check_conditions() {
		// TODO: con los servicios web podré hacer algo?
		return isset($_COOKIE['amlbcookie'])  &&
			isset($_COOKIE['iPlanetDirectoryPro']);
	}
}

?>
