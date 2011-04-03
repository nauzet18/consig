<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 *  Copyright 2011 Jorge López Pérez <jorgelp@us.es>
 *
 *  This file is part of Consigna.
 *
 *  Consigna is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or (at
 *  your option) any later version.
 *
 *  Consigna is distributed in the hope that it will be useful, but WITHOUT
 *  ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *  FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public
 *  License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Consigna.  If not, see <http://www.gnu.org/licenses/>.
 */


class Trabajomiscelanea extends Model {

	function Trabajomiscelanea() {
		parent::Model();
	}

	/**
	 * Recoger el valor de una variable en la tabla miscelánea (config)
	 *
	 * @param $nombre			Nombre de la variable
	 * @param $default		Valor por defecto (si no existe la entrada)
	 *
	 * @return				Valor de la variable
	 */

	public function leer($nombre, $default = '') {

		$q = $this->db->get_where('config', 
				array(
					'var' => $nombre,
					));

		$res = $q->result();
		if (count($res) == 0) {
			return $default;
		} else {
			return $res[0]->valor;
		}
	}

	/**
	 * Guarda una variable en la base de datos (sobreescribe)
	 *
	 * @param $nombre			Nombre
	 * @param $valor		Contenido
	 */

	function escribir($nombre, $valor) {
		$data = array(
				'var' => $nombre,
				'valor' => $valor,
				);

		$this->db->trans_start();
		$this->db->delete('config', $data);

		$this->db->insert('config', $data);

		$this->db->trans_complete();
	}
}
