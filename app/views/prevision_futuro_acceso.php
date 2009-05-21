<?php
$img_si = '<img src="'.site_url('img/interfaz/vale.png').'" alt="sí" />';
$img_no = '<img src="'.site_url('img/interfaz/cancelar.png').'" alt="no" />';
echo form_fieldset('Previsión de permisos de acceso');
?>
<p>
El fichero que va a subir podrá ser accedido en los siguientes casos:
</p>

 <ul class="lista-permisos">
 <li id="prevision-peor-caso"><?php echo ($prevision == 0 ? $img_no : $img_si) ?> Usuarios anónimos conectados desde fuera de la red de la
 Universidad de Sevilla</li>
 <li><?php echo $img_si ?> Usuarios anónimos conectados desde la red de la Universidad de
 Sevilla</li>
 <li><?php echo $img_si; ?> Usuarios autenticados desde cualquier ubicación</li>
 </ul>
<?php
 echo form_fieldset_close();
?>
