<p>Use su usuario virtual de la Universidad de Sevilla (UVUS) y su
contraseña para autenticarse en Consigna</p>
<?php
/*
 * Formulario de login
 */

$this->load->helper('html');
$this->load->library('form_validation');

// Errores
if (validation_errors() || !empty($error)) {
	echo '<div class="cuadro error">';
	echo validation_errors();
	echo !empty($error) ? $error : "";
	echo '</div>';
}

$atr = array(
		'id' => 'form_login'
);

echo form_open('usuario/login', $atr);

// Devolver al usuario a una página concreta

$devolver = isset($devolver_a) ? 
	$devolver_a : $this->input->post('devolver');
if ($devolver) {
	echo form_hidden('devolver', $devolver);
}

// Campos
$usuario = array(
		'name' => 'usuario',
		'class' => 'usuario',
		'value' => set_value('usuario'),
		'maxlength' => '30',
		'size' => '20',
		'tabindex' => '1',
);

echo form_label('Usuario virtual (UVUS):', 'usuario');
echo form_input($usuario);
echo br();

$passwd = array(
		'name' => 'passwd',
		'class' => 'passwd passwd-usuario',
		'value' => '',
		'maxlength' => '30',
		'size' => '20',
		'tabindex' => '2',
);

echo form_label('Contraseña:', 'passwd');
echo form_password($passwd);
echo br();

echo form_submit('login', 'Entrar');

echo form_close();

?>
