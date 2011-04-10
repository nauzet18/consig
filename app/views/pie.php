</div> <!-- contenido -->

<div id="pie">
 <a href="http://www.us.es/servicios/sic">Servicio de Inform&aacute;tica
 y Comunicaciones</a><br />
 <a href="http://www.us.es">Universidad de Sevilla</a><br />

 <a class="dircorreo"
 href='m&#97;ilt&#111;&#58;con&#37;7&#51;i%6&#55;n&#37;6&#49;&#64;u%73&#46;%&#54;&#53;s'>consigna&#64;&#117;&#115;&#46;es</a>

 <p id="version_consigna">
  consigna v<span><?php echo VERSIONCONSIGNA; ?></span> [<a id="sourcecode"
  href="https://labs.us.es/projects/show/consigna">código fuente</a>]
  <br />
   <span class="p"></span>
 </p>
</div>

<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery-1.4.4.min.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery.overlay-1.2.3.pack.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/interfaz.min.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery.hoverIntent.min.js')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js/jquery.cluetip.min.js')?>"></script>

<?php
if (isset($js_adicionales)) {
	foreach ($js_adicionales as $js) {
		echo '<script language="JavaScript" type="text/javascript" src="'
			.site_url('js/'.$js).'"></script>' . "\n";
	}
}
?>

</body>

</html>
