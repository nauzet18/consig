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
		$this->load->model('trabajoldap');
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

			// Recogida de valores (cuidado con XSS) y comprobación de
			// validez
			$usuario = $this->input->post('usuario', TRUE);
			$passwd = $this->input->post('passwd', TRUE);

			$res = $this->trabajoldap->login($usuario, $passwd);

			if ($res === FALSE) {
				$data_form['error'] = 'Nombre de usuario o contraseña
					erróneos';
			} elseif ($res == -1) {
				$data_form['error'] = 'Hay problemas actualmente con la autenticación. Por favor, inténtelo más tarde';
			} else {
				// Sólo nos interesan unos cuantos datos
				$datos = array(
						'autenticado' => TRUE,
						'dn' => $res['dn'],
						'nombre' => ucwords(strtolower($res['givenname'][0] 
							. ' ' .  $res['sn1'][0])),
						'relaciones' => $res['usesrelacion'],
						'mail' => $res['mail'][0],
				);

				$this->session->set_userdata($datos);

				redirect($this->input->post('devolver'));
			}

		}

		// Muestra del formulario
		$this->load->view('cabecera', $data_cabecera);

		if ($this->config->item('https_para_login') == TRUE) {
			$url_login = preg_replace('/^http:/', 'https:',
					site_url('usuario/login')); 
		} else {
			$url_login = site_url('usuario/login');
		}

		$data_form['url_login'] = $url_login;


		$this->load->view('form-login', $data_form);
		$this->load->view('pie', $data_pie);
	}

	// Salir (logout)
	function salir() {
		if ($this->autenticado) {
			// Posible bug en CI 1.7.0 con sess_destroy()
			// Eliminamos los valores
			$data = array(
				'dn' => '',
				'nombre' => '',
				'relaciones' => array(),
				'mail' => '',
				'autenticado' => FALSE,
			);
			$this->session->unset_userdata($data);
			$this->session->sess_destroy();
		}

		redirect('');
	}
}
?>
