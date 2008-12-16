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

class TrabajoLDAP extends Model {
	function TrabajoLDAP() {
		parent::Model();
		$this->config->load('ldap');
	}

	/**
	 * Comprueba si un usuario puede autenticarse con el nombre de
	 * usuario y la contraseña dados
	 *
	 * @param 	string uid del usuario que desea autenticarse
	 * @param 	string contraseña del usuario 
	 * @return	FALSE si falla, o un array con los datos del usuario
	 */
	function login($uid, $passwd) {
		$opciones = $this->config->item('ldap');

		 $ds = @ldap_connect($opciones["host"], $opciones["puerto"]);
		 if (!$ds) {
			 // TODO: log o parecido
			 return FALSE;
		 }

		 // Búsqueda del DN del usuario, para posteriormente hacer bind
		 // con él
		if (@ldap_bind($ds, $opciones['dnadmin'],
					$opciones['passwdadmin']) !== TRUE) {
			// TODO: log de configuración errónea
			return FALSE;
		}

		 $atributos = array('dn', 'sn1', 'givenName', 'UsEsRelacion',
				 'mail');
		 $res = @ldap_search($ds, $opciones['base'],
				 	'uid='.$uid);
		 if ($res === FALSE) {
			 // TODO: log o parecido
			 return FALSE;
		 }
		 $info = @ldap_get_entries($ds, $res);

		 if ($info['count'] == 0) {
			 // TODO: log de usuario no encontrado
			 return FALSE;
		 }

		 // Una vez conocido el DN, intentamos hacer bind de nuevo
		 $dn_usuario = $info[0]['dn'];

		 $ret = @ldap_bind($ds, $dn_usuario, $passwd);
		 @ldap_unbind($ds);
		 
		 if ($ret) {
			 return $info[0];
		 } else {
			 // TODO: log de intento fallido
			 return FALSE;
		 }
	}
}

?>
