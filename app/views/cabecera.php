<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>
<title>Consigna <?php if (!empty($subtitulo)) { echo "| ".$subtitulo;} ?></title>
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
<script language="JavaScript" type="text/javascript">
//<![CDATA[
var url_base = '<?php echo base_url()?>';
//]]>
</script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery-1.2.6.min.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery.overlay-0.14.pack.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/interfaz.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery.hoverIntent.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery.cluetip.js')?>"></script>

<?php
if (isset($js_adicionales)) {
	foreach ($js_adicionales as $js) {
		echo '<script language="JavaScript" type="text/javascript" src="'
			.site_url('js/'.$js).'"></script>' . "\n";
	}
}
?>
</head>

<body<?php echo isset($body_onload) ? ' onload="javascript:'.$body_onload.';"' : ''?>>
<?php
// ¿Autenticado?
$autenticado = $this->session->userdata('autenticado');
if ($autenticado !== FALSE) {
	echo '<div id="datos_autenticado">';
	echo 'Su identidad en este momento: ';
	echo '<span class="usuario">'.$this->session->userdata('nombre').'</span>';
	echo '</div>';
}
?>
<div id="cabecera">
 <div id="logo">
  <h1><a href="<?php echo site_url()?>">Consigna</a></h1>
  <h2 id="subtitulo">Env&iacute;o y recogida de ficheros</h2>
 </div> <!-- logo -->

 <div id="menu">
  <ul>
   <li class="first"><a href="<?php echo site_url('ficheros/nuevo')?>">
     <img src="<?php echo site_url('img/interfaz/nuevo.png')?>" alt="Nuevo" /><br />
	 Nuevo</a>
   </li>
   <?php
   if ($autenticado === FALSE && !isset($no_mostrar_login)):
   	if ($this->config->item('https_para_login') == TRUE) {
		$url_login = preg_replace('/^http:/', 'https:',
				site_url('usuario/login')); 
	} else {
		$url_login = site_url('usuario/login');
	}
   ?>
   <li><a rel="#loginOverlay" href="<?php echo $url_login; ?>"> 
     <img src="<?php echo site_url('img/interfaz/login.png')?>" alt="login" /><br />
	 Autenticaci&oacute;n</a>
   </li>
   <?php
   endif;
   if ($autenticado):
   ?>
   <li><a href="<?php echo site_url('ficheros/propios'); ?>">
     <img src="<?php echo site_url('img/interfaz/misficheros.png')?>" alt="mis ficheros" /><br />
	 Mis ficheros</a>
   </li>
   <li><a href="<?php echo site_url('usuario/salir'); ?>">
     <img src="<?php echo site_url('img/interfaz/salir.png')?>" alt="salir" /><br />
	 Salir</a>
   </li>
   <?php
   endif;
   ?>
  </ul>
 </div> <!-- menu -->

</div> <!-- cabecera -->


<div id="contenido">
<?php
 if (!$autenticado && !isset($no_mostrar_aviso)):
 ?>
 <div id="aviso">
  Puede utilizar su nombre de usuario (UVUS) y contraseña de la
  Universidad de Sevilla para tener acceso a todos los ficheros, además
  de poder configurar y controlar sus envíos
 </div>
 <?php
 endif;


 // Ventanita flotante de login
 if ($autenticado === FALSE && !isset($no_mostrar_login)):
 ?>

 <div id="loginOverlay"
	 style="background-image:url(<?php echo
	 site_url('img/fondos/white.png')?>);">

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
