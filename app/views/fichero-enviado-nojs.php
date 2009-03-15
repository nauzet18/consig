<div class="cuadro ok">
 El fichero fue enviado correctamente
</div>

<p>El fichero que ha enviado está disponible en la dirección <?php echo
anchor($fid)?>, de acuerdo a las opciones que ha señalado en
el momento del envío.</p>

<?php
// TODO: desde dentro de la universidad también va a poder mandar mensajes
// (aunque no cambiar las opciones)

if ($this->session->userdata('autenticado')):
?>

<p>Recuerde que en el enlace anterior podrá cambiar algunas opciones
relativas al fichero y enviar un correo a las direcciones que
especifique para recomendar la descarga del mismo.</p>
<?php
endif;
?>
