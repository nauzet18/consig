<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>
<title>Consigna <?php if (!empty($subtitulo)) { echo "| ".$subtitulo;} ?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="<?php echo site_url('css/estilo.css')?>" type="text/css" media="screen"
/>
<?php
if (isset($css_adicionales)) {
	foreach ($css_adicionales as $css) {
		echo '<link rel="stylesheet" href="'
			. site_url('css/' . $css) . '" '
			.'type="text/css" media="screen" />';
	}
}
?>


<?php
 if ($this->config->item('habilitar_favicon')):
 ?>
 <link rel="icon" type="image/vnd.microsoft.icon" href="<?php echo
 site_url('img/favicon.ico')?>"/>
 <?php
 endif;
?>

<style type="text/css">
<?php 
// Arreglo de PNGs en Explorer (carga de Javascript)
echo '#menu img, #listado-ficheros img, .descarga_fichero img, .close { _behavior: url(' . site_url('js/iepngfix.htc') . '); }';
?>
</style>

<script language="JavaScript" type="text/javascript">
//<![CDATA[

// Variables útiles para procesado Javascript
var url_base = '<?php echo base_url()?>';
var user_auth = '<?php echo $this->session->userdata('autenticado'); ?>';
<?php
if ($this->session->userdata('autenticado')):
?>
var user_id = '<?php echo $this->session->userdata('id')?>';
var user_name = '<?php echo $this->session->userdata('name')?>';
<?php
endif;

// Antivirus
echo "var activar_antivirus = ";
echo ($this->config->item('activar_antivirus') === TRUE) ?
	'true' : 'false';
echo ";\n";
?>
//]]>
</script>

</head>

<body<?php echo isset($body_onload) ? ' onload="javascript:'.$body_onload.';"' : ''?>>
<?php
// ¿Autenticado?
$autenticado = $this->session->userdata('autenticado');
if ($autenticado !== FALSE) {
	echo '<div id="datos_autenticado">';
	echo 'Su identidad en este momento: ';
	echo '<span class="usuario">'.$this->session->userdata('name').'</span>';
	echo '</div>';
}
?>
<div id="cabecera">
 <div id="logo">
  <a href="http://www.us.es">
     <img id="imagen" src="<?php echo site_url('img/logos/Logo.gif')?>" 
  	alt="Universidad de Sevilla" width="105" height="91" />
  </a>
  <div id="titulologo">
  <h1><a href="<?php echo site_url()?>">Consigna</a></h1>
  <h2 id="subtitulo">Env&iacute;o y recogida de ficheros</h2>
  </div>
 </div> <!-- logo -->

 <?php
 if (!isset($no_mostrar_menu)):
 ?>
 <div id="menu">
  <ul>
   <li class="first">
   <div><a href="<?php echo site_url('ayuda')?>">
     <img src="<?php echo site_url('img/interfaz/ayuda.png')?>" alt="Ayuda"
	 width="48" height="48" /><br />
	 Ayuda</a>
	 <br /><span id="cond_uso">[<a rel="#cond_usoOverlay" href="<?php echo
	 site_url('ayuda/legal')?>">condiciones de uso</a>]</span>
	 </div>
   </li>

   <li><div><a href="<?php echo site_url('ficheros/nuevo')?>">
     <img src="<?php echo site_url('img/interfaz/nuevo.png')?>" alt="Nuevo" 
	 width="48" height="48" /><br />
	 Nuevo</a></div>
   </li>
   <?php
   // Seguir las indicaciones de no_mostrar_login, y ocultarlo
   // en el caso de no usar autenticación
   if ($this->config->item('authmodule') == "") {
		   $no_mostrar_login = TRUE;
   }

   if ($autenticado === FALSE && !isset($no_mostrar_login)):
   	if ($this->config->item('https_para_login') == TRUE) {
		$url_login = preg_replace('/^http:/', 'https:',
				site_url('usuario/login')); 
	} else {
		$url_login = site_url('usuario/login');
	}
	
	// Módulo de autenticación con formulario?
	$has_form = $this->auth->has_form();
	if (!$has_form) {
		$url_login .= '?devolver=' . urlencode($this->uri->uri_string());
	}
   ?>
   <li><div><a <?php echo ($has_form ? 'rel="#loginOverlay"' : '')?> href="<?php echo $url_login; ?>"> 
     <img src="<?php echo site_url('img/interfaz/login.png')?>" alt="login"
	 width="48" height="48" /><br />
	 Autenticaci&oacute;n</a></div>
   </li>
   <?php
   endif;
   if ($autenticado):
   ?>
   <li><div><a href="<?php echo site_url('ficheros/propios'); ?>">
     <img src="<?php echo site_url('img/interfaz/misficheros.png')?>" alt="mis ficheros" /><br />
	 Mis ficheros</a></div>
   </li>
   <li><div><a href="<?php echo site_url('usuario/salir'); ?>">
     <img src="<?php echo site_url('img/interfaz/salir.png')?>" alt="salir"
	 width="48" height="48"/><br />
	 Salir</a></div>
   </li>
   <?php
   endif;
   ?>
  </ul>
 </div> <!-- menu -->
 <?php
 endif; // $no_mostrar_menu
 ?>

</div> <!-- cabecera -->


<div id="contenido">
<?php
 if (!$autenticado && !isset($no_mostrar_aviso)) {
	 $this->load->view('aviso-no-autenticado');
 }

 // Ventanita flotante de login
 if ($autenticado === FALSE && !isset($no_mostrar_login)
		 && $has_form):
 ?>

 <div id="loginOverlay">

	 <?php
	 $this->load->helper('url');
	 $data = array(
			 'devolver_a' => uri_string(),
			 'url_login' => $url_login,
	 );
	 $this->load->view("form-login", $data);
	 ?>
 </div>
 <?php
  endif;

 // Mensaje de actualización, envío, etc
 $msj = $this->session->flashdata('mensaje_fichero_cabecera');

 if ($msj) {
	echo '<div class="cuadro ok">' . $msj . '</div>';
 }
 ?>

 <div id="cond_usoOverlay">
  <div>
 <?php
 	$this->load->view('condiciones_uso');
 ?>
  </div>
 </div>
