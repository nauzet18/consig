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

// Errores de procesado de formulario al enviar un fichero o editar uno
// existente
define('PROCESADO_OK', 1);
define('PROCESADO_ERR_FORMULARIO', 2);
define('PROCESADO_ERR_ESCRITURA', 3);


class Ficheros extends Controller {

	var $autenticado;

	function Ficheros()
	{
		parent::Controller();	
		$this->autenticado = $this->session->userdata('autenticado');
		$this->load->config('subredes');
	}
	
	function index()
	{
		$data = array(
				'js_adicionales' => array(
					'jquery.quicksearch.pack.js'
				),
				'css_adicionales' => array(
					'jquery.cluetip.css',
				),
				'body_onload' => 'formulario_busqueda()',
		);
		$this->load->view('cabecera', $data);
		$ficheros = $this->trabajoficheros->extrae_bd();
		$data = array(
				'ficheros' => $ficheros,
		);
		$this->load->view('listado-ficheros', $data);
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

		$data_form = array();
		$fid = null;

		$desatendido = $this->input->post('desatendido');

		// Formulario enviado
		if ($this->input->post('enviar')) {
			$resultado = $this->_procesado_envio_fichero('nuevo',
					$data_form, $fid);
		}

		// Si es desatendido, sólo imprimir el identificador que 
		// ha asignado la bd ($this->input->post('desatendido'))
		if (!$desatendido) {
			$data = array(
					'subtitulo' => 'enviar nuevo fichero',
					'body_onload' => 'pagina_envio()',
					'no_mostrar_aviso' => TRUE,
					'js_adicionales' => array(
						'jquery.timers.js',
						'jquery.blockUI_2.10.js',
						'js/jquery.dimensions.js',
					),
			);
			$this->load->view('cabecera', $data);

			// Formulario de envío, si accedemos por primera vez o el envío 
			// anterior falló
			if (!$this->input->post('enviar') || isset($data_form['error']) 
				|| $resultado == PROCESADO_ERR_FORMULARIO) {

				$id_envio = sha1(microtime() . rand());
				$data_form['mostrar_todo'] = $this->autenticado;
				$data_form['id_envio'] = $id_envio;

				$this->load->view('form-envio-fichero', $data_form);
			} else {
				$data = array(
						'fid' => $fid
				);
				$this->load->view('fichero-enviado-nojs', $data);
			}


			$this->load->view('pie');
		} else {
			if (isset($data_form['error'])) {
				echo $data_form['error'];
			} else {
				// Mensaje de éxito
				$this->session->set_flashdata('mensaje_fichero',
						'El fichero fue enviado correctamente');
				echo $fid;
			}
		}
	}

