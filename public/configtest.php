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

/*
 * Las comprobaciones que se van a hacer son las siguientes:
 *
 * - Versión de PHP >= 5.2.0
 * - PHP + MySQL
 * - Extensión uploadprogress
 * - Subida de ficheros en php.ini
 * - Ficheros config.php y database.php (Detener si no)
 * - Conexión a la BD correcta
 * - Directorio de logs existente y con permiso de escritura
 * - Directorio de ficheros existente y con permiso de escritura
 * - PHP + LDAP si se usa el módulo correspondiente (+ conectividad)
 * - cURL si se usa la funcionalidad del antivirus
 * - php-mbstring si se usa la funcionalidad del antivirus
 * - Reescritura de URLs
 */

$tests = array();
$continuar = TRUE;

// Versión de PHP
$comparacion = version_compare(phpversion(), '5.2.0');

if ($comparacion >= 0) {
	$tests[] = array('Versión de PHP', phpversion(), 'OK');
} else {
	$tests[] = array('Versión de PHP', PHP_VERSION, 
			'Es muy recomendable usar PHP &gt;= 5.2.0');
}

// PHP + MySQL
if (!function_exists('mysql_connect')) {
	$tests[] = array('PHP + MySQL', 'No', 
			'Debe instalar el soporte de MySQL en PHP. En su distribución'
			.' podría llamarse <tt>php-mysql</tt> o parecido.');
} else {
	$tests[] = array('PHP + MySQL', 'Sí', 'OK');
}

// Extensión uploadprogress
$vup = phpversion('uploadprogress');
if (empty($vup)) {
	$tests[] = array('Extensión uploadprogress', 'No', 
			'Compruebe que ha instalado la extensión y que la carga en '
			.'el fichero <tt>php.ini</tt> correctamente.');
} else {
	$tests[] = array('Extensión uploadprogress', $vup, 'OK');
}

// Subida de ficheros en php.ini
if (ini_get('file_uploads') != 1) {
	$tests[] = array('Subida de ficheros en php.ini', 'No',
			'Debe habilitar el envío de ficheros. Consulte la '
			.'documentación de instalación');
} else {
	// POST > Tamaño máximo upload
	$tam_upload = return_bytes(ini_get('upload_max_filesize'));
	$tam_post = return_bytes(ini_get('post_max_size'));

	if ($tam_post <= $tam_upload) {
		$tests[] = array('Subida de ficheros en php.ini', 
				'Sí, pero <tt>post_max_size</tt> &lt;= '
				.'<tt>upload_max_filesize</tt>',
				'Corrija el problema en el fichero <tt>php.ini</tt>');
	} else {
		$tests[] = array('Subida de ficheros en php.ini', 'Todo bien',
				'OK');
	}
}

// Ficheros config.php y database.php (Detener si no)
$diractual = dirname(__FILE__);
$dirconfig = preg_replace('/public$/', 'app/config', $diractual);

if (!file_exists($dirconfig . '/config.php')) {
	$tests[] = array('Fichero <tt>config.php</tt>',
			'No existe o Apache no tiene acceso a él', 
			'Créalo usando la plantilla <tt>config.php-MODELO</tt>');

	$continuar = FALSE;
} else {
	$tests[] = array('Fichero <tt>config.php</tt>', 'Existe', 'OK');
}

if ($continuar && !file_exists($dirconfig . '/database.php')) {
	$tests[] = array('Fichero <tt>database.php</tt>', 
			'No existe o Apache no tiene acceso a él', 
			'Créalo usando la plantilla <tt>database.php-MODELO</tt>');
	$continuar = FALSE;

} elseif ($continuar) {
	$tests[] = array('Fichero <tt>database.php</tt>', 'Existe', 'OK');
}

