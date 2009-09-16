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

class Ayuda extends Controller {
	function Ayuda() {
		parent::Controller();
	}

	function index() {
		$data = array(
				'no_mostrar_aviso' => 1,
				'subtitulo' => 'ayuda',
		);
		$this->load->view('cabecera', $data);

		$this->load->view('manual');

		$this->load->view('pie');
	}

	function legal() {
		$data = array(
				'no_mostrar_aviso' => 1,
				'subtitulo' => 'condiciones de uso',
		);
		$this->load->view('cabecera', $data);

		$this->load->view('condiciones_uso');

		$this->load->view('pie');
	}
}

?>
