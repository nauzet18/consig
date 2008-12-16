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

class TrabajoFicheros extends Model {
	function TrabajoFicheros() {
		parent::Model();
	}

	/*
	 * Devuelve la cadena descriptiva de un intervalo de tiempo dado en
	 * segundos
	 */

	function intervalo_tiempo($segundos) {
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
			}
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
     * Extrae de la base de datos un fichero dado su identificador,
     * devolviendo FALSE si no encuentra ninguno.
     */

    function extrae_bd($fid) {
        $query = $this->db->get_where('ficheros', array('fid' => $fid));

        $res = $query->result();

        if (!$res || count($res) == 0) {
            return FALSE;
        } else {
            return $res[0];
        }

    }
}
?>