	// AJAX: control de velocidad de envíos
	function estado($id) {
        // Buscamos la extensión uploadprogress
        if (!function_exists('uploadprogress_get_info')) {
            echo "noimplementado";
        } else {
            $info = uploadprogress_get_info($id);

            if ($info == NULL) {
                echo "nulo";
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
	 * Visualización y descarga de un fichero.
	 *
	 * Si el usuario está autenticado y es su fichero, podrá enviar el
	 * enlace a las direcciones de correo que especifique
	 */

	function ver_fichero($fid, $descargar = '') {
        $fichero = $this->trabajoficheros->extrae_bd($fid);

        if ($fichero === FALSE) {
			show_error('El fichero indicado no existe. Es posible que '
					.'existiera y haya caducado.');
            return;
        }
		
		// Para decidir las acciones
		$data_cabecera = array(
				'no_mostrar_aviso' => 1
		);

		$permiso = $this->trabajoficheros->acceso_fichero($fichero);
		$decision_password = $this->trabajoficheros->comprueba_passwd($fichero,
				$this->input->post('passwd-fichero'));
		$pide_descarga = $descargar == 'descarga';

		// Casuística
		if ($permiso) {
			$data_fichero = array(
					'fichero' => $fichero,
			);

			if ($pide_descarga) {
				if ($decision_password) {
					// Correcto. Enviamos el fichero
					$this->trabajoficheros->fuerza_descarga($fichero);
					return;
				} else {
					// Se equivocó en la contraseña
					$data_fichero['error'] = 'La contraseña especificada
						no es válida.';
				}

			} // pide_descarga
			if ($this->trabajoficheros->permiso_modificacion($fichero)) {
				$data_fichero['permiso_modificacion'] = 1;
			}
				
			$data_cabecera['subtitulo'] = 'ojeando un fichero';
			$data_cabecera['body_onload'] = 'pagina_descarga()';

			$this->load->view('cabecera', $data_cabecera);
			$this->load->view('ver_fichero', $data_fichero);
			$this->load->view('pie');
		} else {
			// Acceso denegado
			$data_cabecera['subtitulo'] = 'acceso denegado';

			$this->load->view('cabecera', $data_cabecera);
			$this->load->view('ver_fichero_denegado');
			$this->load->view('pie');
		}

	}

	/*
	 * Muestra los ficheros del usuario actual
	 */
	function propios() {
		if (!$this->autenticado) {
            show_error('Debe autenticarse para poder ver sus ficheros.');
		} else {
			$data = array(
					'js_adicionales' => array(
						'jquery.quicksearch.pack.js'
					),
					'css_adicionales' => array(
						'jquery.cluetip.css',
					),
					'body_onload' => 'formulario_busqueda()',
			);
			$this->load->view('cabecera', $data);
			$ficheros = $this->trabajoficheros->extrae_bd(0, 1,
					$this->session->userdata('dn'));
			$data = array(
					'titulo' => 'Sus ficheros enviados',
					'ficheros' => $ficheros,
			);
			$this->load->view('listado-ficheros', $data);
			$this->load->view('pie');
		}
	}

	/*
	 * Edición de un fichero
	 */

	function modificar($fid) {
        $fichero = $this->trabajoficheros->extrae_bd($fid);

        if ($fichero === FALSE) {
            show_error('El fichero indicado no existe.');
            return;
        }

		if (!$this->trabajoficheros->es_propietario($fichero)) {
			show_error('No tiene permiso para modificar el fichero.');
			return;
		} else {
			$this->load->helper('form');
			$this->load->library('form_validation');
			$data_form = array(
					'fichero' => $fichero,
			);

			// Formulario enviado
			if ($this->input->post('enviar')) {
				$resultado = $this->_procesado_envio_fichero('modificar',
						$data_form, $fid);
			}
			
			// ¿Errores?
			if (!$this->input->post('enviar') || isset($data_form['error']) 
				|| $resultado == PROCESADO_ERR_FORMULARIO) {

				$data = array(
						'subtitulo' => 'modificar fichero',
						'body_onload' => 'pagina_modificacion()',
				);
				$this->load->view('cabecera', $data);
				$this->load->view('form-modif-fichero', $data_form);
				$this->load->view('pie');
			} else {
				// Mensaje de éxito
				$this->session->set_flashdata('mensaje_fichero',
						'El fichero fue modificado');
				redirect('/ficheros/' . $fid);
			}

		}
	}


	/**
	 * Borrado de un fichero a petición de su remitente
	 */
	function borrar($fid) {
		$fichero = $this->trabajoficheros->extrae_bd($fid);

        if ($fichero === FALSE) {
            show_error('El fichero indicado no existe.');
            return;
		} elseif (!$this->trabajoficheros->es_propietario($fichero)) {
			show_error('No tiene permiso para borrar el fichero.');
			return;
		} else {
			// ¿Confirmación?
			if ($this->input->post('confirmacion')) {
                $this->trabajoficheros->elimina_fichero($fichero->fid, 
                        'El usuario pide su eliminación');
				$this->session->set_flashdata('mensaje_fichero_cabecera',
						'El fichero fue eliminado');
                redirect('');
			} else {
				$data_cabecera = array(
						'subtitulo' => 'borrado de un fichero',
				);
				$this->load->view('cabecera', $data_cabecera);
				
				$data_form = array(
						'fichero' => $fichero,
				);
				$this->load->view('form-confirmacion-borrado', $data_form);
				$this->load->view('pie');
			}
		}
	}


	/*
	 * Minipágina para los bocadillos en el listado de ficheros
	 */
	function minipagina($fid) {
        $fichero = $this->trabajoficheros->extrae_bd($fid);

        if ($fichero === FALSE) {
			echo "Fichero inexistente o caducado";
        } else {
			$permitido = $this->trabajoficheros->acceso_fichero($fichero);

			if ($permitido === FALSE) {
				echo "Prohibido";
			} else {
				$mimetype = $this->trabajoficheros->consigue_mimetype($fichero->nombre);
				echo '<strong>' . $fichero->nombre . '</strong>';
				echo '
				<img src="' . site_url('img/tipos/16x16/' . $mimetype[1]) . '"
				alt="' . $mimetype[0] . '"/>';

				if ($fichero->listar == 0) {
					echo ' (oculto)';
				}

				echo '<div class="descripcion_fichero">';
				echo empty($fichero->descripcion) ? 'Sin descripción' :
					$fichero->descripcion;
				echo '</div>';
			}
		}
	}


	/*
	 * Funciones privadas
	 */

	/*
	 * Devuelve un "offset" para un periodo de expiración dado, o el offset
	 * del tiempo de expiración por defecto en caso de ser un envío anónimo.
	 */

	function _tiempo_expiracion($opcion, $todos_permitidos = 0) {
		$tiempos = array(
				'1h' => 3600,
				'1d' => 86400,
				'1sem' => 604800,
				'2sem' => 1209600,
		);
		
		$tiempo_defecto = $this->config->item('expiracion_defecto');

		if ($todos_permitidos) {
			return (isset($tiempos[$opcion]) ? $tiempos[$opcion] :
					$tiempos[$tiempo_defecto]);
		} else {
			return $tiempos[$tiempo_defecto];
		}
	}

	/*
	 * Callbacks para validación de formulario de envío.
	 *
	 * La contraseña sólo es necesaria si se ha indicado la opción de
	 * "acceso universal"
	 */
	function _passwd_necesario($p) {
		if ($this->input->post('tipoacceso') == 1 && empty($p)) {
			$this->form_validation->set_message('_passwd_necesario',
					'Dado que el acceso al fichero será público, debe
					especificar una contraseña para el mismo');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function _fichero_necesario($f) {
		if (!isset($_FILES) || empty($_FILES['fichero']['name'])) {
			$this->form_validation->set_message('_fichero_necesario',
					'Debe especificar un fichero');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function _passwd_necesario_modificacion($p) {
		if ($this->input->post('tipoacceso') == 1) {
			
			if ($this->input->post('eliminar_passwd') == 1) {
				$this->form_validation->set_message(
						'_passwd_necesario_modificacion',
						'Dado que el acceso al fichero será público, debe
						especificar una contraseña para el mismo');
				return FALSE;
			} elseif ($this->input->post('eliminar_passwd') === FALSE) {
				// ¿No ha rellenado la contraseña y la tenía en blanco?
				$fichero =
					$this->trabajoficheros->extrae_bd($this->input->post('fid'));
				if (empty($p) &&
						empty($fichero->password)) {

					$this->form_validation->set_message(
							'_passwd_necesario_modificacion',
							'Dado que el acceso al fichero será público, debe
							especificar una contraseña para el mismo');
					return FALSE;
				}
				return TRUE;
			}
		} else {
			return TRUE;
		}
	}

	/*
	 * Procesado de valores para añadir / editar un fichero, marcando los
	 * errores para el módulo de formularios de CodeIgniter según el tipo de
	 * procesado
	 */

	function _procesado_envio_fichero($tipo, &$data_form, &$fid) {

		// Reglas para el formulario.
		// La comprobación de fichero se hará con $_FILES, ya que con 
		// 'required' no obtenemos lo que queremos
		$this->form_validation->set_rules('listar', '', '');
		$this->form_validation->set_rules('tipoacceso', '', '');
		$this->form_validation->set_rules('descripcion', '', '');
		$this->form_validation->set_rules('mostrar_autor', '', '');
		$this->form_validation->set_rules('expiracion', '', '');


		if ($tipo == 'nuevo') {
			$this->form_validation->set_rules('fichero', 'fichero',
					'callback__fichero_necesario');
			$this->form_validation->set_rules('fichero_passwd', 'contraseña',
					'callback__passwd_necesario');
		} else {
			$this->form_validation->set_rules('fichero_passwd', 'contraseña',
					'callback__passwd_necesario_modificacion');
		}


		$resultado = $this->form_validation->run();
		if ($resultado === TRUE) {

			// Array para almacenar en BD
			$data = array();

			if ($tipo == 'nuevo') {
				$fichero = $_FILES['fichero'];

				// Errores en el envío
				if ($fichero['error'] != UPLOAD_ERR_OK) {
					if ($fichero['error'] == UPLOAD_ERR_INI_SIZE) {
						$data_form['error'] = '<p>El fichero excede el tamaño permitido</p>';
					} else {
						$data_form['error'] = '<p>Hay problemas para enviar
							(código <tt>'.$fichero['error'].'</tt>). 
							Póngase en contacto con el administrador</p>';
					}

					return PROCESADO_ERR_ESCRITURA;
				}

				// Limpieza del nombre
				$data['nombre'] = $this->trabajoficheros->limpia_nombre($fichero['name']);
				$data['tam'] = $fichero['size'];

				// Remitente, IP y fechas
				$data['remitente'] = $this->autenticado ?
					$this->session->userdata('dn') :
					'';
				$data['ip'] = $this->input->ip_address();

			} else {
				// Cargamos de la base de datos lo referente al fichero que
				// estemos editando
				$fid = $this->input->post('fid', TRUE);
				if ($fid === FALSE) {
					$data_form['error'] = '<p>Hay algún problema con el
						formulario de edición. Por favor, comuníquelo al administrador
						de la página.</p>';
					return PROCESADO_ERR_FORMULARIO;
				}

				$actual =
					$this->trabajoficheros->extrae_bd($fid);
				if ($actual === FALSE) {
					$data_form['error'] = '<p>Fichero inexistente.</p>';
					return PROCESADO_ERR_FORMULARIO;
				}

				$data['fid'] = $fid;
			}


			// Limpieza de la descripción, si la hay
			$data['descripcion'] =
				$this->trabajoficheros->limpia_descripcion($this->input->post('descripcion', TRUE));

			// Contraseña
			if ($tipo == 'nuevo') {
				$data['password'] = $this->input->post('fichero_passwd');
			} else {
				$passwd_post = $this->input->post('fichero_passwd');
				if (!empty($passwd_post)) {
					$data['password'] = $passwd_post;
				} elseif ($this->input->post('eliminar_passwd') == 1) {
					$data['password'] = '';
				}
			}

			if ($tipo == 'nuevo') {
				$fechaenvio = time();
				$expiracion_fichero =
					$this->_tiempo_expiracion($this->input->post('expiracion'),
							$this->autenticado);
				$fechaexp = $fechaenvio + $expiracion_fichero;
				$data['fechaenvio'] = $fechaenvio;
				$data['fechaexp'] = $fechaexp;
			} else {
				// TODO: actualizaciones de fecha de expiración?
			}

			$listar_fichero = (!$this->autenticado ? '1' :
					$this->input->post('listar'));
			$tipoacceso_fichero = (!$this->autenticado ? '0' :
					$this->input->post('tipoacceso'));
			$mostrar_autor_fichero = (!$this->autenticado ? '1' :
					$this->input->post('mostrar_autor'));


			// Caso excepcional de acceso a fichero: no autenticado,
			// pero con IP interna
			if (!$this->autenticado &&
					$this->trabajoficheros->busqueda_ips(array(
							$this->input->ip_address()
							)))
				{
					$tipoacceso_fichero = 1;
				}

			$data['listar'] = $listar_fichero;
			$data['mostrar_autor'] = $mostrar_autor_fichero;
			$data['tipoacceso'] = $tipoacceso_fichero;

			// Devolverá el fid nuevo, o el actual en caso de estar
			// actualizando un fichero
			$fid = $this->trabajoficheros->almacena_bd($data);

			if ($tipo == 'nuevo') {
				// Copia del fichero al directorio correspondiente
				$tmp = $fichero['tmp_name'];
				if (FALSE === @move_uploaded_file($tmp,
							$this->config->item('directorio_ficheros')
							. '/' . $fid)) {

					// Borrado de la BD
					$this->trabajoficheros->elimina_bd($fid);
					$data_form['error'] = '<p>Hay problemas con la copia
						de ficheros. Por favor, comuníquelo al administrador
						de la página.</p>';
					return PROCESADO_ERR_ESCRITURA;
				}

				return PROCESADO_OK;
			} // tipo = nuevo, copia de fichero
		} else {
			// Se rellenó mal el formulario
			return PROCESADO_ERR_FORMULARIO;
		}
	}

}

