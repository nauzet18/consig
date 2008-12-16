<?php
/*
 * Copyright 2008 Jorge López Pérez <jorge@adobo.org>
 *
 *    This file is part of Consigna.
 *
 *    Foobar is free software: you can redistribute it and/or modify it
 *    under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    Foobar is distributed in the hope that it will be useful, but
 *    WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public
 *    License along with Foobar.  If not, see
 *    <http://www.gnu.org/licenses/>.
 */

class Ficheros extends Controller {

	var $autenticado;

	function Ficheros()
	{
		parent::Controller();	
		$this->autenticado = $this->session->userdata('autenticado');
	}
	
	function index()
	{
		$this->load->view('cabecera');
		$this->load->view('pie');
	}

	/*
	 * Envío de nuevos ficheros
	 *
	 *  Tiene dos modos de funcionamiento: desatendido y atendido.
	 *
	 *  El desatendido es usado al enviar el fichero mediante javascript, de 
	 *  manera que sólo imprime los mensajes de error, o un identificador en 
	 *  caso de haber funcionado todo correctamente
	 *
	 *  El segundo es el habitual, usado para el caso en que javascript no 
	 *  esté habilitado
	 */

	function nuevo() {
		$this->load->helper('form');
		$this->load->library('form_validation');

		// Reglas para el formulario.
		// La comprobación de fichero se hará con $_FILES, ya que con 
		// 'required' no obtenemos lo que queremos
		$this->form_validation->set_rules('fichero_passwd', 'contraseña',
				'callback_passwd_necesario');
		$this->form_validation->set_rules('fichero', 'fichero',
				'callback_fichero_necesario');
		$this->form_validation->set_rules('listar', '', '');
		$this->form_validation->set_rules('tipoacceso', '', '');
		$this->form_validation->set_rules('descripcion', '', '');
		$this->form_validation->set_rules('mostrar_autor', '', '');

		$data_form = array();
		$desatendido = $this->input->post('desatendido');

		// Formulario enviado
		if ($this->input->post('enviar') 
				&& $this->form_validation->run() !== FALSE) {

			$fichero = $_FILES['fichero'];

			// Errores en el envío
			if ($fichero['error'] != UPLOAD_ERR_OK) {
				if ($fichero['error'] == UPLOAD_ERR_INI_SIZE) {
					$data_form['error'] = '<p>El fichero excede el tamaño permitido</p>';
				} else {
					$data_form['error'] = '<p>Hubo un error en el envío (<tt>'.$fichero['error'].'</tt>). 
						Póngase en contacto con el administrador</p>';
				}
			} else {
				/*
				 * Pasos:
				 *
				 *  1. Limpiar el nombre
				 *  2. Limpiar la descripción, si la hubiera
				 *  3. Guardar en BD
				 *  4. Copiar el fichero con un nombre acorde al id obtenido en
				 *     BD
				 *  [ lo siguiente ocurre fuera de este bloque ]
				 *  5. Anuncio del éxito al usuario
				 *  6. TODO: formulario para enviar el fichero a contactos
				 */

				// Limpieza del nombre
				$nombre_fichero = $this->trabajoficheros->limpia_nombre($fichero['name']);

				// Limpieza de la descripción, si la hay
				$descripcion_fichero =
					$this->trabajoficheros->limpia_descripcion($this->input->post('descripcion', TRUE));

				// Guardamos en BD, atendiendo al estado del usuario
				// (autenticado/ no autenticado)
				$passwd_fichero = $this->input->post('fichero_passwd');

				$expiracion_fichero =
					$this->_tiempo_expiracion($this->input->post('expiracion'),
							$this->autenticado);
				$listar_fichero = (!$this->autenticado ? '1' :
						$this->input->post('listar'));
				$tipoacceso_fichero = (!$this->autenticado ? '0' :
						$this->input->post('tipoacceso'));
				$mostrar_autor_fichero = (!$this->autenticado ? '1' :
						$this->input->post('mostrar_autor'));

				// Otras variables generadas para el usuario
				$tam = $fichero['size'];
				$remitente = $this->autenticado ?
					$this->session->userdata('dn') :
					'';
				$ip = $this->input->ip_address();
				$fechaenvio = time();
				$fechaexp = $fechaenvio + $expiracion_fichero;

				$data = array(
						'nombre' => $nombre_fichero,
						'tam' => $tam,
						'remitente' => $remitente,
						'ip' => $ip,
						'fechaenvio' => $fechaenvio,
						'fechaexp' => $fechaexp,
						'listar' => $listar_fichero,
						'mostrar_autor' => $mostrar_autor_fichero,
						'tipoacceso' => $tipoacceso_fichero,
						'password' => $passwd_fichero,
						'descripcion' => $descripcion_fichero,
				);
				$fid = $this->trabajoficheros->almacena_bd($data);

				// Copia del fichero al directorio correspondiente
				// XXX TODO cambiar el valor fijo
				$tmp = $fichero['tmp_name'];
				if (FALSE === move_uploaded_file($tmp,
							'/var/enviados/' . $fid)) {
					// TODO borrarlo de la BD
					$data_form['error'] = '<p>Hubo un problema en la copia
						del fichero. Por favor, comuníquelo al administrador
						de la página.</p>';
				}
			}
		}

		// Si es desatendido, sólo imprimir el identificador que 
		// ha asignado la bd ($this->input->post('desatendido'))
		if (!$desatendido) {
			$data = array(
					'subtitulo' => 'enviar nuevo fichero',
					'body_onload' => 'pagina_envio()',
					'js_adicionales' => array(
						'jquery.timers.js',
						'jquery.blockUI_2.10.js',
					),
			);
			$this->load->view('cabecera', $data);

			// Formulario de envío, si accedemos por primera vez o el envío 
			// anterior falló
			if (!$this->input->post('enviar') || isset($data_form['error']) 
				|| $this->form_validation->run() === FALSE) {

				$id_envio = sha1(microtime() . rand());
				$data_form['mostrar_todo'] = $this->autenticado;
				$data_form['id_envio'] = $id_envio;

				$this->load->view('form-envio-fichero', $data_form);
			} else {
				// TODO
			}


			$this->load->view('pie');
		} else {
			if (isset($data_form['error'])) {
				echo $data_form['error'];
			} else {
				echo $fid;
				//echo "111"; // XXX, id del fichero enviado
			}
		}
	}

