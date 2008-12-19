$(document).ready(function() {
	$("a[@rel=#loginOverlay]").overlay({
		onBeforeLoad: function() { 
			this.expose();     
			$("#form_login .usuario").val("");
			$("#form_login .passwd").val("");
			$("#form_login .usuario").focus();
		},                 
		onLoad: function(content, link) {
			$("#form_login .usuario").focus();
		},

		onClose: function(content) {
			$.unexpose();
		}
	});

	// Comodidad en la interfaz
	$(".opciones_radio .opcion").click(function() {
		$(this).find("input").attr("checked", "checked");
	});

	// Elementos enrollados/desplegados
	$(".enrollable legend").click(function() {
			$(this).next().slideToggle(function () {
				$(this).parent().toggleClass("desplegado");
			});
	});

	// Click sobre un elemento de una lista de ficheros
	$("#listado-ficheros tr.permitido").click(function() {
		var id = $(this).attr('id').replace(/fichero-/, "");
		top.location.href = url_base + 'ficheros/' + id;
	});

});

function pagina_login() {
	$(".usuario").focus();
}

function pagina_envio() {
	// Enrollamos
	$(".enrollable legend").trigger("click");

	$("#form_subida").attr("target", "iframe_upload");

	$("#form_subida")
		.append('<input type="hidden" name="desatendido" value="1" />');

	$("#form_subida").submit(function() {
			// Comprobación de campos
			$("div.error").remove();

			var fichero = $("input[name=fichero]").val();
			var passwd = $("input[name=fichero_passwd]").val();
			var tipoacceso = $("input[name=tipoacceso]:checked").val();
			var errores = "";

			if (fichero == "") {
				errores += "<p>Debe especificar un fichero</p>";
			}

			if (tipoacceso == 1 && passwd == "") {
				errores += "<p>Dado que el acceso al fichero será público, debe "
				+ "especificar una contraseña para el mismo</p>";
			}

			if (errores != "") {
				$(this).before('<div class="cuadro error">' + errores + '</div>');
				var destino_scroll = $(".error").offset().top;
				$('html,body').animate({scrollTop: destino_scroll}, "fast", "swing");

				return false;
			}


			$("#iframe_upload").remove();
			$("body")
				.append('<iframe name="iframe_upload" id="iframe_upload"></iframe>');
			$("#iframe_upload").attr("onload", "javascript:fin_envio(1);");
			$("#progreso").html("0%");
			$("#progreso").width("0%");
			$("#progreso_velocidad").html("- kB/s");
			$.blockUI({ 
					message: $("#progreso_overlay"),
					applyPlatformOpacityRules: false, 
					css: { 
						width: '300px',
					}
			});

			$("#progreso").everyTime(1000, 'contador', function() {
				$.ajax({
					url: url_base + "ficheros/estado/" + $("#id_envio").val(),
					cache: false,
					dataType: "text",
					success: function(d) {
            if (d != "noimplementado" && d != "nulo") {
              var datos = d.split(";");
              $("#progreso").html(datos[0] + "%");
              $("#progreso").width(datos[0] + "%");
              $("#progreso_velocidad").html(datos[1]);
              $("#progreso_restante").html(datos[2]);
            } else {
              $("#progreso_velocidad").html("- kB/s");
              $("#progreso_restante").html("-");
            }
					},
					error: function(obj, quepaso, otro) {
						$("#progreso_velocidad").html("¿? kB/s");
            $("#progreso_restante").html("desconocido");
					},
				});
			}, 0, true);
			
			// Pulsación de ESC para cancelar
			$(document).keypress(function (e) {
				if (e.which == 0) {
					fin_envio(2);
				}
			});

	});

	// Evitamos que se siga enviando
	$(window).unload(fin_envio);
  $("#progreso_cancelar").click(function() {
    fin_envio(2);
  });
}

/*
 * Argumento tipo:
 *  1: fin del envío, correcto
 *  2: fin del envío por cancelación
 */
function fin_envio(tipo) {
	$("#progreso").stopTime('contador');
	$("#iframe_upload").stop();
	if (tipo == 1) {
		$("#progreso").html("100%");
		$("#progreso").width("100%");
		// Accedemos al resultado mediante
		var mensaje = $("#iframe_upload").contents().text();
		if (mensaje.match(/^\d+$/)) {
			top.location.href = url_base + 'ficheros/' + mensaje;
			return; // Evitamos unblockUI
		} else {
			$("#form_subida").before('<div class="cuadro error">' 
				+ mensaje + '</div>');
		}
  } else if (tipo == 2) {
		// Cancelado. Paramos
		window.stop();
	}
	$.unblockUI();
}

// vim: sw=2 tabstop=2
