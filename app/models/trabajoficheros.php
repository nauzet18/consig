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
		}

		if (count($patrones) > 0) {
				$sub2 =& $q->where();
				foreach ($patrones as $a2 => $p) {
					$sub2->or_like($a2, $p, 'both');
				}
		}

		// Orden
		$q->order_by($ordenar_por, $orden);

		// Mimetype
		$mimetype_defecto =
			$this->db->escape($this->config->item('mimetype_defecto'));
		$icono_defecto =
			$this->db->escape($this->config->item('mimetype_icono_defecto'));

		$q->join('mimetypes', 'ficheros.mid = mimetypes.mid', 'left');
		$q->select("ficheros.*, IF(ISNULL(mimetype), "
				. $mimetype_defecto .", mimetype) as mimetype, "
				." IF(ISNULL(icono), ".$icono_defecto.", icono) as icono",
				false);
        $query = $q->get('ficheros');

        $res = $query->result();

        if (isset($condiciones['fid']) && (!$res || count($res) == 0)) {
            return FALSE;
        }

		return isset($condiciones['fid']) ? $res[0] : $res;

    }


	/*
	 * "Aproxima" el mimetype de un fichero a partir de su extensión,
	 * devolviendo la fila concreta en la base de datos
	 */

	function consulta_mimetype($extension) {
		// Valores por defecto
		$mid = 0;
		$mimetype = $this->config->item('mimetype_defecto');
		$icono = $this->config->item('mimetype_icono_defecto');

		// De momento nos quedamos con la primera ocurrencia según
		// la extensión. Más adelante se debería comprobar el verdadero
		// mimetype
		$q = $this->db->get_where('mimetypes', 
				array('extension' => $extension),
				1);
		$res = $q->row();
		if ($res) {
			$mid = $res->mid;
			$mimetype = $res->mimetype;
			$icono = empty($res->icono) ? "mime.png" : $res->icono;
		}

		return (object) array(
				'mid' => $mid, 
				'mimetype' => $mimetype,
				'icono' => $icono);
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

		// Registramos la descarga
		$this->historico_add($fid);

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

		// Evitamos cargar en memoria el fichero desactivando
		// la salida con buffer
		while (ob_get_level() > 0) {
			ob_end_flush();
		}

		readfile($ruta);
	}


	/**
	 * Determina si un fichero pertenece a un determinado usuario.
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
	 * Añade al histórico la descarga de un fichero
	 *
	 * @param	int		Fichero descargado
	 *
	 */

	function historico_add($fid) {
		$autenticado = $this->session->userdata('autenticado');
		$usuario = ($autenticado ? $this->session->userdata('id') : '-');

		$ip = $this->input->ip_address();

		$datos = array(
				'fid' => $fid,
				'identidad' => $usuario,
				'ip' => $ip,
				'timestamp' => time()
			);
		$this->db->insert('historicodescargas', $datos);

		$this->logdetalles('download', '', $fid);
	}

	/**
	 * Cuenta el número de descargas de un fichero
	 *
	 * @param	int		Identificador del fichero
	 *
	 * @return	int		Número de veces que se ha descargado el fichero
	 */
	function historico_num($fid = 0) {
		$this->db->where(array('fid' => $fid));
		$this->db->from('historicodescargas');

		$res = $this->db->count_all_results();

		return $res;
	}

	/**
	 * Devuelve las descargas para un fichero dado
	 *
	 * @param	int		Identificador del fichero
	 *
	 * @return	array		Array de objetos con los
	 *                      siguientes atributos:
	 *                        - fid
	 *                        - identidad
	 *                        - ip
	 *                        - timestamp
	 */
	function historico_detallado($fid = 0) {
		$this->db->where(array('fid' => $fid));
		$this->db->from('historicodescargas');
		$this->db->order_by('timestamp', 'desc');

		$res = $this->db->get();

		return $res->result();
	}
}
?>
