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

if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Auth {
	var $CI;

	function Auth() {
		$this->CI =& get_instance();
		// Carga del módulo de autenticación correspondiente
		$authmod = $this->CI->config->item('authmodule');

		if ($authmod === FALSE || empty($authmod)) {
			log_message('error', 'El módulo de autenticación está vacío');
			return FALSE;
		} else {
			$this->authmod = $authmod;
			// Cargamos en '$this->authmod'
			$this->CI->load->library('authmodules/' . $authmod,
					array(),
					'authmod');
		}
	}

	function has_form() {
		return $this->CI->authmod->has_form();
	}

	function login_action(&$err) {
		$id = '';
		$ret = $this->CI->authmod->login_action($err, $id);

		if ($ret === FALSE) {
			log_message('info', 'Intento de login fallido. id=' . $id
					.', IP: ' . $this->CI->input->ip_address());
		} else {
			log_message('info', 'Login correcto. id=' . $ret
					.', IP: ' . $this->CI->input->ip_address());
		}

		return $ret;
	}

	function cache_expiration() {
		$this->CI->authmod->cache_expiration();
	}

	function logout() {
		if ($this->CI->session->userdata('autenticado')) {
			log_message('info', 'Logout. id=' .
					$this->CI->session->userdata('id')
					.', IP: ' . $this->CI->input->ip_address());
			// Posible bug en CI 1.7.0 con sess_destroy()
			// Eliminamos los valores
			$data = array(
					'id' => '',
					'name' => '',
					'mail' => '',
					'autenticado' => FALSE,
					);
			$this->CI->session->unset_userdata($data);
			$this->CI->session->sess_destroy();
		}

		$this->CI->authmod->logout();
	}



	/**
	 * Optional cached query
	 */

	function get_user_data($id, $force_reload = FALSE) {
		if (!$force_reload) {
			$this->CI->db->where('id', $id);
			$this->CI->db->from('usercache');
			if ($this->CI->db->count_all_results() == 1) {
				$this->CI->db->where('id', $id);
				$q = $this->CI->db->get('usercache');
				$res = $q->result_array();
				return $res[0];
			}
		}

		$data = $this->CI->authmod->get_user_data($id);

		if ($data !== FALSE) {
			// Actualizamos en la BD
			$this->CI->db->where('id', $id);
			$this->CI->db->delete('usercache'); 
			$this->CI->db->insert('usercache', $data);
		}

		return $data;
	}
}

?>
