<h1>Ayuda sobre uso de consigna</h1>

<p>Puede consultar los siguientes asuntos:</p>

<ul>
 <li><a href="#descripcion">1. Descripción</a></li>
 <li>2. Trabajo con ficheros</li>
 <ul>
	 <li><a href="#descarga">2.1. Descarga de un fichero</a></li>
	 <li><a href="#envio">2.2. Envío de un fichero</a></li>
	 <li><a href="#modificacion">2.3. Modificación de un fichero</a></li>
	 <li><a href="#borrado">2.4. Borrado de un fichero</a></li>
 </ul>
</ul>


<h2><a name="descripcion">1. Descripción</a></h2>

<p>El servicio consigna está ideado para el intercambio de ficheros de gran
tamaño. Los ficheros enviados a consigna están disponibles durante un cierto
período de tiempo con algunas restricciones de acceso, entre las que se
encuentran el uso de contraseñas, la limitación a usuarios desde una IP
dentro de la red de la Universidad de Sevilla o la obligación de estar
autenticado como usuario de la misma.</p>

<p>El servicio le permite autenticarse usando su UVUS, teniendo de esta
manera ventajas tales como control de sus propios ficheros (modificación y
borrado) y el acceso a todos los ficheros publicados.</p>


<h2>2. Trabajo con ficheros</h2>

<p>Los listados de ficheros son presentados por defecto ordenados en orden
cronológico inverso, pero puede alterarse este criterio haciendo click sobre
el título de cada columna. La columna que en ese momento esté funcionando
como referencia para ordenar los resultados estará acompañada de un símbolo
gráfico que determinará el criterio de ordenación (ascendente o
descendente).</p>

<img class="centrar_ayuda" 
 src="<?php echo site_url('img/ayuda/ordenacion_ficheros.png');?>"
 alt="Ordenación de los ficheros" />

<p>Además, si la cantidad de ficheros es muy elevada, el listado se paginará
mostrando <?php $this->config->item('resultados_por_pagina')?> resultados en
cada página. Podrá moverse entre las páginas conservando el criterio de
ordenación.</p>

<img class="centrar_ayuda" 
 src="<?php echo site_url('img/ayuda/paginacion_ficheros.png');?>"
 alt="Paginación de los ficheros" />

<h2><a name="descarga">2.1. Descarga de un fichero</a></h2>

<p>En consigna hay dos niveles de acceso: de entrada a la ficha del fichero
y de descarga del fichero. Para poder descargar un fichero es necesario
tener permiso de entrada a su ficha.</p>

<p>El permiso de entrada, por defecto, se basa en la dirección IP del
usuario que accede y en su condición de autenticado en la aplicación. Si un
usuario está autenticado y/o se está conectando desde la red interna de la
Universidad de Sevilla, tiene acceso a la ficha de cualquier envío en
consigna. Si el usuario no está autenticado y está conectando desde una
ubicación distinta a la Universidad de Sevilla, existen dos casos en los que
podrá acceder a un fichero:</p>

<ul>
 <li>Que el fichero fuera enviado por un usuario anónimo desde la red de la
 Universidad de Sevilla</li>

 <li>Que el fichero fuera enviado por un usuario autenticado y que
 especificara al enviar el fichero que sería de acceso público</li>
</ul>

<p>Una vez se ha accedido a la ficha de un envío, éste puede requerir una
contraseña o no, dependiendo de lo que el remitente deseara en el momento
del envío del fichero. En el caso de un envío público por parte de un
usuario autenticado, el uso de contraseña es obligatorio.</p>

<h2><a name="envio">2.2. Envío de un fichero</a></h2>
<p>
A la hora de enviar un fichero se distingue entre un usuario autenticado y
un usuario anónimo. Un usuario autenticado que desee enviar un fichero tiene
más opciones a la hora de enviarlo, situadas en un elemento desplegable
llamado <em>Opciones del envío</em>.
</p>

<img class="centrar_ayuda" 
 src="<?php echo site_url('img/ayuda/opciones-envio.png');?>" alt="Opciones de envio" />

<p>Las opciones al enviar un fichero se citan a continuación, junto a sus
valores por defecto, que son usados además para los envíos anónimos:</p>

