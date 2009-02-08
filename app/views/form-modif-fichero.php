<?php
/*
 * $datos_fichero es un array con los datos del fichero de edición
 */

$this->load->helper('form');
$this->load->helper('html');
$this->load->library('form_validation');

// Errores
if (validation_errors() || !empty($error)) {
	echo '<div class="cuadro error">';
	echo validation_errors();
	echo !empty($error) ? $error : "";
	echo '</div>';
}

echo '<h2>'.$fichero->nombre.'</h2>';

$atr = array(
		'id' => 'form_modif',
		'autocomplete' => 'off', // Evitamos que los navegadores
								 // intenten recordar datos
		);
echo form_open('modificar/' . $fichero->fid, $atr);

echo form_fieldset('Datos del fichero');

$this->load->view('form-opciones-fichero', array(
			'fichero' => $fichero,
			'mostrar_todo' => 1,
			));


?>
