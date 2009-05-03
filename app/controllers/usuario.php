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

class Usuario extends Controller {

	var $autenticado;

	function Usuario()
	{
		parent::Controller();
		$this->autenticado = $this->session->userdata('autenticado');
	}
	
	function index()
	{
		if ($this->autenticado !== FALSE) {
			redirect('ficheros/propios');
		} else {
			redirect('usuario/login');
		}
	}

	// Sección de autenticación
	function login() {
		$final_id = FALSE;
		$has_form = $this->auth->has_form();
		$err = '';

		if ($has_form === FALSE) {
			$final_id = $this->auth->login_action($err);
		} else {
			// Formulario
			$this->load->helper('form');
			$this->load->library('form_validation');

			$data_cabecera = array(
					'subtitulo' => 'autenticación',
					'no_mostrar_aviso' => TRUE,
					'no_mostrar_login' => TRUE,
					'body_onload' => 'pagina_login()',
					);
			$data_form = array();
			$data_pie = array();

			// Reglas para el formulario
			$this->form_validation->set_rules('usuario', 'Usuario',
					'required');
			$this->form_validation->set_rules('passwd', 'Contraseña',
					'required');

			// ¿Envío de formulario?
			if ($this->input->post('login') 
					&& $this->form_validation->run() !== FALSE) {
				$final_id = $this->auth->login_action($err);
			}
		} // has_form

		if ($final_id !== FALSE) {
			$data = $this->auth->get_user_data($final_id, TRUE);
			$data['autenticado'] = TRUE;

			$this->session->set_userdata($data);

			redirect($this->input->post('devolver'));
		} else {

			if ($has_form) {
				// Muestra del formulario
				$this->load->view('cabecera', $data_cabecera);
				if ($this->config->item('https_para_login') == TRUE) {
					$url_login = preg_replace('/^http:/', 'https:',
							site_url('usuario/login')); 
				} else {
					$url_login = site_url('usuario/login');
				}

				$data_form['url_login'] = $url_login;

				// ¿Hubo errores?
				if (!empty($err)) {
					$data_form['error'] = $err;
				}


				$this->load->view('form-login', $data_form);
				$this->load->view('pie', $data_pie);
			} else {
				show_error($err);
			}
		}
	}

	// Salir (logout)
	function salir() {
		if ($this->autenticado) {
			// Posible bug en CI 1.7.0 con sess_destroy()
			// Eliminamos los valores
			$data = array(
				'id' => '',
				'name' => '',
				'mail' => '',
				'autenticado' => FALSE,
			);
			$this->session->unset_userdata($data);
			$this->session->sess_destroy();
		}

		// Acciones adicionales
		$this->auth->logout();

		redirect('');
	}
}
?>
