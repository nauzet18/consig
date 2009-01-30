<?php

/*
 * Valores para los campos. Se rellenan en función del tipo de acceso al
 * formulario (nuevo fichero, edición de fichero) y de la circunstancia en
 * que se carga (error al rellenar la ficha, por ejemplo).
 */

$descripcion = isset($descripcion) ? $descripcion : '';
$expiracion = isset($expiracion) ? $expiracion : '2sem';
$listar = array(
		(isset($listar) ? ($listar == 0) : FALSE),
		(isset($listar) ? ($listar == 1) : TRUE));
$tipoacceso = array(
		(isset($tipoacceso) ? ($tipoacceso == 0) : TRUE),
		(isset($tipoacceso) ? ($tipoacceso == 1) : FALSE));
$mostrar_autor = array(
		(isset($mostrar_autor) ? ($mostrar_autor == 0) : FALSE),
		(isset($mostrar_autor) ? ($mostrar_autor == 1) : TRUE));

$valores_campos = array(
		'fichero_passwd' => '',
		'descripcion' => set_value('descripcion', $descripcion),
		'expiracion' => set_value('expiracion', $expiracion),
		'listar' => $listar,
		'tipoacceso' => $tipoacceso,
		'mostrar_autor' => $mostrar_autor,
);

// Cuadro de envío
if (isset($fid)) {
	echo form_fieldset('Datos del fichero');
}


// ¿Envío nuevo o existente?
if (isset($fid)) {
	echo form_hidden('fid', $fid);
}


$passwd = array(
		'name' => 'fichero_passwd',
		'class' => 'passwd passwd-fichero',
		'value' => $valores_campos['fichero_passwd'],
		'maxlength' => '30',
		'size' => '20',
);


// ¿Envío nuevo o existente?
if (isset($fid)) {
	echo form_label('Contraseña de acceso al fichero (no rellenar para dejar
				igual):', 'fichero_passwd');
} else {
	echo form_label('Contraseña de acceso al fichero:', 'fichero_passwd');
}
echo form_password($passwd);

// TODO: en edición, permitir eliminar la clave


if ($mostrar_todo):
?>
<div class="form_descripcion">
Es obligatorio especificar una contraseña si el fichero podrá ser
accesible desde cualquier ubicación y sin autenticar. En otro caso, será
opcional.
</div>
<?php
endif;

$data = array(
		'name' => 'descripcion',
		'rows' => '5',
		'cols' => '30',
		'value' => $valores_campos['descripcion'],
);

echo form_label('Describa su envío (opcional):', 'descripcion');
echo form_textarea($data);
echo br();

if ($mostrar_todo) {
	// Expiración
	$opc_expiracion = array(
			'1h' => '1 hora',
			'1d' => '1 día',
			'1sem' => '1 semana',
			'2sem' => '2 semanas',
	);

	echo form_label('¿Cuánto tiempo desea que esté disponible su envío?',
			'expiracion');
	echo form_dropdown('expiracion', $opc_expiracion,
			$valores_campos['expiracion']);
	echo br();
?>
<div class="form_descripcion">
Pasado el tiempo indicado en esta opción, el fichero será borrado
automáticamente del sistema.
</div>
<?php
}

echo form_fieldset_close();



// Opciones del envío, si se tienen que mostrar
if ($mostrar_todo) {
	echo form_fieldset('Opciones del envío', array('class' => 'enrollable desplegado'));
	echo '<div class="contenido_enrollado">';
	echo form_label('¿Desea que el fichero sea listado en la página
			principal?', 'listar');
	echo '<div class="opciones_radio">';
	echo '<div class="opcion"><input type="radio" name="listar" value="1" '
		. set_radio('listar', '1', $valores_campos['listar'][1]).'/> Sí</div>';
	echo '<div class="opcion"><input type="radio" name="listar" value="0" ' 
		. set_radio('listar', '0', $valores_campos['listar'][0]) .'/> No</div>';
	echo '</div>';


	echo form_label('¿Desea que sea posible únicamente su descarga para
			usuarios autenticados o conectados a la red de la Universidad de
			Sevilla?', 'tipoacceso');
	echo '<div class="opciones_radio">';
	echo '<div class="opcion"><input type="radio" name="tipoacceso" value="0" '
		. set_radio('tipoacceso', '0', $valores_campos['tipoacceso'][0]). '/> Sí</div>';
	echo '<div class="opcion"><input type="radio" name="tipoacceso" value="1" ' 
		. set_radio('tipoacceso', '1', $valores_campos['tipoacceso'][1]) . '/> No, el acceso al fichero podrá
		realizarse desde cualquier ubicación y sin necesidad de
		autenticación</div>';
	echo '</div>';

	echo form_label('¿Desea que el fichero quede asociado públicamente a su
			nombre?', 'mostrar_autor');
	echo '<div class="opciones_radio">';
	echo '<div class="opcion"><input type="radio" name="mostrar_autor" value="1" ' 
		. set_radio('mostrar_autor', '1',
				$valores_campos['mostrar_autor'][1]) . '/> Sí</div>';
	echo '<div class="opcion"><input type="radio" name="mostrar_autor" value="0" ' 
		. set_radio('mostrar_autor', '0',
				$valores_campos['mostrar_autor'][0]) . '/> No, no mostrar públicamente que soy
		yo quien envía el fichero</div>';
	echo '</div>';

	echo '</div>'; // contenido_enrollado
	echo form_fieldset_close();
}

echo form_submit('enviar', 'Enviar');
echo br();


echo form_close();
?>
