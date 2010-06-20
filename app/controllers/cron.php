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

class Cron extends Controller {

	var $activar_antivirus;

	function Cron()
	{
		parent::Controller();	

		// Antivirus
		$this->activar_antivirus = $this->config->item('activar_antivirus');
		if ($this->activar_antivirus === TRUE) {
			$this->load->model('antivirus');
		}
	}

	function index() {
		$this->load->model('trabajoficheros');
		$this->load->library('email');

		// 1. Ficheros expirados
		$expirados = $this->trabajoficheros->expirados();

		if ($this->config->item('expiracion_efectiva') === TRUE) {
			foreach ($expirados as $f) {
				// Envío de correo al usuario, si es un fichero con
				// propietario
				if ($this->config->item('correo_caducado') && !empty($f->remitente)) {
					$usuario = $this->auth->get_user_data($f->remitente);
					
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
						.  $this->manejoauxiliar->fecha_legible($f->fechaexp));
			}
		}

		// 2. Entradas en caché de LDAP
		$this->auth->cache_expiration();

		// 3. Antivirus
		if ($this->activar_antivirus === TRUE) {
			/*
			 * Analizamos los ficheros que se encuentren en los supuestos
			 * siguientes:
			 *
			 *  1. Ficheros con estado ERROR
			 *  2. Ficheros con estado LIMPIO y con más de max_clean_rescan
			 *  3. Ficheros con estado PENDIENTE y con más de max_pending
			 */
			
			$fich_err = $this->antivirus->get_where(Antivirus::ERROR);

			$tmax1 = time(NULL) 
				- $this->config->item('antivirus_max_clean_rescan');
			$fich_limpios = $this->antivirus->get_where(Antivirus::CLEAN,
					$tmax1);
			$tmax2 = time(NULL) 
				- $this->config->item('antivirus_max_pending');
			$fich_pendientes =
				$this->antivirus->get_where(Antivirus::PENDING, $tmax2);

			// Encolamos todos
			$tot = array_merge($fich_err, $fich_limpios, $fich_pendientes);
			foreach ($tot as $f) {
				$this->antivirus->enqueue($f->fid);
			}
		}
	}
}

?>
