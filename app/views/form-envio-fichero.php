<?php
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

$atr = array(
		'id' => 'form_subida',
		'autocomplete' => 'off', // Evitamos que los navegadores
								 // intenten recordar datos
		);
echo form_open_multipart('ficheros/nuevo', $atr);

// Id del envío
echo '<input type="hidden" name="UPLOAD_IDENTIFIER" id="id_envio" value="'
	. $id_envio . '" />';

// Cuadro de envío
echo form_fieldset('Datos del fichero');

echo form_label('Fichero que desea enviar: (máximo: '.ini_get('upload_max_filesize').')', 'fichero');

$data = array(
		'name' => 'fichero',
		);
echo form_upload($data);
echo br();


$passwd = array(
		'name' => 'fichero_passwd',
		'class' => 'passwd passwd-fichero',
		'value' => set_value('fichero_passwd', ''),
		'maxlength' => '30',
		'size' => '20',
);

echo form_label('Contraseña de acceso al fichero:', 'fichero_passwd');
echo form_password($passwd);

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
		'value' => set_value('descripcion', ''),
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
	echo form_dropdown('expiracion', $opc_expiracion, '2sem');
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
		. set_radio('listar', '1', TRUE).'/> Sí</div>';
	echo '<div class="opcion"><input type="radio" name="listar" value="0" ' 
		. set_radio('listar', '0') .'/> No</div>';
	echo '</div>';


	echo form_label('¿Desea que sea posible únicamente su descarga para
			usuarios autenticados o conectados a la red de la Universidad de
			Sevilla?', 'tipoacceso');
	echo '<div class="opciones_radio">';
	echo '<div class="opcion"><input type="radio" name="tipoacceso" value="0" '
		. set_radio('tipoacceso', '0', TRUE). '/> Sí</div>';
	echo '<div class="opcion"><input type="radio" name="tipoacceso" value="1" ' 
		. set_radio('tipoacceso', '1') . '/> No, el acceso al fichero podrá
		realizarse desde cualquier ubicación y sin necesidad de
		autenticación</div>';
	echo '</div>';

	echo form_label('¿Desea que el fichero quede asociado públicamente a su
			nombre?', 'mostrar_autor');
	echo '<div class="opciones_radio">';
	echo '<div class="opcion"><input type="radio" name="mostrar_autor" value="1" ' 
		. set_radio('mostrar_autor', '1', TRUE) . '/> Sí</div>';
	echo '<div class="opcion"><input type="radio" name="mostrar_autor" value="0" ' 
		. set_radio('mostrar_autor', '0') . '/> No, no mostrar públicamente que soy
		yo quien envía el fichero</div>';
	echo '</div>';

	echo '</div>'; // contenido_enrollado
	echo form_fieldset_close();
}

echo form_submit('enviar', 'Enviar');
echo br();


echo form_close();
?>


<div id="progreso_overlay">
 <img id="progreso_indicador" 
  src="<?php echo site_url('img/interfaz/ajax-loader.gif');?>" 
  alt="Cargando..." />
 <h4>
 Espere, su fichero se está enviando...
 </h4>
 <div id="progreso_contenedor"><div id="progreso">0%</div></div>
 <div id="progreso_datos"><span id="progreso_velocidad">- kB/s</span>
 (tiempo restante: <span id="progreso_restante">desconocido</span>)</div>
 <div id="progreso_cancelar"><img src="<?php echo
	 site_url('img/interfaz/cancelar.png')?>" /></div>
</div>
