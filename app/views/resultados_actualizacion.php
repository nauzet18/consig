<h1>Resultado de la actualización</h1>

<?php
if (TRUE === $exito):
?>
<div class="cuadro ok">
¡Las actualizaciones se aplicaron con éxito!
</div>

<p>La versión del esquema de la base de datos se ha actualizado. Recuerde
reactivar el acceso al sitio y compruebe que todo es correcto. También debe
desactivar el acceso a <tt>/upgrade</tt> si lo activó temporalmente para
llevar a cabo la actualización.</p>
<?php
else:
?>
<div class="cuadro error">
Hubo algún error actualizando el esquema de su base de datos
</div>

<p>
Compruebe en la tabla de abajo los mensajes de error. Restaure la copia de
seguridad de su base de datos para volver al estado anterior.
</p>

<?php
endif;
?>

<table>
 <thead>
  <tr>
   <th>Versión del esquema aplicada</th>
   <th>Información adicional</th>
  </tr>
 </thead>
 <tbody>
 <?php
 foreach ($resultados as $v => $estado) {
	 $estilo = '';
	 if ($estado[0] == 'error') {
		 $estilo = 'color: #ffffff; background-color: #bb0000';
	 } else {
		 $estilo = 'color: #ffffff; background-color: #00bb00';
	 }

	 echo '<tr><td>' . $v . '</td><td style="'.$estilo.'">';
	 if ($estado[0] == 'error') {
		 echo $estado[1];
	 } else {
		 echo 'Actualización correcta';
	 }
	 echo '</td></tr>';
 }
 ?>
 </tbody>
</table>