<table class="centrar_ayuda">
 <thead>
  <tr>
   <th>Opción</th>
   <th>Descripción</th>
   <th>Valor por defecto/valor para usuarios anónimos</tt>
  </tr>
 </thead>
 <tbody>
  <tr>
   <td>Contraseña de acceso al fichero</td>
   <td>Contraseña que permitirá la descarga final del fichero</td>
   <td>-</td>
  </tr>
  <tr>
   <td>Caducidad</td>
   <td>Tiempo que el fichero estará disponible en la consigna. Pasado ese
   tiempo, será eliminado automáticamente del sistema</td>
   <td><?php echo $this->config->item('expiracion_defecto')?></td>
  </tr>

  <tr>
   <td>Fichero listado en página principal</td>
   <td>En la <a href="<?php echo site_url()?>">página principal de la
   consigna</a> aparece un listado de todos los ficheros enviados. Si no
   desea que su envío aparezca, escoja la opción <em>No</em>. Su fichero
   podrá ser accedido conociendo la URL del mismo.</td>
   <td>Sí</td>
  </tr>

  <tr>
   <td>Fichero accesible sólo por usuarios de la Universidad de Sevilla</td>
   <td>Escoja la opción <em>Sí</em> si desea que su fichero sea accesible
   según las restricciones mencionadas en el punto <a
   href="#descarga">2.1</a>. Si se escoge la opción <em>No</em>, es
   obligatorio el establecimiento de una contraseña para su descarga, ya que
   el fichero será accesible desde cualquier IP y por cualquier usuario,
   esté o no identificado.</td>
   <td>No (anónimos: Sí)</td>
  </tr>

  <tr>
   <td>Asociar fichero a su nombre</td>
   <td>Escoja la opción <em>No</em> si no desea que su fichero quede
   asociado públicamente a su nombre. En la ficha del mismo aparecerá
   <em>Anónimo*</em> en lugar de su nombre real.</td>
   <td>Sí (anónimos : Anónimo)</td>
  </tr>
 </tbody>
</table>

<p>Cuando se proceda a enviar el fichero, aparecerá un indicador del
progreso del envío, incluyendo una estimación de tiempo restante y de la
velocidad de envío.</p>

<img class="centrar_ayuda" 
 src="<?php echo site_url('img/ayuda/envio-progreso.png');?>" alt="Progreso del envío" />

<p>Cuando el envío concluya, si todo ha ido bien, será enviado a la ficha
del envío con un mensaje que le informa del éxito de la operación.</p>

<p>Tenga en cuenta que si ha enviado el fichero de manera anónima y su IP no
pertenece a la Universidad de Sevilla, podrá ver la ficha únicamente en esta
primera ocasión y no en las posteriores.</p>

<h3>Sobre los listados</h3>

<p>Aunque marque un fichero como anónimo o como no mostrado en el listado
principal, podrá verlo listado en el apartado <em>Mis ficheros</em>.</p>

<p>Al lado de todos sus ficheros verá la siguiente simbología:</p>

<table class="centrar_ayuda">
 <thead>
  <tr>
   <th>Símbolo</th>
   <th>Significado</th>
  </tr>
 </thead>
 <tbody>
  <tr>
   <td><img src="<?php echo site_url('img/interfaz/fichero_propio.png')?>"
	   alt="Propio" /></a></td>
   <td>Este fichero fue enviado por usted</td>
  </tr>
  <tr>
   <td><img src="<?php echo site_url('img/interfaz/oculto.png')?>"
	   alt="Oculto" /></a></td>
   <td>Este fichero fue marcado para no ser mostrado en el listado principal</td>
  </tr>
 </tbody>
</table>

<h2><a name="modificacion">2.3. Modificación de un fichero</a></h2>

<p>Puede modificar un fichero siempre que lo enviara estando autenticado con
su cuenta. Accediendo a cualquiera de sus ficheros podrá ver dos opciones
adicionales:</p>

<img class="centrar_ayuda" src="<?php echo
site_url('img/ayuda/opciones-propietario.png')?>" alt="Opciones para el
propietario" />

<p>Las únicas opciones no susceptibles de modificación son el fichero en
sí y el tiempo de expiración. El resto de opciones son modificables de la
misma manera en que se especifican al enviar un fichero.</p>

<p>Puede optar por no cambiar la contraseña (dejando en blanco el recuadro),
	cambiarla por otra o, si lo desea, eliminarla. Para esto marque la
	casilla <em>Dejar fichero sin clave</em>. Si este cuadro está
	desactivado, podrá comprobar que tiene activada la opción de acceso a
	cualquier usuario de fuera de la Universidad de Sevilla.</p>

<img class="centrar_ayuda" src="<?php echo
site_url('img/ayuda/modificar-passwd.png')?>" 
alt="Modificación de la contraseña" />


<h2><a name="borrado">2.4. Borrado de un fichero</a></h2>

<p>Puede eliminar un fichero siempre que lo enviara estando autenticado con
su cuenta. Accediendo a cualquiera de sus ficheros podrá ver dos opciones
adicionales:</p>

<img class="centrar_ayuda" src="<?php echo
site_url('img/ayuda/opciones-propietario.png')?>" alt="Opciones para el
propietario" />

<p>Para borrar un fichero se le pedirá confirmación del borrado. Si confirma
su decisión, el ficheo será eliminado del sistema de consigna.</p>
