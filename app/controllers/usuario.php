<?php
/*
 * Copyright 2008 Jorge López Pérez <jorgelp@us.es>
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

class Usuario extends CI_Controller {

	var $autenticado;

	function __construct()
	{
		parent::__construct();
		$this->autenticado = $this->session->userdata('autenticado');

		// Si no hay módulo de autenticación, devolver un error
		if ($this->config->item('authmodule') == "") {
			show_error('La autenticación está desactivada', 404);
			return;
		}
	}
	
	function index()
	{
		if ($this->autenticado !== FALSE) {
			redirect('ficheros/propios');
		} else {
			redirect('usuario/login');
		}
	}


	/**
	 * Presenta la página de login si el módulo actual tiene definido un
	 * formulario, o intenta la validación en caso contrario
	 */

	function login() {
		$final_id = '';
		$ret_action = -1;
		$has_form = $this->auth->has_form();
		$err = '';

		/*
		 * Página de vuelta
		 *
		 * Prioridades:
		 *
		 * login_devolver_a > POST > GET
		 */
		
		$devolver_a = $this->session->flashdata('login_devolver_a');
		if ($devolver_a === FALSE) {
			$devolver_a = $this->input->post('devolver');
			if ($devolver_a === FALSE) {
				parse_str($_SERVER['QUERY_STRING'], $_GET); 
				$devolver_a = $this->input->get('devolver');
			}
		}

		if ($has_form === FALSE) {
			$ret_action = $this->auth->login_action($err, $final_id);
		} else {
			// Formulario
			$this->load->helper('form');
			$this->load->library('form_validation');

			// Reglas para el formulario
			$this->form_validation->set_rules('usuario', 'Usuario',
					'required');
			$this->form_validation->set_rules('passwd', 'Contraseña',
					'required');

			// ¿Envío de formulario?
			if ($this->input->post('login') 
					&& $this->form_validation->run() !== FALSE) {
				$ret_action = $this->auth->login_action($err, $final_id);
			}
		} // has_form

		if ($ret_action == 1) {
			$this->auth->store_session($final_id);
			redirect($devolver_a);
		} elseif ($ret_action == -1) {
			if ($has_form) {
				// Muestra del formulario
				$data_form = array(
						'devolver_a' => $devolver_a
				);

				// ¿Hubo errores?
				if (!empty($err)) {
					$data_form['error'] = $err;
				}

				$this->auth->show_form($data_form);
			} else {
				show_error($err);
			}
		} else {
			// Resto de códigos de login_action disponibles para hacer
			// redirecciones
		}
	}

	// Salir (logout)
	function salir() {
		$this->auth->logout();

		redirect('');
	}
}
?>
