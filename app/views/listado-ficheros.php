<h2>Listado de ficheros</h2>
 <table id="listado-ficheros">
  <thead>
   <tr>
    <th></th>
	<th>Nombre del fichero</th>
	<th>Tama&ntilde;o</th>
	<th>Fecha de env&iacute;o</th>
   </tr>
  </thead>
  <tbody>
<?php
foreach ($ficheros as $f) {
	// Estimación de mimetype
	$mimetype = $this->trabajoficheros->consigue_mimetype($f->nombre);

	// ¿Tiene permitido el acceso?
	$permitido = $this->trabajoficheros->acceso_fichero($f);
	if ($permitido === TRUE) {
		$clase = 'permitido';
	} else {
		$clase = 'denegado';
	}
	echo '<tr id="fichero-'. $f->fid .'" class="'.$clase.'">';
	echo '<td>';
    if (!$permitido) {
        echo ' <img src="' . site_url('img/interfaz/prohibido.png') .'"
	  alt="acceso denegado"/>';
    }
    echo '
	 <img src="' . site_url('img/tipos/16x16/' . $mimetype[1]) . '"
	  alt="' . $mimetype[0] . '"/></td>';

	// Nombre del fichero
    if ($permitido) {
        echo '<td>'. anchor('ficheros/' . $f->fid, $f->nombre)  .'</td>';
    } else {
        echo '<td>'. $f->nombre  .'</td>';
    }

	// Tamaño
	echo '<td>' . $this->trabajoficheros->tam_fichero($f->tam) . '</td>';

	// Fecha de envío
	echo '<td>' . $this->trabajoficheros->fecha_legible($f->fechaenvio) .
		'</td>';

	echo "</tr>\n";
}
?>
  </tbody>
 </table>
