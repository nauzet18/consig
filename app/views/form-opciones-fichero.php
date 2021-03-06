<?php

/*
 * Valores para los campos. Se rellenan en función del tipo de acceso al
 * formulario (nuevo fichero, edición de fichero) y de la circunstancia en
 * que se carga (error al rellenar la ficha, por ejemplo).
 */

$descripcion = isset($fichero) ? $fichero->descripcion : '';
// XXX: pensar en las posibilidades de cambiar la fecha de expiración (lo de
// abajo es erróneo)
//$expiracion = isset($fichero) ? $fichero->expiracion : '2sem';
$expiracion = $this->config->item('expiracion_defecto');
$listar = array(
		(isset($fichero) ? ($fichero->listar == 0) : FALSE),
		(isset($fichero) ? ($fichero->listar == 1) : TRUE));

// El valor por defecto del tipo de acceso depende de si el usuario es
// anónimo o no

$defecto_ta_publico = ($this->session->userdata('autenticado') == 1);

$tipoacceso = array(
		(isset($fichero) ? ($fichero->tipoacceso == 0) : !$defecto_ta_publico),
		(isset($fichero) ? ($fichero->tipoacceso == 1) : $defecto_ta_publico));
$mostrar_autor = array(
		(isset($fichero) ? ($fichero->mostrar_autor == 0) : FALSE),
		(isset($fichero) ? ($fichero->mostrar_autor == 1) : TRUE));

$valores_campos = array(
		'fichero_passwd' => '',
		'descripcion' => set_value('descripcion', $descripcion),
		'expiracion' => set_value('expiracion', $expiracion),
		'listar' => $listar,
		'tipoacceso' => $tipoacceso,
		'mostrar_autor' => $mostrar_autor,
);

// Cuadro de envío
if (isset($fichero)) {
	echo form_hidden('fid', $fichero->fid);
}


$passwd = array(
		'name' => 'fichero_passwd',
		'class' => 'passwd passwd-fichero',
		'value' => $valores_campos['fichero_passwd'],
		'maxlength' => '30',
		'size' => '20',
);


// ¿Envío nuevo o existente?
if (isset($fichero)) {
	echo form_label('Contraseña de acceso al fichero (no rellenar para dejar
				igual):', 'fichero_passwd');
	echo form_password($passwd);
	$eliminar_passwd = array(
			'name' => 'eliminar_passwd',
			'class' => 'eliminar-passwd',
			'value' => 1,
			'checked' => FALSE
	);
	echo form_checkbox($eliminar_passwd);
	echo ' Dejar fichero sin clave';
} else {
	echo form_label('Contraseña de acceso al fichero:', 'fichero_passwd');
	echo form_password($passwd);
}

if (isset($mostrar_todo) && $mostrar_todo):
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

// TODO: al editar ficheros, ¿permitir cambios de fecha de expiración?
if ($mostrar_todo && !isset($fichero)) {
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

if (isset($fichero)) {
	$texto_boton = 'Modificar';
} else {
	$texto_boton = 'Enviar';
}


// Previsión de acceso a usuarios

$valor_temporal_tipoacceso = $valores_campos['tipoacceso'][0] ? 0 : 1;

$this->load->view('prevision_futuro_acceso', array('prevision' =>
			$this->trabajoficheros->futuro_acceso($valor_temporal_tipoacceso)));


echo form_submit('enviar', $texto_boton);
echo br();


echo form_close();
?>
