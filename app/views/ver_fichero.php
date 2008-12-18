<?php
$this->load->helper('date');
// Mensaje de actualización, envío, etc
$msj = $this->session->flashdata('mensaje_fichero');

if ($msj) {
	echo '<div class="cuadro ok">' . $msj . '</div>';
}


// Estimación de mimetype
$mimetype = $this->trabajoficheros->consigue_mimetype($fichero->nombre);
?>


<div class="descarga_fichero">
 <img src="<?php echo site_url('img/tipos/128x128/' . $mimetype[1])?>"
  alt="<?php echo $mimetype[0]?>" />

 <span class="nombre_fichero"><?php echo $fichero->nombre?></span>
 <span class="tam_fichero"><?php echo
	 $this->trabajoficheros->tam_fichero($fichero->tam)?></span>
</div>

<div class="ficha_fichero">
 <ul>
  <li><img src="<?php echo site_url('img/interfaz/fecha-envio.png')?>"
  alt="fecha de envío" /><?php echo
  $this->trabajoficheros->fecha_legible($fichero->fechaenvio);?> 
	  (caduca en <?php echo $this->trabajoficheros->intervalo_tiempo(time() -
         $fichero->fechaexp, 2);?>)</li>


<?php
	  // Mostrar autor sólo si él lo ha autorizado
	  if ($fichero->mostrar_autor || empty($fichero->remitente)) {
		  echo '<li>' .
			  $this->trabajoficheros->usuario_html($fichero->remitente)  . '</li>';
	  }
?>
 </ul>
</div>

