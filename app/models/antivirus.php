<?php
/*
 * Copyright 2010 Jorge López Pérez <jorgelp@us.es>
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

class Antivirus extends Model {
	function Antivirus() {
		parent::Model();
	}

	/*
	 * Estados de un fichero
	 */

	const PENDING = "PENDIENTE";
	const INFECTED = "INFECTADO";
	const CLEAN = "LIMPIO";
	const ERROR = "ERROR";


	/**
	 * Añade en la base de datos un fichero con estado PENDIENTE, o
	 * actualiza su estado en caso de existir previamente.
	 * Posteriormente lo añade en la cola de revisión.
	 *
	 * @param int	fichero a encolar
	 * @param int	prioridad en la cola (0 = máxima, 2^32 mínima)
	 * @return		FALSE si el proceso falló
	 */

	// TODO: considerar TTR mayor si los ficheros van a tener mucho tamaño
	function enqueue($fid, $prioridad = 10) {

		// Carga biblioteca
		$this->_inicializa_pheanstalk();

		// Guardar en BBDD
		$result = $this->store($fid, self::PENDING, '');

		if ($result === FALSE) {
			return FALSE;
		}

		// Encolar en beanstalkd
		$host = $this->config->item('beanstalkd_host');
		$port = $this->config->item('beanstalkd_port');
		$tube = $this->config->item('beanstalkd_tube');

		try {

			$pheanstalk = new Pheanstalk($host, $port);
			$pheanstalk
				->useTube($tube)
				->put("SCAN " . $fid, $prioridad);
		} catch (Exception $e) {
			// TODO : más detalle
			log_message('error', 'Error encolando trabajo en beanstalkd');
			$this->store($fid, self::ERROR, 
					'El fichero no se pudo encolar para su revisión');
			return FALSE;
		}

		log_message('info', 'Fichero ' . $fid . ' encolado para ser escaneado');
	}

	/**
	 * Almacena en la base de datos el estado de un fichero. Si no existía
	 * un estado previo, se crea.
	 */

	function store($fid, $estado = '',
				$extra = '') {

		if (empty($fid) || !is_numeric($fid)) {
			log_message('error', 'Llamada a antivirus->store() con fid '
					.'inválido ['.var_export($fid, TRUE).']');

			return FALSE;
		} else if (empty($estado) || !$this->_check_state($estado)) {
			log_message('error', 'Llamada a antivirus->store() con estado '
					.'inválido ['.var_export($estado, TRUE).']');

			return FALSE;
		}

		$data = array(
				'fid' => $fid,
				'estado' => $estado,
				'extra' => $extra,
				'timestamp' => time(),
				);

		$this->db->query("LOCK TABLES antivirus WRITE");
		$this->db->delete("antivirus", array('fid' => $fid));
		$this->db->insert('antivirus', $data);
		$this->db->query("UNLOCK TABLES");

		return TRUE;
	}

	/**
	 * Extrae de la base de datos la información de virus de un fichero
	 */

	function get($fid) {
		if (!is_numeric($fid)) {
			log_message('error', 'Llamada a antivirus->get() con '
					.'fid inválido: ['. var_export($fid, TRUE) .']');
			return FALSE;
		}

		$query = $this->db->get_where('antivirus', array('fid' => $fid));

        $res = $query->result();

		return count($res) > 0 ? $res[0] : FALSE;

	}

	/**
	 * Extrae todos los ficheros con el estado dado y última fecha de
	 * escaneo anterior a la indicada
	 */

	function get_where($estado, $timestamp = 0) {
		$cond = array(
				'estado' => $estado,
				);

		if ($timestamp != 0) {
			$cond['timestamp <='] = $timestamp;
		}

		$query = $this->db->get_where('antivirus', $cond);

        $res = $query->result();

		return $res;
	}

	/**
	 * Elimina de la base de datos del antivirus un fichero dado
	 */
	function delete($fid) {
		$this->db->delete('antivirus', array('fid' => $fid));
	}

	/**
	 * Comprueba si un estado dado es válido
	 */

	function _check_state($estado) {
		if ($estado != self::PENDING && $estado != self::INFECTED &&
				$estado != self::CLEAN && $estado != self::ERROR) {
			return FALSE;
		} else {
			return TRUE;
		}
	}


	/**
	 * Carga bibliotecas de pheanstalk
	 */
	function _inicializa_pheanstalk() {
		$pheanstalkClassRoot = getcwd() . '/' . APPPATH . 'libraries/pheanstalk/classes';
		require_once($pheanstalkClassRoot . '/Pheanstalk/ClassLoader.php');

		Pheanstalk_ClassLoader::register($pheanstalkClassRoot);
	}

}

?>
