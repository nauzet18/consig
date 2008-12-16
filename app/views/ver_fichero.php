<h2>Descarga de fichero</h2>

<?php
// Mensaje de actualización, envío, etc
$msj = $this->session->flashdata('mensaje_fichero');

if ($msj) {
	echo '<div class="cuadro ok">' . $msj . '</div>';
}
?>
