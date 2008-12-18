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
	  (caduca en <?php echo
	   $this->trabajoficheros->intervalo_tiempo($fichero->fechaexp - time(),
		   3);?>)</li>

  <li><?php echo $this->trabajoficheros->usuario_html($fichero->remitente,
		  $fichero->mostrar_autor)?></li>

  <li><img src="<?php echo site_url('img/interfaz/descripcion.png')?>"
  alt="descripción" /> Descripción:
  
  <div class="descripcion_fichero"><?php echo
  empty($fichero->descripcion) ? 'Sin descripción' : $fichero->descripcion?></div>
  </div>
 </ul>
</div>

