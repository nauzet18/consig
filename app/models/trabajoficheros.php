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

class TrabajoFicheros extends Model {
	function TrabajoFicheros() {
		parent::Model();
		$this->load->helper('date');
	}

	/*
	 * Devuelve la cadena descriptiva de un intervalo de tiempo dado en
	 * segundos
	 */

	function intervalo_tiempo($segundos, $granularidad = 3) {
		$unidades = array(
				'sem' => 604800,
				'd' => 86400,
				'h' => 3600,
				'm' => 60,
				's' => 1,
				);

		$cadena = '';
		foreach ($unidades as $n => $v) {
			if ($segundos >= $v) {
				$cadena .= ($cadena ? ' ' : '') . floor($segundos / $v) . $n;
				$segundos %= $v;
				$granularidad--;
			}

            if ($granularidad == 0)
                break;
		}

		return ($cadena ? $cadena : '0s');
	}

    /*
     * Devuelve la velocidad de un envío
     */

	function velocidad_envio($bps) {
		$unidades = array(
				'MB' => 1048576,
				'kB' => 1024,
				'B' => 1,
				);

		$cadena = '';
		foreach ($unidades as $n => $v) {
			if ($bps >= $v) {
				$cadena .= ($cadena ? ' ' : '') . round($bps / $v, 1) . 
                       $n .  '/s';
                break;
			}
		}

		return ($cadena ? $cadena : '¿? B/s');
	}

	/*
	 * Devuelve el tamaño de un fichero en formato "humano",
	 * con una precisión moderada, a partir de su tamaño en bytes
	 */

	function tam_fichero($bytes) {
		$unidades = array(
				'GB' => 1073741824,
				'MB' => 1048576,
				'kB' => 1024,
				'B' => 1
		);

		$res = '0 B';

		foreach ($unidades as $u => $v) {
			if ($bytes >= $v) {
				$res = round($bytes/$v, 2) . ' ' . $u;
				break;
			}
		}

		return $res;
	}

	/*
	 * Devuelve el nombre de un fichero libre de caracteres erróneos y/o
	 * fragmentos malintencionados
	 */

	function limpia_nombre($nombre) {
		$nuevo_nombre = basename($nombre);
		$nuevo_nombre = strip_tags($nuevo_nombre);

		return $nuevo_nombre;
	}

	/*
	 * Devuelve la descripción de un fichero limpia de etiquetas y
	 * fragmentos maliciosos
	 */

	function limpia_descripcion($desc) {
		return ($desc === FALSE ? "" : strip_tags($desc));
	}

	/*
	 * Almacena un fichero en base de datos, actualizándolo en caso de
	 * recibir un id existente
	 */

	function almacena_bd($datos) {
		// Contraseña
		if (isset($datos['password']) && !empty($datos['password'])) {
			$datos['password'] = sha1($datos['password']);
		}

		if (isset($datos['fid'])) {
			$fid = $datos['fid'];
			unset($datos['fid']);
			$this->db->where('fid', $fid);
			$this->db->update('ficheros', $datos); 
			$this->logdetalles('update', '', $fid);

		} else {
			$this->db->insert('ficheros', $datos);
			$fid = $this->db->insert_id();
			$this->logdetalles('upload',
					$datos['tam'], $fid);
		}

		return $fid;
	}

	/*
	 * Elimina de la base de datos un fichero dado por su identificador
	 */

	function elimina_bd($fid) {
		$this->db->delete('ficheros', array('fid' => $fid));
	}

	/*
	 * Elimina un fichero del sistema de ficheros pero NO de la base de
	 * datos
	 */
	function elimina_fs($fid) {
		// Nos curamos en salud
		$fid = basename($fid);
		$ruta = $this->config->item('directorio_ficheros') . '/' . $fid;

		if (!file_exists($ruta)) {
			return FALSE;
		} else {
			return unlink($ruta);
		}
	}

	/**
	 * Elimina totalmente un fichero
	 */
	function elimina_fichero($fid, $motivo = 'no especificado') {
		$this->logdetalles('delete',
				$motivo, $fid);
		$this->elimina_bd($fid);
		if (FALSE === $this->elimina_fs($fid)) {
			$this->logdetalles('error', 
					'Error eliminando fichero ' . $fid . '. No sigue en '
					.'BD, comprueba los permisos. Motivo original del '
					.'borrado: ' . $motivo);

			return FALSE;
		} else {
			return TRUE;
		}
	}

    /*
     * Extrae de la base de datos el fichero o los ficheros que indiquen las
	 * condiciones del primer parámetro, aplicando si fuera necesario los
	 * patrones definidos en el segundo
	 *
	 *  @param array 	array asociativo con condiciones
	 *  @param array	array asociativo con patrones
	 *	@param string	atributo por el que se ordenan los resultados. si no
	 *					se especifica ninguno, se usa 'fechaenvio'
	 *
	 *  @return		el elemento o elementos extraídos, FALSE si no se
	 * 				encontró
     */