if ($continuar) {

// Engañamos a los ficheros de configuración
define('BASEPATH', '/tmp');
include($dirconfig .'/config.php');
include($dirconfig .'/database.php');

// Conexión a la BD

$bd = $db['default'];

$link = @mysql_connect($bd['hostname'], $bd['username'], 
		$bd['password']);
if (!$link) {
	$tests[] = array('Conexión a la BD', 'No se puede conectar: <tt>' .
			mysql_error() .'</tt>', 'Compruebe los parámetros de '
			.'conexión a la base de datos (fichero <tt>database.php</tt>');
} else {
	$ret = @mysql_select_db($bd['database'],$link);
	if ($ret === FALSE) {
		$tests[] = array('Conexión a la BD', 'Conecta, pero no puede '
				.'usarse la base de datos <tt>'.$bd['database'].'</tt>',
				'Compruebe que el usuario tiene acceso');
	} else {
		$tests[] = array('Conexión a la BD', 'Sin problemas', 'OK');
	}
	@mysql_close($link);
}

// Directorio de logs existente y con permiso de escritura
if (!is_writable($config['log_path'])) {
	$tests[] = array('Directorio de logs', 
		'No existe o el usuario de Apache no puede escribir',
		'Compruebe el directorio <tt>'.$config['log_path'].'</tt>');
} else {
	$tests[] = array('Directorio de logs', 
			'Existe y se puede escribir en él', 'OK');
}

// Directorio de ficheros existente y con permiso de escritura
if (!is_writable($config['directorio_ficheros'] . '/')) {
	$tests[] = array('Directorio de ficheros', 
		'No existe o el usuario de Apache no puede escribir',
		'Compruebe el directorio <tt>'.$config['directorio_ficheros'].'</tt>');
} else {
	$tests[] = array('Directorio de ficheros', 
			'Existe y se puede escribir en él', 'OK');
}


// PHP + LDAP si se usa el módulo correspondiente (+ conectividad)
if (isset($config['authmodule']) && $config['authmodule'] == 'LDAP') {
	if (!function_exists('ldap_connect')) {
		$tests[] = array('Soporte LDAP', 'No',
			'Debe instalar el soporte de LDAP en PHP. En su distribución'
			.' podría llamarse <tt>php-ldap</tt> o parecido.');
	} else {
		$tests[] = array('Soporte LDAP', 'Sí', 'OK');

		// Fichero de configuración de LDAP
		if (!file_exists($dirconfig . '/ldap.php')) {
			$tests[] = array('Fichero <tt>ldap.php</tt>', 
					'No existe o Apache no tiene acceso a él', 
					'Créalo usando la plantilla <tt>ldap.php-MODELO</tt>');
		} else {
			// Probamos a conectar
			include($dirconfig .'/ldap.php');
			$opciones = $config['ldap'];

			$ds = @ldap_connect($opciones["host"], $opciones["puerto"]);
			if (!$ds) {
				$tests[] = array('Conexión a LDAP', 'No se puede conectar' .
						'Problemas de creación en PHP '
						.'para acceso a LDAP');
			} else {
				@ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3);
				// Prueba de bind
				if (@ldap_bind($ds, $opciones['dnadmin'],
						$opciones['passwdadmin']) !== TRUE) {
					// Problema con bind
					$tests[] = array('LDAP', 'Problemas de conexión'
						.' o bind en LDAP', 'Compruebe el fichero '
						.'<tt>ldap.php</tt>. Error: ' . ldap_error($ds));
				} else {
					// Todo OK
					$tests[] = array('LDAP', 'Sin problemas', 'OK');
				}
			}
		}
	}
}

// cURL si se usa la funcionalidad del antivirus
if (isset($config['activar_antivirus'])) {
	if (!function_exists('curl_init')) {
		$tests[] = array('cURL', 'No están disponibles las '
			.'bibliotecas de cURL', 'Instale las extensiones'
			.' cURL para PHP');
	} else {
		$tests[] = array('cURL', 'Extensiones instaladas', 'OK');
	}

	// Ídem para php-mbstring
	if (!function_exists('mb_strlen')) {
		$tests[] = array('php-mbstring', 'No están disponibles las '
			.'bibliotecas mbstring', 'Instale las extensiones'
			.' mbstring para PHP');
	} else {
		$tests[] = array('php-mbstring', 'Extensiones instaladas', 'OK');
	}
}



// ---- Fin tests -----
} // $continuar

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>
<title>Consigna | test de configuración</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="css/estilo.css" type="text/css" media="screen">
<script language="JavaScript" type="text/javascript">
//<![CDATA[

// Variables útiles para procesado Javascript
var url_base = '<?php echo $config['base_url']?>';
//]]>
</script>
<script language="JavaScript" type="text/javascript"
src="<?php echo $config['base_url']?>js/jquery-1.4.4.min.js"></script>
<script language="JavaScript" type="text/javascript"
src="<?php echo $config['base_url']?>js/configtest.js"></script>

</head>
<body>

 <div id="contenido">
  <h1>Test de configuración</h1>

  <table>
   <thead>
    <tr>
	 <th>Comprobación</th>
	 <th>Resultado</th>
	 <th>Comentarios</th>
	</tr>
   </thead>
   <tbody>
   <?php
   foreach ($tests as $test) {
	   ?>
		   <tr>
		   <td><?php echo $test[0]?></td>
		   <td style="color: #ffffff; background-color: <?php 
		   	echo $test[2] == 'OK' ?
		   	'#00bb00' : '#bb0000' ?>"><?php echo $test[1]?></td>
		   <td><?php echo $test[2] ?></td>
		   </tr>
	   <?php
   }
   ?>
   </tbody>
  </table>

 </div>
</body>
</html>
<?php

// Auxiliares
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}
?>
