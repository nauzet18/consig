$(document).ready(function() {
	// Comprobación de URLs limpias
	var fila_base = '<tr><td>URLs limpias</td><td style="color: #ffffff; ';
	fila_base += 'background-color: ';

	$.ajax({
		url: url_base + 'ayuda/legal',
		datatype: 'text',
		success: function(d) {
			fila_base += '#00bb00">Reescritura correcta</td><td>OK</td></tr>';
			$("table").append(fila_base);
		},
		error: function() {
			fila_base += '#bb0000">No funciona la reescritura</td><td>Revise su <tt>.htaccess</tt> y las opciones de reescritura</td></tr>';
			$("table").append(fila_base);
		},
	});
});
// vim: sw=2 tabstop=2
