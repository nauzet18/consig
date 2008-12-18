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

class TrabajoFicheros extends Model {
	function TrabajoFicheros() {
		parent::Model();
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

		foreach ($unidades as $u => $v) {
			if ($bytes >= $v) {
				return round($bytes/$v, 2) . $u;
			}
		}
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
		if (isset($datos['fid'])) {
			$fid = $datos['fid'];
			unset($datos['fid']);
			$this->db->where('fid', $fid);
			$this->db->update('ficheros', $datos); 

		} else {
			$this->db->insert('ficheros', $datos);
			$fid = $this->db->insert_id();
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
     * Extrae de la base de datos un fichero dado su identificador, o todos
	 * los ficheros si no se especifica ninguno (o se pasa 0 como
	 * primer parámetro).
	 *
	 * Si
     * 
	 * En el caso de especificar un identificador, se devuelve FALSE si no
	 * se encuentra.
	 *
	 * En otro caso, se devolverá un array vacío
     */

    function extrae_bd($fid = 0, $ignorar_listar = 0) {
		if ($fid != 0) {
			$this->db->where('fid', $fid);
		} elseif ($ignorar_listar == 0) {
			// Si fid != 0, ignoraremos esta bandera
			$this->db->where('listar', '1');
		}

		// TODO: configurable
		$this->db->order_by("fechaenvio", "desc");
        $query = $this->db->get('ficheros');

        $res = $query->result();

        if ($fid == 0 && (!$res || count($res) == 0)) {
            return FALSE;
        } else if ($fid != 0) {
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
		// TODO: configurable
		$mimetype = 'application/octet-stream';
		$icono = 'mime.png';
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
		$timezone = $this->config->item('zona_horaria');
		$daylight_saving = TRUE;

		// TODO XXX ¿no funciona en GMT?
		//$nuevo_t = gmt_to_local($timestamp, $timezone, $daylight_saving);
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
			// Hack para cargar un modelo desde otro modelo
			$ci =& get_instance();
			$ci->load->model('trabajoldap');
			$datos = $ci->trabajoldap->consulta($usuario);
			$cadena = ($datos === FALSE) 
				? 'Desconocido' : $datos['nombre'];
		}

		return '<span class="usuario">' . $cadena .  '</span>';
	}
}
?>
