<?php
/*
 * Copyright 2011 Jorge López Pérez <jorgelp@us.es>
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

class Actualizacionesbd extends Model {
	function Actualizacionesbd() {
		parent::Model();
	}

	/**
	 * Lee las actualizaciones que hay que aplicar para una determinada
	 * versión de la base de datos.
	 *
	 * @param $version		Número de versión de la base de datos
	 * @return				Nombre de la clase que aplicará las
	 *						actualizaciones, si todo fue correcto,
	 *						FALSE si no se encontró
	 */
	function leer($version) {
		$clase = 'Bd_' . $version;
		$ruta = APPPATH . 'libraries/upgrades/' . $clase . '.php';
		
		if (!file_exists($ruta)) {
			log_message('error', 'Intento de recoger actualizaciones '
					.'del esquema para versión inexistente ('.$version.')');
			return FALSE;
		}

		require_once($ruta);
		return $clase;
	}

	/**
	 * Ejecuta las actualizaciones pasadas de una versión dada del esquema.
	 * Éstas vienen definidas como un array, que tendrá la siguiente forma:
	 *
	 * (operación, parámetros)
	 *
	 * Donde operación puede ser:
	 * 
	 * - sql : ejecuta una sentencia SQL
	 * - nombre de función: ejecuta el método 'nombre de función' de la
	 *                      clase que contiene las actualizaciones.
	 *
	 * @param	$version			Versión del esquema
	 * @param	$error				Error (si lo hubo)
	 * @return	boolean				TRUE si todo fue bien, FALSE si no
	 */

	function ejecutar($version, &$error) {
		// Sólo números
		$version = preg_replace('[^0-9]', '', $version);
		$conjuntoactualizaciones = $this->leer($version);
		if (FALSE === $conjuntoactualizaciones) {
			$error = 'No existe la actualización de esquema v' . $version;
			return FALSE;
		}

		$ops = $conjuntoactualizaciones::$pasos;

		if (!is_array($ops)) {
			log_message('error', 'Actualizaciones ' 
					.  $conjuntoactualizaciones
					.' incorrectas, revise la sintaxis.');
			$error = 'La actualización '. $conjuntoactualizaciones . ' está mal definida';
			return FALSE;
		}

		$mensaje_log = 'Actualización a ' . $conjuntoactualizaciones . '. Ejecutando ';
		foreach ($ops as $op) {
			if ($op[0] == 'sql') {
				// Ejecutar código SQL
				log_message('info', $mensaje_log . 'SQL: ' .  $op[1]);
				$res = $this->db->simple_query($op[1]);
				if (FALSE === $res) {
					$error = 'Falló la sentencia SQL ' . $op[1];
					log_message('error', $error);
					return FALSE;
				}
			} else {
				// Ejecutar función
				log_message('info', $mensaje_log . 'método: ' .  $op[0]);

				$res = call_user_func(array($conjuntoactualizaciones,
							$op[0]));
				if (FALSE === $res) {
					$error = 'Falló la llamada a ' .$op[0];
					log_message('error', $error);
					return FALSE;
				} elseif (!is_array($res)) {
					$error = 'El método ' .$op[0] .' no devuelve el'
						.' array de resultados que debería';
					log_message('error', $error);
					return FALSE;
				} else if ($res[0] == FALSE) {
					// Hubo algún problema
					$error = 'El método ' .$op[0] .' falló: '
						. $res[1];
					log_message('error', $error);
					return FALSE;
				}
			}
		}

		// Todo fue bien
		log_message('info', 'Actualización con éxito a ' . $conjuntoactualizaciones);
		return TRUE;
	}
}
