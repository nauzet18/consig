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

class Manejoauxiliar {

	var $CI;

	function Manejoauxiliar() {
		$this->CI =& get_instance();
	}

	/**
	 * Configura la clase de paginación de CodeIgniter con unos parámetros
	 * comunes
	 *
	 * @param	string	Ruta en la que se está trabajando
	 * @param	int		Cantidad total de entradas que se desea paginar
	 */

	function paginacion_config($ruta, $total) {
		$config = array(
			'base_url' => site_url($ruta),
			'total_rows' => $total,
			'per_page' => $this->CI->config->item('resultados_por_pagina'),
			'full_tag_open' => '<div id="paginacion">',
			'full_tag_close' => '</div>',
			'first_link' => 'Primera',
			'last_link' => '&Uacute;ltima',
			'cur_tag_open' => '<span id="paginacion_actual">',
			'cur_tag_close' => '</span>',
			'uri_segment' => 5,
		); 

		$this->CI->pagination->initialize($config);
	}

	/**
	 * Devuelve la porción de array de ficheros indicada por la paginación
	 *
	 * @param	array	Conjunto de ficheros que se desea paginar
	 */

	function paginacion_subconjunto($ficheros) {
		$rpp = $this->CI->config->item('resultados_por_pagina');
		$pagina_actual = $this->CI->uri->segment(5, 0);

		// ¿Es númerico?
		if (!is_numeric($pagina_actual)) {
			$pagina_actual = 0;
		}

		return array_slice($ficheros, $pagina_actual, $rpp);
	}

	/**
	 * Controla que los parámetros de ordenación sean legales
	 *
	 * @param	string	Atributo por el que ordenar
	 * @param	string	Orden (asc, desc)
	 * @return	FALSE si no son legales
	 */

	function controla_ordenacion($atr_orden, $orden) {
		if (($atr_orden != 'fechaenvio' && $atr_orden != 'tam'
				&& $atr_orden != 'nombre') 
				|| ($orden != 'asc' && $orden != 'desc')) {
			// Indicamos que se debe volver a 'fechaenvio', 'desc'
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Genera un array para la cabecera de los listados de ficheros, que
	 * será usado por la vista 'listado-ficheros'. 
	 *
	 * Cada columna contendrá un enlace para ordenar por esa misma columna y
	 * con diferentes criterios, además de mostrarse un indicador de
	 * ordenación (un triángulo hacia arriba o hacia abajo) al lado del
	 * atributo actual por el que se está ordenando.
	 *
	 * Por defecto el atributo de ordenación es la fecha de envío
	 * 
	 * @param	string	Sección actual (index, propios...)
	 * @param	string	Atributo de ordenación
	 * @param	string	Orden de ordenación
	 * @return	array
	 */

	function columnas_con_ordenacion($seccion, $atr_orden, $orden) {
		$columnas = array(
				'nombre' => 'Nombre del fichero', 
				'tam' => 'Tama&ntilde;o',
				'fechaenvio' => 'Fecha de env&iacute;o'
		);
		$resultado = array();

		foreach ($columnas as $k => $t) {
			if ($atr_orden == $k) {
				$orden_contrario = ($orden == 'asc' ? 'desc' : 'asc');
				$resultado[$k] = anchor(site_url($seccion . '/' . $atr_orden
							.'/' . $orden_contrario), $t);
				$resultado[$k] .= ' <img src="'
					.site_url('img/interfaz/flecha_'.$orden_contrario.'.png')
					.'" alt="Ordenar " />';
			} else {
				$resultado[$k] = anchor(site_url($seccion . '/' . $k
							.'/asc'), $t);
			}

		}

		return $resultado;
	}

	/**
	 * Acorta una cadena (nombre de fichero, usuario, etc) si fuera
	 * necesario. 
	 * Ej: "fichero con nombre muy largo.pdf" -> "fichero con....pdf"
	 *
	 * Delante de los puntos suspensivos introduce $longitud - 7 (4 del
	 * final + 3 de los puntos suspensivos) caracteres.
	 */

	function abrevia($cadena, $longitud = 50) {
		$resultado = $cadena;

		if (strlen($cadena) > $longitud) {
			$resultado = substr($cadena, 0, $longitud - 7) . '...' 
				. substr($cadena, -4); 
		}

		return $resultado;
	}


	/**
	 * Devuelve un usuario formateado convenientemente en HTML.
	 * Los administradores verán más información (IP, etc)
	 *
	 * @param object	Fichero cargado de la base de datos
	 */
	function remitente_de($fichero) {

		$cadena = '';
		$usuario = $fichero->remitente;
		$ip = $fichero->ip;
		$privilegiado = $this->CI->gestionpermisos->es_privilegiado();

		// Carga de datos
		$datos = FALSE;
		if (!empty($usuario) && ($privilegiado === TRUE ||
					$fichero->mostrar_autor)) {
			$datos = $this->CI->auth->get_user_data($usuario);
		}

		if (empty($usuario)) {
			$cadena = 'Anónimo';
		} else {
			if (!$fichero->mostrar_autor) {
				$cadena = 'Anónimo *';
			} else {
				$cadena = ($datos === FALSE) 
						? 'Desconocido' : $datos['name'];
			}
		}

		// Caso privilegiado
		if ($privilegiado) {
			$cadena .= ' (IP: ' . $fichero->ip;
			if ($datos !== FALSE) {
				if (!$fichero->mostrar_autor) {
					$cadena .= ', ' . $datos['name'];
				}

				$cadena .= ', <a href="mailto:'.$datos['mail']
					.'">'.$datos['mail'].'</a>';
			}
			$cadena .= ')';
		}

		return '<span class="usuario">' . $cadena .  '</span>';
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


}

?>
