<?php
/*
 * Copyright 2011 Jorge López Pérez <jorgelp@us.es>
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


class Upgrade extends Controller {

	function Upgrade() {
		parent::Controller();
		$this->load->model('trabajomiscelanea');
	}

	function index() {
		if ($this->config->item('habilitar_upgrade') !== TRUE) {
			show_error('Actualización deshabilitada', 403);
		}

		$data = array(
				'no_mostrar_aviso' => 1,
				'no_mostrar_menu' => 1,
				'no_mostrar_login' => 1,
				'subtitulo' => 'Actualización de la base de datos',
		);
		$this->load->view('cabecera', $data);

		/*
		 * ¿Hay algo que actualizar? Por defecto partimos de la versión
		 * 0 (1.3), que es la primera que implementó este método de
		 * actualización.
		 */
		$versionbd_usandose = $this->trabajomiscelanea->leer('versionbd',
				0);
		if ($versionbd_usandose < VERSIONBD) {
			$this->load->model('actualizacionesbd');
			$this->actualizacionesbd->leer('0');
		}


		$this->load->view('pie');
	}
}
