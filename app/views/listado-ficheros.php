<?php
$titulo = isset($titulo) ? $titulo : "Listado de ficheros";
?>
<h1><?php echo $titulo; ?></h1>

<?php
if (isset($caja_busqueda) && $caja_busqueda == 1) {
	$this->load->view('busqueda');
}

if (isset($total_ocupado)) {
	echo '<div class="tamtotal">Tamaño total: '.
		$this->manejoauxiliar->tam_fichero($total_ocupado) 
		.  "</div>\n";
}

?>
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
if (count($ficheros) == 0) {
	echo '<tr><td colspan="4" class="sin-ficheros">'
		.'No hay ficheros para mostrar</td></tr>';
}

$es_privilegiado = $this->gestionpermisos->es_privilegiado();

foreach ($ficheros as $f) {
	$es_propietario = FALSE;

	// ¿Tiene permitido el acceso?
	$permitido = $this->gestionpermisos->acceso_fichero($f);
	if ($permitido === TRUE) {
		$clase = 'permitido';
		$es_propietario = $this->trabajoficheros->es_propietario($f);
	} else {
		$clase = 'denegado';
	}
	echo '  <tr id="fichero-'. $f->fid .'" class="'.$clase.'" '
		.' rel="'.site_url('ficheros/minipagina/' . $f->fid).'">';
	echo "\n";
	echo '   <td>';
    if (!$permitido) {
        echo '<img src="' . site_url('img/interfaz/prohibido.png') .'"
	  alt="acceso denegado"/>';
    } else {
		echo '<img src="' 
			. site_url('img/tipos/32x32/' . $f->icono) 
			. '" alt="' . $f->mimetype. '"/>';
		if ($f->listar == 0) {
			echo '<img class="icono_oculto" src="'
				. site_url('img/interfaz/oculto.png')
				.'" alt="fichero oculto" />';
		}

	}
	echo "   </td>\n";

	// Nombre del fichero
	$f->nombre = $this->manejoauxiliar->abrevia($f->nombre);

    if ($permitido) {

		// ¿Propietario?
		$atributos_enlace = array();
		if ($es_propietario) {
			$atributos_enlace['class'] = 'fichero_propio';
		}

        echo '   <td class="td_nombrefich">'. anchor($f->fid, $f->nombre,
				$atributos_enlace) . "</td>\n";
    } else {
        echo '   <td class="td_nombrefich">'. $f->nombre  ."</td>\n";
    }

	// Tamaño
	echo '   <td>' . $this->manejoauxiliar->tam_fichero($f->tam) . "</td>\n";

	// Fecha de envío, y expiración si es el propietario del fichero
	$texto_fecha = $this->manejoauxiliar->fecha_legible($f->fechaenvio);

	if ($es_privilegiado || $es_propietario) {
		 $texp =  $f->fechaexp - time();
		 if ($texp <= 0) {
			 $texto_expiracion = 'a punto de caducar';
		 } else {
			 $texto_expiracion =
				 $this->manejoauxiliar->intervalo_tiempo($texp, 2);
		 }
		 $texto_fecha .= ' <div class="expiracion_mini">' .
			 $texto_expiracion . '</div>';
	}

	echo '   <td>' . $texto_fecha .  "</td>\n";

	echo "  </tr>\n";
}
?>
  </tbody>
 </table>

<?php 
echo $this->pagination->create_links();
?>
