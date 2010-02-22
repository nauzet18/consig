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
	 $this->manejoauxiliar->tam_fichero($fichero->tam)?></span>
<?php
if (empty($fichero->password)) {
	echo '</a>';
}

	/*
	 * Antivirus
	 */
	if (isset($info_av) && $info_av !== FALSE) {
		$this->load->view('antivirus/' . $info_av->estado,
				$info_av);
	}


	 // Contraseña del fichero

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
 </div> <!-- cuadro_password_fichero -->
<?php
echo form_close(); 
endif;
?>
</div> <!-- descarga_fichero -->


<div class="ficha_fichero">
 <?php
 if (isset($permiso_modificacion)):
 ?>
 <ul class="operaciones_fichero">
  <li class="modificar">
  <img src="<?php echo site_url('img/interfaz/modificar.png')?>"
  alt="modificar" /><a href="<?php echo site_url('modificar/' 
  	. $fichero->fid)?>">Modificar</a></li>
  <li class="borrar">
  <img src="<?php echo site_url('img/interfaz/borrar.png')?>"
  alt="borrar" /><a href="<?php echo site_url('borrar/' 
  	. $fichero->fid)?>">Borrar</a></li>

  </ul>

 <?php
 endif;


 // Cálculo del tiempo de expiración
 $texp =  $fichero->fechaexp - time();
 if ($texp <= 0) {
	 $texto_expiracion = 'a punto de caducar';
 } else {
	 $texto_expiracion = 'caduca en ' 
		 . $this->manejoauxiliar->intervalo_tiempo($texp, 3);
 }
 ?>

 <ul>
  <li><img src="<?php echo site_url('img/interfaz/www.png')?>"
  alt="enlace" />
  <a href="<?php echo site_url($fichero->fid)?>">
	<?php echo site_url($fichero->fid)?>
  </a></li>

  <li><img src="<?php echo site_url('img/interfaz/fecha-envio.png')?>"
  alt="fecha de envío" /><?php echo
  $this->manejoauxiliar->fecha_legible($fichero->fechaenvio);?> 
	  (<?php echo $texto_expiracion; ?>)</li>

  <li><?php echo $this->manejoauxiliar->remitente_de($fichero) ?></li>
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
  
  <div class="descripcion_fichero">
    <?php echo
  empty($fichero->descripcion) ? 'Sin descripción' : $fichero->descripcion?>
  </div>
 </ul>
</div> <!-- ficha_fichero -->

