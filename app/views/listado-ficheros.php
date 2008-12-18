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
	echo '<tr>';
	echo '<td>
	 <img src="' . site_url('img/tipos/16x16/' . $mimetype[1]) . '"
	  alt="' . $mimetype[0] . '"/></td>';

	// Nombre del fichero
	echo '<td>'. $f->nombre  .'</td>';

	// Tamaño
	echo '<td>' . $this->trabajoficheros->tam_fichero($f->tam) . '</td>';

	// Fecha de envío
	echo '<td>' . $this->trabajoficheros->fecha_legible($f->fechaenvio) .
		'</td>';

	echo '</tr>';
}
?>
  </tbody>
 </table>
