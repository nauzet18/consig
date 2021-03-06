$(document).ready(function() {
	$("a[rel=#loginOverlay]").overlay({
		onBeforeLoad: function() { 
			//this.expose();     
			$("#form_login .usuario").val("");
			$("#form_login .passwd").val("");
			$("#form_login .usuario").focus();
		},                 
		onLoad: function(content, link) {
			$("#form_login .usuario").focus();
		},

		onClose: function(content) {
			//$.unexpose();
		}
	});

	$("a[rel=#cond_usoOverlay]").overlay({
		onBeforeLoad: function() { 
		},                 
		onLoad: function(content, link) {
		},

		onClose: function(content) {
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
		top.location.href = url_base + id;
	});

	// Burbuja con información del fichero
	$("#listado-ficheros tr").cluetip({
		showTitle: false,
		width: '300px',
		cluetipClass: 'jtip',
		ajaxCache: true,
		hoverIntent: {
			sensitivity:  1,
			interval:     100,
			timeout:      100
		}
	});

	// Huevo de pascua
	$("#version_consigna span").click(function() {
		$("#version_consigna span.p").html('<img src="' + 
			url_base + 'img/logos/pistacho.gif" alt="pistachito" />');
	});


});

function pagina_login() {
	$(".usuario").focus();
}

function pagina_envio() {
	// Enrollamos
	$(".enrollable legend").trigger("click");

	$("#form_subida").attr("target", "iframe_upload");

	var accion = $("#form_subida").attr("action");
	$("#form_subida").attr("action", accion + "/desatendido");

	// Previsión de acceso
	$(".opcion").click(function() {
		// Opción
		var esteinput = $(this).children("input");
		var opcion = esteinput.attr('name');
		if (opcion == 'tipoacceso') {
			// Extraemos el valor
			var valor = esteinput.val();
			actualiza_prevision(valor);
		}
	});

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

			if (passwd == "" && (tipoacceso == 1 || user_auth == "")) {
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
				.append('<iframe name="iframe_upload" id="iframe_upload" '
				+'onload="javascript:fin_envio();"></iframe>');
			$("#progreso").html("0%");
			$("#progreso").width("0%");
			$("#progreso_velocidad").html("- kB/s");
			$.blockUI({ 
					message: $("#progreso_overlay"),
					applyPlatformOpacityRules: false, 
					css: { 
						width: '300px'
					}
			});

			$("#progreso").everyTime(3000, 'contador', function() {
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
					}
				});
			}, 0, true);
	});

}

function fin_envio() {
	$("#progreso").stopTime('contador');
	$("#iframe_upload").stop();
	$("#progreso").html("100%");
	$("#progreso").width("100%");
	// Redirigimos al usuario
	var mensaje = $("#iframe_upload").contents().text();
	if (mensaje.match(/^\d+$/)) {
		if (user_auth == '') {
			top.location.href = url_base;
		} else {
			top.location.href = url_base + '/ficheros/propios';
		}
		return; // Evitamos unblockUI
	} else {
		$("#form_subida").before('<div class="cuadro error">' 
			+ mensaje + '</div>');
		var destino_scroll = $(".error").offset().top;
		$('html,body').animate({scrollTop: destino_scroll}, "fast", "swing");
	}
	$.unblockUI();
}

function pagina_descarga(fid) {
	$("#passwd_fichero").focus();
	$("#cuadro_password_fichero").each(function() {
			$(".descarga_fichero").click(function() {
				/*
				$("#cuadro_password_fichero").expose({
					speed: 400,
					opacity: 0.3,
				});
				*/
				$("#passwd_fichero").focus();
			});
	});

	if (activar_antivirus) {
		// Estado de análisis del AV (cada 5s)
		$("#info_av.pendiente").everyTime(5000, 'contador', function() {
			$.ajax({
				url: url_base + "ficheros/estadoav/" + fid,
				cache: false,
				dataType: "text",
				success: function(d) {
					// No sigue pendiente
					if (d != "") {
						$("#info_av").stopTime('contador');
						$("#info_av").replaceWith(d);
					}
				},
				error: function(obj, quepaso, otro) {
					$("#info_av").html("Error desconocido al consultar vía AJAX");
				}
			});
		}, 0, true);
	} // Fin activar_antivirus

	// Histórico de descargas
	$("#historico_detallado").hide();
	$("#ver_historico_detallado").click(function() {
		$("#historico_detallado").toggle('normal');
	});
}


/**
 * Modificación de un fichero. Realiza:
 *
 * - Desactivación de contraseña al estar seleccionado "Eliminar clave"
 * - Si el tipo de acceso es 'público', desactiva el campo "Eliminar clave",
 *   lo pone a 0 y exige una contraseña
 */

function pagina_modificacion() {

	// Estado inicial para tipo público
	if ($("input[name=tipoacceso]:checked").val() == 1) {
			$(".eliminar-passwd").attr("disabled", true);
	}

	// Si se puede cambiar, tipoacceso = privado, vale
	$(".eliminar-passwd").change(function() {
		if ($(this).attr('checked') == true) {
			$(".passwd-fichero").val("");
			$(".passwd-fichero").attr("disabled", true);
		} else {
			$(".passwd-fichero").attr("disabled", false);
		}
	});

	// Cambio de opciones. ¿Es "tipo de acceso"?
	$(".opcion").click(function() {
		// Opción
		var esteinput = $(this).children("input");
		var opcion = esteinput.attr('name');
		if (opcion == 'tipoacceso') {
			// Extraemos el valor
			var valor = esteinput.val();
			if (valor == 0) {
				$(".eliminar-passwd").attr("disabled", false);
			} else
				$(".eliminar-passwd").attr("disabled", true);
				$(".eliminar-passwd").attr("checked", false);
				$(".passwd-fichero").attr("disabled", false);
			}

			// Actualizamos previsión de acceso
			actualiza_prevision(valor);

	});

}

/**
 * Actualiza la previsión de acceso en el caso de usuarios anónimos y
 * desde una IP externa.
 *
 * Si ha cambiado el tipo de acceso significa que el envío es autenticado,
 * por tanto todo depende del valor de tipoacceso
 */
function actualiza_prevision(tipoacceso) {
	if (tipoacceso == 0) {
		$("#prevision-peor-caso img").attr("src",
			url_base + '/img/interfaz/cancelar.png');
	} else {
		$("#prevision-peor-caso img").attr("src",
			url_base + '/img/interfaz/vale.png');
	}
}


// vim: sw=2 tabstop=2
