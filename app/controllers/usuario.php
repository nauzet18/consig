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

class Usuario extends Controller {

	var $autenticado;

	function Usuario()
	{
		parent::Controller();
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
		$final_id = FALSE;
		$has_form = $this->auth->has_form();
		$err = '';

		// Página de vuelta
		// Quizás tengamos una URL de vuelta en forma de cookie
		$devolver_a = $this->session->flashdata('login_devolver_a');
		if ($devolver_a === FALSE) {
			$devolver_a = $this->input->post('devolver');
		}

		if ($has_form === FALSE) {
			$final_id = $this->auth->login_action($err);
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
				$final_id = $this->auth->login_action($err);
			}
		} // has_form

		if ($final_id !== FALSE) {
			$this->auth->store_session($final_id);
			redirect($devolver_a);
		} else {

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
		}
	}

	// Salir (logout)
	function salir() {
		$this->auth->logout();

		redirect('');
	}
}
?>
