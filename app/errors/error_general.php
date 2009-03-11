<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>
<title>Consigna | Error</title>
<link rel="stylesheet" href="<?php echo site_url('css/estilo.css')?>" type="text/css" media="screen"
/>

</head>

<body>

<div id="cabecera">
 <div id="logo">
  <h1><a href="<?php echo site_url()?>">Consigna</a></h1>
  <h2 id="subtitulo">Env&iacute;o y recogida de ficheros</h2>
 </div> <!-- logo -->
</div>

<div id="contenido">
		<h1><?php echo $heading; ?></h1>
		<div class="error">
		<?php echo $message; ?>
		</div>
</div>


</body>

