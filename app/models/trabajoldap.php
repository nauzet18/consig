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
	 * @return	FALSE si falla, -1 si hay problemas con la configuración de 
	 *          LDAP, o un array con los datos del usuario
	 */
	function login($uid, $passwd) {
		$opciones = $this->config->item('ldap');

		 $ds = @ldap_connect($opciones["host"], $opciones["puerto"]);
		 if (!$ds) {
			log_message('error', 'No se puede conectar a LDAP');
			return -1;
		 }

		 // Búsqueda del DN del usuario, para posteriormente hacer bind
		 // con él
		if (@ldap_bind($ds, $opciones['dnadmin'],
					$opciones['passwdadmin']) !== TRUE) {
			log_message('error', 'No se pudo hacer bind. Revise la configuración');
			return -1;
		}

		 $atributos = array('dn', 'sn1', 'givenName', 'UsEsRelacion',
				 'mail');
		 $res = @ldap_search($ds, $opciones['base'],
				 	'uid='.$uid, $atributos);
		 if ($res === FALSE) {
			 return FALSE;
		 }
		 $info = @ldap_get_entries($ds, $res);

		 if ($info['count'] == 0) {
			 return FALSE;
		 }

		 // Una vez conocido el DN, intentamos hacer bind de nuevo
		 $dn_usuario = $info[0]['dn'];

		 $ret = @ldap_bind($ds, $dn_usuario, $passwd);
		 @ldap_unbind($ds);

		 if ($ret !== FALSE) {
			 return $info[0];
		 } else {
			 log_message('info', 'Intento de login fallido. uid='.$uid
				 .', IP: ' . $this->input->ip_address());
			 return FALSE;
		 }
	}

	/**
	 * Extrae la información asociada a un DN de LDAP, prefiriendo los datos
	 * cacheados en la base de datos.
	 *
	 * Un trabajo periódico en cron hará expirar a las entradas que tengan
	 * más de cierto tiempo.
	 *
	 * @param 	string dn 
	 * @param 	int fuerza la actualización de cache para el dn
	 * @return	FALSE si falla, o un array con los datos del usuario
	 */

	function consulta($dn, $forzar = 0) {
		if (!$forzar) {
			$this->db->where('dn', $dn);
			$this->db->from('cacheldap');
			if ($this->db->count_all_results() == 1) {
				$this->db->where('dn', $dn);
				$q = $this->db->get('cacheldap');
				$res = $q->result_array();
				$res[0]['relaciones'] = unserialize($res[0]['relaciones']);
				return $res[0];
			}
		}

		// Si se ha forzado, o bien no se ha encontrado nada...
		$opciones = $this->config->item('ldap');
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

		$atributos = array('dn', 'sn1', 'givenName', 'UsEsRelacion',
				'mail');

		$res = ldap_read($ds, $dn, '(objectClass=*)', $atributos);
		$info = @ldap_get_entries($ds, $res);

		if ($info['count'] == 0) {
			log_message('error', 'Búsqueda en LDAP de usuario inexistente: '
					. $dn);
			return FALSE;
		}

		@ldap_unbind($ds);

		// Ponemos los datos como deseamos
		$datos = array(
				'dn' => $info[0]['dn'],
				'nombre' => ucwords(strtolower($info[0]['givenname'][0] 
						. ' ' .  $info[0]['sn1'][0])),
				'relaciones' => serialize($info[0]['usesrelacion']),
				'mail' => $info[0]['mail'][0],
				'timestamp' => time(),
		);

		// Actualizamos en la BD
		$this->db->where('dn', $dn);
		$this->db->delete('cacheldap'); 
		$this->db->insert('cacheldap', $datos);

		$datos['relaciones'] = unserialize($datos['relaciones']);
		return $datos;
	}
}

?>
