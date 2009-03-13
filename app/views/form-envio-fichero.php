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

$this->load->view('form-opciones-fichero');

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
