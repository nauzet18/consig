<?php
// Mensaje de actualización, envío, etc
$msj = $this->session->flashdata('mensaje_fichero');

if ($msj) {
	echo '<div class="cuadro ok">' . $msj . '</div>';
}
?>

<h2>Fichero disponible</h2>

<div class="cuadro">
</div>
