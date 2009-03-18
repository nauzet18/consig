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

class Cron extends Controller {

	function Cron()
	{
		parent::Controller();	
	}

	function index() {
		$this->load->model('trabajoficheros');
		$this->load->model('trabajoldap');
		$this->load->library('email');

		// 1. Ficheros expirados
		$expirados = $this->trabajoficheros->expirados();

		if ($this->config->item('expiracion_efectiva') === TRUE) {
			foreach ($expirados as $f) {
				// Envío de correo al usuario, si es un fichero con
				// propietario
				if ($this->config->item('correo_caducado') && !empty($f->remitente)) {
					$usuario = $this->trabajoldap->consulta($f->remitente,
							1);
					
					if (!empty($usuario['mail'])) {
						$correo_auto =
							$this->config->item('direccion_correo_automatico');
						$this->email->clear();

						$this->email->from($correo_auto, 'Servicio de consigna');
						$this->email->to($usuario['mail']);

						$this->email->subject('Fichero caducado');
						$this->email->message($this->load->view('email-fichero-caducado',
									array('nombre_fichero' => $f->nombre),
									TRUE));
						$this->email->send();
					}

				}


				// Borrado en sí
				$this->trabajoficheros->elimina_fichero($f->fid, 'Expira el '
						.  $this->trabajoficheros->fecha_legible($f->fechaexp));

			}
		}

		// 2. Entradas en caché de LDAP
		$this->trabajoldap->expira_cache();
	}
}

?>
