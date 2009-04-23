<?php
$titulo = isset($titulo) ? $titulo : "Listado de ficheros";
?>
<h1><?php echo $titulo; ?></h1>
 <table id="listado-ficheros">
  <thead>
   <tr>
    <th></th>
	<th><?php echo $orden['nombre']?></th>
	<th><?php echo $orden['tam']?></th>
	<th><?php echo $orden['fechaenvio']?></th>
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
	echo '<tr id="fichero-'. $f->fid .'" class="'.$clase.'"
		rel="'.site_url('ficheros/minipagina/' . $f->fid).'">';
	echo '<td>';
    if (!$permitido) {
        echo ' <img src="' . site_url('img/interfaz/prohibido.png') .'"
	  alt="acceso denegado"/>';
    } else {
		echo '
			<img src="' . site_url('img/tipos/32x32/' . $mimetype[1]) . '"
			alt="' . $mimetype[0] . '"/>';
		if ($f->listar == 0) {
			echo '<img src="'. site_url('img/interfaz/oculto.png')
				.'" alt="fichero oculto" />';
		}

		echo '</td>';
	}

	// Nombre del fichero
    if ($permitido) {

		// ¿Propietario?
		$atributos_enlace = array();
		if ($this->trabajoficheros->es_propietario($f)) {
			$atributos_enlace['class'] = 'fichero_propio';
		}

        echo '<td>'. anchor($f->fid, $f->nombre,
				$atributos_enlace) . '</td>';
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

<?php 
echo $this->pagination->create_links();
?>
