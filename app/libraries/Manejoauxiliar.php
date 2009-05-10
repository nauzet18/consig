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
}

?>
