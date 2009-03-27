<?php
$this->load->helper('form');
// Mensaje de actualización, envío, etc
$msj = $this->session->flashdata('mensaje_fichero');

if ($msj) {
	echo '<div class="cuadro ok">' . $msj . '</div>';
}

if (isset($error)) {
	echo '<div class="cuadro error">' . $error . '</div>';
}


// Estimación de mimetype
$mimetype = $this->trabajoficheros->consigue_mimetype($fichero->nombre);
?>


<div class="descarga_fichero">
<?php
if (empty($fichero->password)) {
	echo '<a href="' 
		. site_url($fichero->fid . '/descarga')
		.'">';
}
?>
 <img src="<?php echo site_url('img/tipos/128x128/' . $mimetype[1])?>"
  alt="<?php echo $mimetype[0]?>" /><br />

 <span class="nombre_fichero<?php echo
 ($this->trabajoficheros->es_propietario($fichero) ? ' fichero_propio' : '')
 ?>"><?php echo $fichero->nombre?></span><br />
 <span class="tam_fichero"><?php echo
	 $this->trabajoficheros->tam_fichero($fichero->tam)?></span>
<?php
if (empty($fichero->password)) {
	echo '</a>';
}
?>
</div>

<?php
	 if (!empty($fichero->password)):
		 echo form_open($fichero->fid . '/descarga');

?>

<div id="cuadro_password_fichero">
<?php 
 echo form_label('Contraseña:', 'passwd-fichero');

 $data_passwd_fichero = array(
	 'name' => 'passwd-fichero',
	 'id' => 'passwd_fichero',
	 'maxlength' => '255',
	 'size' => '15',
	 'class' => 'passwd',
 );
 echo form_password($data_passwd_fichero); 
?>
	<input type="image" src="<?php echo site_url('img/interfaz/boton-descarga.png') ?>" value="Descargar" alt="Descargar">
<?php
 echo form_close(); 
?>
</div>
<?php
endif;
?>

<div class="ficha_fichero">
 <ul>
 <?php
 if (isset($permiso_modificacion)):
 ?>
  <li class="modificar">
  <img src="<?php echo site_url('img/interfaz/modificar.png')?>"
  alt="modificar" /><a href="<?php echo site_url('modificar/' 
  	. $fichero->fid)?>">Modificar</a></li>
  <li class="borrar">
  <img src="<?php echo site_url('img/interfaz/borrar.png')?>"
  alt="borrar" /><a href="<?php echo site_url('borrar/' 
  	. $fichero->fid)?>">Borrar</a></li>
 <?php
 endif;
 ?>
  <li><img src="<?php echo site_url('img/interfaz/fecha-envio.png')?>"
  alt="fecha de envío" /><?php echo
  $this->trabajoficheros->fecha_legible($fichero->fechaenvio);?> 
	  (caduca en <?php echo
	   $this->trabajoficheros->intervalo_tiempo($fichero->fechaexp - time(),
		   3);?>)</li>

  <li><?php echo $this->trabajoficheros->usuario_html($fichero->remitente,
		  $fichero->mostrar_autor)?></li>
<?php
  	if ($fichero->listar == 0):
?>
  <li><img src="<?php echo site_url('img/interfaz/oculto.png')?>"
   alt="fichero oculto" /> Este fichero no se muestra en la página
   principal</li>
<?php
	endif;
?>

  <li><img src="<?php echo site_url('img/interfaz/descripcion.png')?>"
  alt="descripción" /> Descripción:
  
  <div class="descripcion_fichero"><?php echo
  empty($fichero->descripcion) ? 'Sin descripción' : $fichero->descripcion?></div>
  </div>
 </ul>
</div>

