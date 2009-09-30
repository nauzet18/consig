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

class Gestionpermisos {

	var $CI;

	function Gestionpermisos() {
		$this->CI =& get_instance();
	}

	/**
	 * Indica si un usuario tiene acceso a un determinado fichero, dado como
	 * su objeto proveniente de la base de datos.
	 *
	 * Acceso permitido en los siguientes casos:
	 *
	 *  1. Usuario autenticado
	 *  2. Fichero con acceso universal
	 *  3. Usuario conectado desde una IP de las contenidas en el fichero de
	 *     subredes
	 *  4. Fichero subido desde una IP interna
	 *  5. Fichero subido por un usuario autenticado
	 *
	 *  @param	objeto fichero sobre el que se quiere hacer la comprobación
	 *	@return si el usuario actual tiene acceso al fichero
	 */

	function acceso_fichero($fichero) {
		if ($this->CI->session->userdata('autenticado') 
				|| $fichero->tipoacceso == 1) {
			return TRUE;
		} else {
			/*
			 * Buscamos IP del usuario que accede,
			 * y si no hay éxito comprobamos la del remitente.
			 *
			 * Realmente tipoacceso debe ser 1 para IPs internas (se hace
			 * así al enviar un fichero), pero puede que hayamos modificado
			 * el fichero de subredes después de que el usuario enviara el
			 * fichero.
			 */

			$ip_remitente = $fichero->ip;
			$ip_usuario = $this->CI->input->ip_address();

			$ips = array($ip_remitente, $ip_usuario);

			return $this->CI->trabajoficheros->busqueda_ips($ips);
		}
	}

	/**
	 * Comprueba si un usuario es privilegiado
	 *
	 * @param	identificador del usuario, opcional. Si no, se busca en la
	 *          sesión actual
	 * @return	TRUE si lo es, FALSE en otro caso
	 */

	function es_privilegiado($id = FALSE) {

		// Paso sin parámetros
		if ($id === FALSE) {
			if ($this->CI->session->userdata('autenticado')) {
				$id = $this->CI->session->userdata('id');
			} else {
				$id = '';
			}
		}

		// Usuario anónimo
		if (empty($id)) {
			return FALSE;
		}

		$privilegiados = $this->CI->config->item('privilegiados');
		for($i=0;$i<count($privilegiados);$i++) {
			if ($privilegiados[$i] == $id) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Determina si el usuario actual tiene permiso para modificar los datos
	 * de un fichero.
	 *
	 */
	function permiso_modificacion($fichero) {
		return ($this->CI->trabajoficheros->es_propietario($fichero) ||
				$this->es_privilegiado());
	}

}
