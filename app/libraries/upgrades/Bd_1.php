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

if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Bd_1 {
	public static $pasos = array(
		array('sql', "CREATE INDEX idx_mimetype_extension ON mimetypes(extension);"),
		array('sql', "CREATE INDEX idx_session_last_activity     ON ci_sessions(session_last_activity);"),
		array('sql', "ALTER DATABASE consigna CHARACTER SET utf8 COLLATE utf8_general_ci;"),
		array('sql', "ALTER TABLE antivirus CHARSET=utf8;"),
		array('sql', "ALTER TABLE ci_sessions CHARSET=utf8;"),
		array('sql', "ALTER TABLE ficheros CHARSET=utf8;"),
		array('sql', "ALTER TABLE historicodescargas CHARSET=utf8;"),
		array('sql', "ALTER TABLE mimetypes CHARSET=utf8;"),
		array('sql', "ALTER TABLE usercache CHARSET=utf8;"),
		array('sql', "ALTER TABLE ficheros ADD COLUMN `mid` INT UNSIGNED NOT NULL DEFAULT 0 AFTER nombre;"),

		array('sql', "#Tabla: ficheros"),
		array('sql', "ALTER TABLE ficheros MODIFY     `nombre` VARCHAR(512) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE ficheros MODIFY     `remitente` VARCHAR(200) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE ficheros MODIFY     `ip` VARCHAR(20) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE ficheros MODIFY     `password` VARCHAR(50) CHARACTER SET utf8 ;"),
		array('sql', "ALTER TABLE ficheros MODIFY     `descripcion` TEXT CHARACTER SET utf8 ;"),
		array('sql', "#Tabla: mimetypes"),
		array('sql', "ALTER TABLE mimetypes MODIFY    `mimetype` VARCHAR(100) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE mimetypes MODIFY    `extension` VARCHAR(255) CHARACTER SET utf8 ;"),
		array('sql', "ALTER TABLE mimetypes MODIFY    `icono` VARCHAR(100) CHARACTER SET utf8 ;"),
		array('sql', "#Tabla: usercache"),
		array('sql', "ALTER TABLE usercache MODIFY    `id` VARCHAR(200) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE usercache MODIFY    `name` VARCHAR(255) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE usercache MODIFY    `mail` VARCHAR(255) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "#Tabla: ci_sessions"),
		array('sql', "ALTER TABLE ci_sessions MODIFY session_id varchar(40) CHARACTER SET utf8 DEFAULT '0' NOT NULL;"),
		array('sql', "ALTER TABLE ci_sessions MODIFY session_ip_address varchar(16) CHARACTER SET utf8 DEFAULT '0' NOT NULL;"),
		array('sql', "ALTER TABLE ci_sessions MODIFY session_user_agent varchar(50) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE ci_sessions MODIFY session_data text CHARACTER SET utf8 default '' NOT NULL;"),
		array('sql', "#Tabla: antivirus"),
		array('sql', "ALTER TABLE antivirus MODIFY    `estado` VARCHAR(100) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE antivirus MODIFY    `extra` VARCHAR(255) CHARACTER SET utf8 ;"),
		array('sql', "#Tabla: historicodescargas"),
		array('sql', "ALTER TABLE historicodescargas MODIFY   `identidad` VARCHAR(100) CHARACTER SET utf8 NOT NULL;"),
		array('sql', "ALTER TABLE historicodescargas MODIFY   `ip` VARCHAR(255) CHARACTER SET utf8 ;"),
		array('sql', "ALTER TABLE config MODIFY `var` VARCHAR(40) CHARACTER SET utf8 ;"),
		array('sql', "ALTER TABLE config MODIFY `valor` VARCHAR(255) CHARACTER SET utf8 ;"),

		array('actualiza_extensiones'),
		);

	static function actualiza_extensiones() {
		$CI =& get_instance();

		// Extraemos las extensiones posibles
		$q = $CI->db->query('SELECT extension, min(mid) as mid FROM'
				.' mimetypes GROUP BY extension');

		$res = $q->result();

		$primercampo = function($obj) { return $obj->extension; };
		$segundocampo = function($obj) { return $obj->mid; };

		$extensiones = array_combine(
				array_map($primercampo, $res),
				array_map($segundocampo, $res));

		// Para cada fichero hallamos su extensión correspondiente
		$asociacion_extensiones = array();
		$q2 = $CI->db->query('SELECT fid,nombre FROM ficheros');
		foreach ($q2->result() as $f) {
			// Por defecto, tipo 0
			$asociacion_extensiones[$f->fid] = 0;
			if (strpos($f->nombre, ".") !== FALSE) {
				$partes = preg_split("/\./", $f->nombre);
				$extension = $partes[count($partes) - 1];
				if (isset($extensiones[$extension])) {
					$asociacion_extensiones[$f->fid] =
						$extensiones[$extension];
				}
			}
		}

		// Actualizamos finalmente la base de datos
		foreach ($asociacion_extensiones as $f => $mid) {
			$CI->db->query("UPDATE ficheros SET mid='".$mid."' WHERE
					fid='".$f."'");
		}
		
		return array(TRUE, 'Actualizada la asociación de extensiones');
	}
}
