<?php
/*
 * Copyright 2008 Jorge López Pérez <jorge@adobo.org>
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

class LDAP {
	var $CI;

	function LDAP() {
		$this->CI =& get_instance();
		$this->CI->config->load('ldap');
	}

	/**
	 * Comprueba si un usuario puede autenticarse con el nombre de
	 * usuario y la contraseña dados
	 *
	 * @return	FALSE si falla, o el identificador de usuario final si todo
	 *          es correcto
	 */
	function login_action(&$err) {
		$opciones = $this->CI->config->item('ldap');

		 $ds = @ldap_connect($opciones["host"], $opciones["puerto"]);
		 if (!$ds) {
			log_message('error', 'No se puede conectar a LDAP');
			$err = 'Existe un problema temporal con la autenticación. '
				 .'Por favor, pruebe más tarde.';
			return FALSE;
		 }

		 // Recogida de valores (cuidado con XSS) y comprobación de
		 // validez
		 $usuario = $this->CI->input->post('usuario', TRUE);
		 $passwd = $this->CI->input->post('passwd', TRUE);

		 // Quitamos @ del usuario
		 $usuario = preg_replace('/@.*$/', '', $usuario);


		 // Búsqueda del DN del usuario, para posteriormente hacer bind
		 // con él
		if (@ldap_bind($ds, $opciones['dnadmin'],
					$opciones['passwdadmin']) !== TRUE) {
			log_message('error', 'No se pudo hacer bind. Revise la configuración');
			$err = 'Existe un problema temporal con la autenticación. '
				 .'Por favor, pruebe más tarde.';
			return FALSE;
		}

		 $atributos = array('dn');
		 $res = @ldap_search($ds, $opciones['base'],
				 	'uid='.$usuario, $atributos);
		 if ($res === FALSE) {
			 $err = 'Nombre de usuario o contraseña erróneos';
			 return FALSE;
		 }
		 $info = @ldap_get_entries($ds, $res);

		 if ($info['count'] == 0) {
			 $err = 'Nombre de usuario o contraseña erróneos';
			 return FALSE;
		 }

		 // Una vez conocido el DN, intentamos hacer bind de nuevo
		 $dn_usuario = $info[0]['dn'];

		 $ret = @ldap_bind($ds, $dn_usuario, $passwd);
		 @ldap_unbind($ds);

		 if ($ret !== FALSE) {
			 return $dn_usuario;
		 } else {
			 log_message('info', 'Intento de login fallido. uid='.$usuario
				 .', IP: ' . $this->CI->input->ip_address());
			 $err = 'Nombre de usuario o contraseña erróneos';
			 return FALSE;
		 }
	}

	/**
	 * Extrae la información asociada a un DN de LDAP
	 *
	 * @param 	string dn 
	 * @return	FALSE si falla, o un array con los datos del usuario
	 */

	function get_user_data($id) {

		$opciones = $this->CI->config->item('ldap');
		$ds = @ldap_connect($opciones["host"], $opciones["puerto"]);
		if (!$ds) {
			log_message('error', 'No se puede conectar a LDAP');
			return FALSE;
		}

		if (@ldap_bind($ds, $opciones['dnadmin'],
					$opciones['passwdadmin']) !== TRUE) {
			log_message('error', 'No se pudo hacer bind. Revise la configuración');
			return FALSE;
		}

		$atributos = array('dn', 'sn', 'givenName', 'mail');

		$res = ldap_read($ds, $id, '(objectClass=*)', $atributos);
		$info = @ldap_get_entries($ds, $res);

		if ($info['count'] == 0) {
			log_message('error', 'Búsqueda en LDAP de usuario inexistente: '
					. $id);
			return FALSE;
		}

		@ldap_unbind($ds);

		// Ponemos los datos como deseamos
		$datos = array(
				'id' => $info[0]['dn'],
				'name' => ucwords(strtolower($info[0]['givenname'][0] 
						. ' ' .  $info[0]['sn'][0])),
				'mail' => $info[0]['mail'][0],
				'timestamp' => time(),
		);

		return $datos;
	}

	/**
	 * Expira las entradas en la caché que tengan más de 12h
	 * TODO tiempo configurable
	 */
	function cache_expiration() {
		$fechaexp = time() - 12 * 60 * 60;
		$this->CI->db->where('timestamp <=', $fechaexp);
		$this->CI->db->delete('usercache'); 
	}

	/**
	 * Form fields
	 */

	function has_form() {
		return TRUE;
	}

	/**
	 * Logout
	 */
	function logout() {
	}
}

?>