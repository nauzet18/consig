<div id="cuadro_busqueda">
<?php
echo form_open('buscar');
if ($this->uri->segment(1) == '-') {
	$valor = $this->uri->segment(2);
} else {
	$valor = '';
}


$data_casilla = array(
        'name' => 'cadena',
        'value' => $valor,
        'class' => 'textobusqueda',
);
echo anchor('ayuda#busqueda', 
        '<img src="'.site_url('img/interfaz/ayuda_peq.png')
        .'" alt="Ayuda sobre búsqueda" />');
echo form_input($data_casilla);
echo form_submit('Enviar', 'Buscar');
echo form_close();
?>
</div>
