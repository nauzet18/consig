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

// Errores de procesado de formulario al enviar un fichero o editar uno
// existente
define('PROCESADO_OK', 1);
define('PROCESADO_ERR_FORMULARIO', 2);
define('PROCESADO_ERR_ESCRITURA', 3);


class Ficheros extends Controller {

	var $autenticado;
	var $antivirus;

	function Ficheros()
	{
		parent::Controller();	
		$this->autenticado = $this->session->userdata('autenticado');
		$this->gestionpermisos->checkLogin();
		$this->load->config('subredes');
		
		// Usando antivirus
		$antivirus = $this->config->item('activar_antivirus');
		if ($antivirus === TRUE) {
			$this->load->model('antivirus');
		}
	}
	
	function index($atr_orden = 'fechaenvio', $orden = 'desc')
	{
		$this->load->library('pagination');
		$this->load->library('manejoauxiliar');

		$opciones = array(
				'atr_orden' => $atr_orden,
				'orden' => $orden,
				'filtros' => array(
					'listar' => 1
				),
				'busquedas' => array(),
				'seccion' => 'index',
				'caja_busqueda' => 1,
		);


		$this->_presentar_listado($opciones);
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
	 *
	 *  Para prevenir ante desbordamientos de post_max_size, se introduce un
	 *  parámetro en la ruta del controlador: 'desatendido', que
	 *  anteriormente era pasado como variable POST.
	 */

	function nuevo($desatendido = '') {
		$this->load->helper('form');
		$this->load->library('form_validation');

		$data_form = array();
		$fid = null;

		$desatendido = !empty($desatendido);

		// Formulario que excede post_max_size usado desde JS
		if ($desatendido && !$this->input->post('enviar')) {
			$data_form['error'] = '<p>El fichero excede el tamaño '
				.'permitido</p>';
		}

		// Formulario enviado
		if ($this->input->post('enviar')) {
			$resultado = $this->_procesado_envio_fichero('nuevo',
					$data_form, $fid);
		}

		// Si no es desatendido, mostrar las vistas habituales
		if (!$desatendido) {
			$data = array(
					'subtitulo' => 'enviar nuevo fichero',
					'body_onload' => 'pagina_envio()',
					'no_mostrar_aviso' => TRUE,
					'js_adicionales' => array(
						'jquery.timers.js',
						'jquery.blockUI_2.20.js',
						'jquery.dimensions.pack.js',
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
			// Si es desatendido, sólo imprimir el identificador que 
			// ha asignado la bd

			if (isset($data_form['error'])) {
				echo $data_form['error'];
			} else {
				// Mensaje de éxito
				$this->session->set_flashdata('mensaje_fichero_cabecera',
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
        $fichero = $this->trabajoficheros->extrae_bd(array('fid' => $fid));

        if ($fichero === FALSE) {
			show_error('El fichero indicado no existe. Es posible que '
					.'existiera y haya caducado.', 404);
            return;
        }
		$this->load->library('manejoauxiliar');
		
		// Para decidir las acciones
		$data_cabecera = array(
				'no_mostrar_aviso' => 1
		);

		$permiso = $this->gestionpermisos->acceso_fichero($fichero);

		$pide_descarga = $descargar == 'descarga';

		// Casuística
		if ($permiso) {
			$data_fichero = array(
					'fichero' => $fichero,
			);

			if ($pide_descarga) {
				$decision_password =
					$this->trabajoficheros->comprueba_passwd($fichero,
							$this->input->post('passwd-fichero'));
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
			if ($this->gestionpermisos->permiso_modificacion($fichero)) {
				$data_fichero['permiso_modificacion'] = 1;
			}

			// ¿Infectado?
			if ($this->antivirus) {
				$info_av = $this->antivirus->get($fichero->fid);

				if ($info_av == FALSE) {
					log_message('error', 'No hay información de antivirus'
							.' del fichero ' . $fichero->fid);
				}

				$data_fichero['info_av'] = $info_av;
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
	function propios($atr_orden = 'fechaenvio', $orden = 'desc') {
		$this->load->library('pagination');
		$this->load->library('manejoauxiliar');

		if (!$this->autenticado) {
            show_error('Debe autenticarse para poder ver sus ficheros.', 403);
		} else {
			$opciones = array(
				'atr_orden' => $atr_orden,
				'orden' => $orden,
				'filtros' => array(
					'remitente' => $this->session->userdata('id'),
				),
				'busquedas' => array(),
				'seccion' => 'propios',
				'titulo' => 'Sus ficheros enviados',
				'mostrar_total_ocupado' => TRUE,
			);

			$this->_presentar_listado($opciones);
		}

	}

	/*
	 * Edición de un fichero
	 */

	function modificar($fid) {
        $fichero = $this->trabajoficheros->extrae_bd(array('fid' => $fid));

        if ($fichero === FALSE) {
            show_error('El fichero indicado no existe.', 404);
            return;
        }

		if (!$this->gestionpermisos->permiso_modificacion($fichero)) {
			show_error('No tiene permiso para modificar el fichero.', 403);
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
				redirect($fid);
			}

		}
	}


	/**
	 * Borrado de un fichero a petición de su remitente
	 */
	function borrar($fid) {
		$fichero = $this->trabajoficheros->extrae_bd(array('fid' => $fid));

        if ($fichero === FALSE) {
            show_error('El fichero indicado no existe.', 404);
            return;
		} elseif (!$this->gestionpermisos->permiso_modificacion($fichero)) {
			show_error('No tiene permiso para borrar el fichero.', 403);
			return;
		} else {
			// ¿Confirmación?
			if ($this->input->post('confirmacion')) {
                $this->trabajoficheros->elimina_fichero($fichero->fid, 
                        'Eliminación manual');
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

	/**
	 * Búsqueda de un fichero.
	 *
	 * Como se llamará desde un alias de ruta (/-xx) los segmentos de
	 * la URL no se corresponden con los de /fichero/buscar/xx, por tanto
	 * hay que pensar en las URLs del primero
	 */
	function buscar($cadena = '', $atr_orden = 'nombre', $orden = 'asc') {
		// Redirección a la página buena
		$cadena_post = $this->input->post('cadena', TRUE);
		if (empty($cadena) && $cadena_post !== FALSE) {
			$cadena_post = trim($cadena_post);
			$cadena_post = preg_replace('/([^\w\d\._-].*)$/', '', $cadena_post);
			redirect('-/' . $cadena_post);
		} elseif (empty($cadena)) {
			show_error('La búsqueda no puede quedar vacía. Recuerde que'
					.' la búsqueda se trunca tras el primer carácter'
					.' no alfanumérico.');
		} else {
			$this->load->library('pagination');
			$this->load->library('manejoauxiliar');

			$opciones = array(
					'atr_orden' => $atr_orden,
					'orden' => $orden,
					'filtros' => array(
						'listar' => 1
					),
					'busquedas' => array(
						'nombre' => $cadena,
						'descripcion' => $cadena),
					'seccion' => '/-/' . $cadena,
					'caja_busqueda' => 1,
					'titulo' => 'Resultado de la búsqueda ('.
						$cadena .')',
			);


			$this->_presentar_listado($opciones);
		}
	}


	/*
	 * Minipágina para los bocadillos en el listado de ficheros
	 */
	function minipagina($fid) {
		$fichero = $this->trabajoficheros->extrae_bd(array('fid' => $fid));

		if ($fichero === FALSE) {
			echo "Fichero inexistente o caducado";
		} else {
			$permitido = $this->gestionpermisos->acceso_fichero($fichero);


			if ($permitido === FALSE) {
				$img = site_url('img/interfaz/prohibido.png');
				$titulo = "Tiene prohibido el acceso a este fichero";
				$descripcion = "Autent&iacute;quese o acceda desde la red de la
					Universidad de Sevilla";
			} else {
				$mimetype = $this->trabajoficheros->consigue_mimetype($fichero->nombre);
				$titulo = $fichero->nombre;
				$img = site_url('img/tipos/32x32/' . $mimetype[1]);
				$descripcion = empty($fichero->descripcion) ? 'Sin descripción' :
					$fichero->descripcion;

				if ($fichero->listar == 0) {
					$titulo .= ' (oculto)';
				}

			}
			?>
				<div class="envoltura_minipagina">
				 <strong><?php echo $titulo ?></strong>
				 <img src="<?php echo $img ?>" style="float: right"
					 alt="Icono "/>

				 <div class="descripcion_fichero">
				  <?php echo $descripcion ?>
				 </div>
				</div>
			<?php

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
	 * "acceso universal", o si el usuario es anónimo.
	 *
	 * En ciertas circunstancias (usuario desde una IP privilegiada) el
	 * usuario puede enviar ficheros sin contraseña por error.
	 */
	function _passwd_necesario($p) {
		if (empty($p) && 
				($this->input->post('tipoacceso') == 1 
				 || !$this->autenticado)) {
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
					$this->trabajoficheros->extrae_bd(array(
								'fid' => $this->input->post('fid')));
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
							Póngase en contacto con '
							.$this->config->item('texto_contacto').'</p>';
					}

					return PROCESADO_ERR_ESCRITURA;
				}

				// Limpieza del nombre
				$data['nombre'] = $this->trabajoficheros->limpia_nombre($fichero['name']);
				$data['tam'] = $fichero['size'];

				// Remitente, IP y fechas
				$data['remitente'] = $this->autenticado ?
					$this->session->userdata('id') :
					'';
				$data['ip'] = $this->input->ip_address();

			} else {
				// Cargamos de la base de datos lo referente al fichero que
				// estemos editando
				$fid = $this->input->post('fid', TRUE);
				if ($fid === FALSE) {
					$data_form['error'] = '<p>Hay algún problema con el
						formulario de edición. Por favor, póngase en '
						.'contacto con ' 
						.  $this->config->item('texto_contacto')
						.'.</p>';
					return PROCESADO_ERR_FORMULARIO;
				}

				$actual =
					$this->trabajoficheros->extrae_bd(array(
								'fid' => $fid));
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
						de ficheros. Por favor, póngase en '
						.'contacto con '
						. $this->config->item('texto_contacto')
						.'.</p>';
					return PROCESADO_ERR_ESCRITURA;
				}

				return PROCESADO_OK;
			} // tipo = nuevo, copia de fichero
		} else {
			// Se rellenó mal el formulario
			return PROCESADO_ERR_FORMULARIO;
		}
	}

	/**
	 * Muestra un listado de ficheros, encargándose de toda la lógica que
	 * ello implica
	 *
	 * @param	array	array asociativo con las opciones necesarias para
	 * 					generar un listado.
	 *
	 */

	function _presentar_listado($opciones) {
		$seccion = $opciones['seccion'];

		// Si la sección comienza con '/' se entiende que es una ruta dentro
		// CI 'absoluta'. Si no, cuelga de '/ficheros'
		if (substr($seccion, 0, 1) != '/') {
			$seccion = 'ficheros/' . $seccion;
		}

		$atr_orden = $opciones['atr_orden'];
		$orden = $opciones['orden'];

		if (FALSE === $this->manejoauxiliar->controla_ordenacion($atr_orden, $orden)) {
			$atr_orden = 'fechaenvio';
			$orden = 'desc';
		}

		$data = array(
				'css_adicionales' => array(
					'jquery.cluetip.css',
				),
		);
		$this->load->view('cabecera', $data);
		$ficheros = $this->trabajoficheros->extrae_bd(
				$opciones['filtros'],
				$opciones['busquedas'],
				$atr_orden, $orden);

		/*
		 * Paginación
		 */
		$this->manejoauxiliar->paginacion_config($seccion
				.'/'.$atr_orden.'/'.$orden, count($ficheros));
		$columnas =
			$this->manejoauxiliar->columnas_con_ordenacion($seccion, 
					$atr_orden, $orden);

		$data = array(
				'ficheros' => $this->manejoauxiliar->paginacion_subconjunto($ficheros),
				'orden' => $columnas,
		);

		// Título
		if (isset($opciones['titulo'])) {
			$data['titulo'] = $opciones['titulo'];
		}

		// Búsqueda en la página principal
		if (isset($opciones['caja_busqueda'])){
			$data['caja_busqueda'] = 1;
		}
		// Espacio total
		if (isset($opciones['mostrar_total_ocupado'])) {
			$total = 0;
			foreach ($ficheros as $f) {
				$total += $f->tam;
			}
			$data['total_ocupado'] = $total;
		}
		$this->load->view('listado-ficheros', $data);
		$this->load->view('pie');
	}
}
?>