	function extrae_bd($condiciones = array(), $patrones = array(),
			$ordenar_por = 'fechaenvio', $orden = 'desc') {

		// Ignitedquery da más potencia en la búsqueda
		$this->load->library('ignitedquery');

		$q = new IgnitedQuery();
		foreach ($condiciones as $a => $v) {
			$sub =& $q->where();
			$sub->where($a, $v);

			if (count($patrones) > 0) {
				$sub2 =& $sub->where();
				foreach ($patrones as $a2 => $p) {
					$sub2->or_like($a2, $p, 'both');
				}
			}
		}

		// Orden
		$q->order_by($ordenar_por, $orden);

        $query = $q->get('ficheros');

        $res = $query->result();

        if (isset($condiciones['fid']) && (!$res || count($res) == 0)) {
            return FALSE;
        } else if (isset($condiciones['fid'])) {
			return $res[0];
		} else {
            return $res;
        }

    }

	/*
	 * "Aproxima" el mimetype de un fichero a partir de su extensión,
	 * devolviendo un array con dos elementos: el mimetype y su icono, si lo
	 * hubiera
	 */

	function consigue_mimetype($nombre) {
		$mimetype = $this->config->item('mimetype_defecto');
		$icono = $this->config->item('mimetype_icono_defecto');
		if (strpos($nombre, ".") !== FALSE) {
			$partes = split("\.", $nombre);
			$extension = $partes[count($partes) - 1];
			$q = $this->db->get_where('mimetypes', 
					array('extension' => $extension),
					1);
			$res = $q->row();
			if ($res) {
				$mimetype = $res->mimetype;
				$icono = empty($res->icono) ? "mime.png" : $res->icono;
			}
		}

		return array($mimetype, $icono);
	}

	/*
	 * Devuelve una fecha en formato legible, según la zona
	 * horaria configurada, a partir de un timestamp GMT
	 */

	function fecha_legible($timestamp) {
		/*
		 * TODO Valorar en el futuro la posibilidad de usar fechas GMT
		 *      conviriténdolas a locales
		 *
		 * $timezone = $this->config->item('zona_horaria');
		 * $daylight_saving = TRUE;
		 *
		 * $nuevo_t = gmt_to_local($timestamp, $timezone, $daylight_saving);
		 */

		$nuevo_t = $timestamp;
		return strftime("%d de %B de %Y, %H:%Mh", $nuevo_t);
	}

	/*
	 * Devuelve un usuario formateado convenientemente en HTML
	 */
	function usuario_html($usuario, $mostrar_autor = 1) {

		$cadena = '';

		if (empty($usuario)) {
			$cadena = 'Anónimo';
		} elseif (!$mostrar_autor) {
			$cadena = 'Anónimo *';
		} else {
			$ci =& get_instance();
			$datos = $ci->auth->get_user_data($usuario);
			$cadena = ($datos === FALSE) 
				? 'Desconocido' : $datos['name'];
		}

		return '<span class="usuario">' . $cadena .  '</span>';
	}

	/**
	 * Indica si un usuario tiene acceso a un determinado fichero, dado como
	 * su objeto proveniente de la base de datos.
	 *
	 * Acceso permitido en los siguientes casos:
	 *
	 *  1. Usuario autenticado
	 *  2. Fichero con acceso universal
	 *  3. Usuario conectado desde una IP de las contenidas en el fichero de
	 *     subredes
	 *  4. Fichero subido desde una IP interna
	 *  5. Fichero subido por un usuario autenticado
	 *
	 *  @param	objeto fichero sobre el que se quiere hacer la comprobación
	 *	@return si el usuario actual tiene acceso al fichero
	 */

	function acceso_fichero($fichero) {
		if ($this->session->userdata('autenticado') 
				|| $fichero->tipoacceso == 1) {
			return TRUE;
		} else {
			/*
			 * Buscamos IP del usuario que accede,
			 * y si no hay éxito comprobamos la del remitente.
			 *
			 * Realmente tipoacceso debe ser 1 para IPs internas (se hace
			 * así al enviar un fichero), pero puede que hayamos modificado
			 * el fichero de subredes después de que el usuario enviara el
			 * fichero.
			 */

			$ip_remitente = $fichero->ip;
			$ip_usuario = $this->input->ip_address();

			$ips = array($ip_remitente, $ip_usuario);

			return $this->trabajoficheros->busqueda_ips($ips);
		}
	}

	/**
	 * Búsqueda de una o más IPs en la lista de IPs internas
	 */