	// AJAX: control de velocidad de envíos
	function estado($id) {
        // Buscamos la extensión uploadprogress
        if (!function_exists('uploadprogress_get_info')) {
            echo "nulo";
        } else {
            $info = uploadprogress_get_info($id);

            if ($info == NULL) {
                echo "0;0;0";
            } else {
                $enviados = $info['bytes_uploaded'];
                $total = $info['bytes_total'];

                $porcentaje = round($enviados/$total, 2)*100;
                $velocidad =
                    $this->trabajoficheros->velocidad_envio($info['speed_last']);
                $estimado =
                    $this->trabajoficheros->intervalo_tiempo($info['est_sec']);

                print $porcentaje . ";" . $velocidad . ";" . $estimado;
            }
        }
	}

	/*
	 * Callbacks para validación de formulario de envío.
	 *
	 * La contraseña sólo es necesaria si se ha indicado la opción de
	 * "acceso universal"
	 */
	function passwd_necesario($p) {
		if ($this->input->post('tipoacceso') == 1 && empty($p)) {
			$this->form_validation->set_message('passwd_necesario',
					'Dado que el acceso al fichero será público, debe
					especificar una contraseña para el mismo');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function fichero_necesario($f) {
		if (!isset($_FILES) || empty($_FILES['fichero']['name'])) {
			$this->form_validation->set_message('fichero_necesario',
					'Debe especificar un fichero');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/*
	 * Funciones privadas
	 */

	/*
	 * Devuelve un "offset" para un periodo de expiración dado, ignorándolo
	 * en caso de explicitarse que se trata de un envío anónimo.
	 */

	function _tiempo_expiracion($opcion, $todos_permitidos = 0) {
		$tiempos = array(
				'1h' => 3600,
				'1d' => 86400,
				'1sem' => 604800,
				'2sem' => 1209600,
		);

		// TODO: tiempo por defecto configurable
		if ($todos_permitidos) {
			return (isset($tiempos[$opcion]) ? $tiempos[$opcion] :
					$tiempos['2sem']);
		} else {
			return $tiempos['2sem'];
		}
	}

}

