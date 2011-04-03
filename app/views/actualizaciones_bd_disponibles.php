<h1>Debe actualizar su base de datos</h1>

<p>
La versión del esquema de su base de datos es la <tt><?php echo
$versionbd_usandose ?></tt>. Esta versión de consigna necesita actualizar a
la versión <tt><?php echo VERSIONBD?></tt>.
</p>

<h2>Recomendaciones</h2>
<ul>
 <li><strong>¡Importante!</strong>: haga siempre una copia de seguridad de
 su base de datos antes de ejecutar las actualizaciones. Puede hacerla con
 <tt>mysqldump</tt>.</li>

 <li>Limite el acceso a consigna mientras esté ejecutando la actualización,
 para evitar que los usuarios vean errores durante el proceso.</li>

 <li>Puede comprobar el progreso de las actualizaciones siguiendo el log
 (nivel de mensaje: <tt>INFO</tt>).</li>

<?php
$this->load->helper('form');

echo form_open('upgrade');
echo form_fieldset('Actualización');
?>
<p>¿Está seguro de querer actualizar? ¿Ha tenido en cuenta las
recomendaciones anteriores?</p>
<div style="text-align: center">
 <?php

  echo form_submit('confirmacion', 'Sí, quiero actualizar');

  echo form_close();
  ?>
</div>
<?php
echo form_fieldset_close();
?>