	function busqueda_ips($arrips) {
		$subredes = $this->config->item('subredes');

		foreach ($subredes as $subred) {
			foreach ($arrips as $ip) {
				if (preg_match($subred, $ip)) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}


	/**
	 * Predice el futuro acceso al fichero
	 *
	 * @param	el tipo de acceso al fichero (0 = privado, 1 = público). Por
	 * defecto es privado
	 * @return	0 si la IP no es interna y no está autenticado, o está
	 * 		  	  autenticado pero el tipo de acceso es 0
	 * 			1 si la IP es interna, o está autenticado y el tipo de
	 * 			  acceso es 1
	 */
	function futuro_acceso($tipoacceso = 0) {
		$ip_interna =
			$this->trabajoficheros->busqueda_ips(array(
						$this->input->ip_address()
					));
		$autenticado = $this->session->userdata('autenticado');

		if ((!$autenticado && !$ip_interna) ||
				($autenticado && $tipoacceso == 0)) {
			return 0;
		} else {
			return 1;
		}
	}

	/**
	 * Toma una decisión respecto a una contraseña. Existe la siguiente
	 * casuística:
	 *
	 *  1. Fichero sin contraseña: TRUE
	 *  2. Fichero con contraseña: comprobación de la contraseña
	 */
	function comprueba_passwd($fichero, $cadena) {
		if (empty($fichero->password)) {
			return TRUE;
		} else {
			return ($fichero->password == sha1($cadena));
		}
	}

	/**
	 * Fuerza la descarga de un fichero por parte del usuario
	 */

	function fuerza_descarga($fichero) {
		$fid = $fichero->fid;
		$ruta = $this->config->item('directorio_ficheros') . '/' . $fid;

		// Incoherencia bd <-> sistema de ficheros
		if (!file_exists($ruta)) {
			$this->logdetalles('error', 
				'Inconsistencia BD-FS. En FS no existe', $fid);
			show_error('Existe un problema con la base de datos. Por favor, '
				.'pruebe más tarde', 500);
			die();
		}

		/*
		 * Cabeceras y demás
		 */

		if (ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);

		// TODO: ignorar mimetype o no? IE con problemas?
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=\""
			. $fichero->nombre ."\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ". $fichero->tam);
		readfile($ruta);
	}

	/**
	 * Determina si el usuario actual tiene permiso para modificar los datos
	 * de un fichero.
	 *
	 */
	function permiso_modificacion($fichero) {
		$autenticado = $this->session->userdata('autenticado');
		if (!$autenticado) {
			return FALSE;
		} else {
			$usuario= $this->session->userdata('id');
			// XXX ¿administradores?
			return $usuario == $fichero->remitente;
		}
	}

	/**
	 * Determina si un fichero posee a un determinado usuario.
	 *
	 * @param fichero
	 * @param string usuario, opcional. Si no se indica, se supone que se
	 * trabaja contra el usuario actual
	 */
	function es_propietario($fichero, $usuario = '') {
		if (empty($usuario) && !$this->session->userdata('autenticado')) {
			return FALSE; // No hay usuario actual
		} elseif (empty($usuario)) {
			$usuario = $this->session->userdata('id');
		}

		return $fichero->remitente == $usuario;
	}

	/**
	 * Devuelve una lista con los ficheros que han expirado
	 *
	 * @return  lista de ficheros expirados
	 */

	function expirados() {
		$ahora = time();

		$this->db->where('fechaexp <=' , $ahora);
		$query = $this->db->get('ficheros');

        $res = $query->result();

		return $res;
	}

	/**
	 * Registra un mensaje en el log con toda la información posible del
	 * usuario actual.
	 *
	 * Si se refiere a un fichero, añade información sobre éste.
	 *
	 * @param	nivel de log del mensaje (info, debug, etc)
	 * @param	mensaje que se desea añadir
	 * @param	fichero del que se hace el registro
	 */
	function logdetalles($nivel, $msj, $fichero = 0) {
		$autenticado = $this->session->userdata('autenticado');
		$usuario = ($autenticado ? $this->session->userdata('id') : '-');

		$ip = $this->input->ip_address();

		// Usuario
		$msj = $usuario . '@' . $ip . ' ' . $msj;

		// Fichero
		if ($fichero != 0) {
			$f = $this->trabajoficheros->extrae_bd(array('fid' => $fichero));
			if ($f === FALSE) {
				// El fichero no existe!
				logdetalles('error', 
						'Llamada a log() con fichero inexistente ('
							.$fichero.')');
				$msj = '- ' . $msj;
			} else {
				// Prefijamos el mensaje con los datos del fichero
				$msj = $f->fid . ':/' . $f->nombre . '/ ' . $msj;
			}
		} else {
			$msj = '- ' . $msj;
		}

		// Guardamos log
		log_message($nivel, $msj);
	}

	/**
	 * Comprueba si un usuario es privilegiado
	 *
	 * @param	identificador del usuario, opcional. Si no, se busca en la
	 *          sesión actual
	 * @return	TRUE si lo es, FALSE en otro caso
	 */

	function es_privilegiado($id = FALSE) {

		// Paso sin parámetros
		if ($id === FALSE) {
			if ($this->session->userdata('autenticado')) {
				$id = $this->session->userdata('id');
			} else {
				$id = '';
			}
		}

		// Usuario anónimo
		if (empty($id)) {
			return FALSE;
		}

		$privilegiados = $this->config->item('privilegiados');
		for($i=0;$i<count($privilegiados);$i++) {
			if ($privilegiados[$i] == $id) {
				return TRUE;
			}
		}

		return FALSE;
	}

}
?>
