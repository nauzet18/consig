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

		// TODO: caso vacío
		if ($authmod !== FALSE && !empty($authmod)) {
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
	 * Consulta de los datos de usuario
	 *
	 * @param string	Identificador de usuario
	 * @param boolean	Forzar el refresco de caché
	 */

	function get_user_data($id, $force_reload = FALSE) {
		$this->CI->db->query("LOCK TABLES usercache WRITE");
		if (!$force_reload) {
			$this->CI->db->where('id', $id);
			$this->CI->db->from('usercache');
			$q = $this->CI->db->get();
			$res = $q->result_array();
			if (count($res) != 0) {
				$this->CI->db->query("UNLOCK TABLES");
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

		$this->CI->db->query("UNLOCK TABLES");

		return $data;
	}

	/**
	 * Muestra el formulario de login
	 *
	 * @param array	Parámetros de configuración del formulario en forma de
	 * array asociativo. Parámetros posibles:
	 *
	 *   'devolver_a': URL a la que devolver al usuario
	 *   'error': Errores a mostrar del intento de autenticación anterior
	 */
	function show_form($data_form) {
			$this->CI->load->helper('form');
			$data_cabecera = array(
					'subtitulo' => 'autenticación',
					'no_mostrar_aviso' => TRUE,
					'no_mostrar_login' => TRUE,
					'body_onload' => 'pagina_login()',
					);
			$data_pie = array();

			$this->CI->load->view('cabecera', $data_cabecera);
			if ($this->CI->config->item('https_para_login') == TRUE) {
				$url_login = preg_replace('/^http:/', 'https:',
						site_url('usuario/login')); 
			} else {
				$url_login = site_url('usuario/login');
			}

			$data_form['url_login'] = $url_login;

			$this->CI->load->view('form-login', $data_form);
			$this->CI->load->view('pie', $data_pie);
	}

	/**
	 * Guarda la sesión del usuario en forma de cookie
	 *
	 * @param string	Identificador de usuario
	 */

	function store_session($id) {
		if (empty($id)) {
			show_error('Hay problemas con la autenticación. Póngase en '
					.'contacto con ' .
					$this->CI->config->item('texto_contacto'), 500);
			log_message('error', 'Se intenta guardar sesión sin id');
			exit;
		}

		// Recogemos los datos del usuario
		$data = $this->get_user_data($id, TRUE);
		$data['autenticado'] = TRUE;
		$this->CI->session->set_userdata($data);
	}
}

?>
