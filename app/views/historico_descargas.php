<li><img src="<?php echo site_url('img/interfaz/descargas.png')?>"
alt="descargas" />
<?php echo $historico_num . ' ' . ($historico_num == 1 ? 'vez' : 'veces') ?>
	  descargado

<?php
if (isset($historico_detallado)):
?>
<img id="ver_historico_detallado" 
src="<?php echo site_url('img/interfaz/buscar.png')?>" alt="Detalle" />
<?php
endif;
?>
</li>

<?php
if (isset($historico_detallado)):
?>
<table id="historico_detallado">
 <thead>
  <tr>
   <th>Fecha y hora</th>
   <th>Identidad del usuario</th>
   <th>IP</th>
  </tr>
 </thead>
 <tbody>
<?php
	foreach ($historico_detallado as $descarga) {
		echo '<tr>';
		echo '<td>' .
			$this->manejoauxiliar->fecha_legible($descarga->timestamp) . '</td>';
		echo '<td>' . $descarga->identidad . '</td>';
		echo '<td>' . $descarga->ip . '</td>';
		echo '</tr>';
	}
?>
 </tbody>
</table>
<?php
endif;
?>
