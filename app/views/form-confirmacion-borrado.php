<?php
$this->load->helper('form');

echo form_open('borrar/' . $fichero->fid);
echo form_fieldset('Confirmación de borrado');
?>

<p>¿Está seguro de querer borrar el fichero <img src="<?php echo
site_url('img/tipos/32x32/' . $fichero->icono)?>" alt="<?php echo
$fichero->mimetype?>" /> <span class="nombre_fichero"><?php echo
$fichero->nombre?></span> (<span class="tam_fichero"><?php echo
	 $this->manejoauxiliar->tam_fichero($fichero->tam)?>)</span>?
</p>

<p>Si lo borra dejará de estar disponible en consigna.</p>

<div style="text-align: center">
 <?php

  echo form_submit('confirmacion', 'Sí, quiero borrarlo');

  echo form_close();
  ?>
</div>

<?php
echo form_fieldset_close();
?>
