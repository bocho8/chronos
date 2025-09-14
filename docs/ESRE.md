










S.I.G.I.E
Chronos
E.S.R.E


Rol
Apellido
Nombre
Cédula Identidad
Email
Tel./Cel.
Coordinador
Chapuis
Juan
5.630.283-4
jchapuis@scuolaitaliana.edu.uy
098844043
Sub-Coordinador
Roizen
Agustin
6.339.592-9
aroizen@scuolaitaliana.edu.uy
097259510
Integrante
Marino
Bruno
5.707.620-6
bmarino@scuolaitaliana.edu.uy
092617596



Fecha de entrega: 28/07/2025

Primera Entrega








1. Propósito y Alcance del Software	3
2. Perspectiva del Producto	3
3. Funciones Específicas	4
3.1. Funciones Esperables	4
3.2. Funciones Futuras	4
4. Requerimientos Funcionales	4
4.1. Gestión de Horarios	4
4.2. Gestión de Docentes y Disponibilidad	5
4.3. Administración de Usuarios y Permisos	7
4.4. Interfaz de Usuario	8
4.4.1 Requerimientos Generales de la Interfaz	8
4.4.2 Requerimientos de Responsividad y Accesibilidad	8
4.4.3 Requerimientos de Navegación y Menús	9
4.4.4 Requerimientos de Formularios y Validación	9
4.4.5 Requerimientos de Tablas y Visualización de Datos	9
4.4.6 Requerimientos de Mensajes y Feedback	9
4.4.7 Requerimientos Específicos por Rol en la Interfaz	10
4.4.8 Requerimientos de Gestión de Horarios en la Interfaz	10
4.4.9 Requerimientos de Acceso y Autenticación	10
5. Requerimientos No Funcionales	11
5.1. Rendimiento	11
5.2. Usabilidad	11
5.3. Fiabilidad	11
5.4. Seguridad	11
5.5. Mantenibilidad	12
5.6. Restricciones	12
6. Interfaces Externas	13
6.1. Interfaces de Usuario	13
6.2. Interfaces de Software	13
6.3. Interfaces de Comunicación	13
6.4. Interfaces de Hardware	13
7. Supuestos y Dependencias	14
7.1. Supuestos	14
7.2. Dependencias	14
8. Verificación de Requisitos	14
9. Apéndices/Anexos	14
Glosario	14




1. Propósito y Alcance del Software
El presente documento, la Especificación de Requerimientos de Software (ESRE), tiene como propósito describir de manera clara, precisa y verificable todas las funciones y restricciones del sistema web de gestión de horarios escolares antes de que comience su programación o diseño. Este documento funcionará como un contrato de software y servirá como base para las fases de diseño, desarrollo, prueba y mantenimiento, permitiendo minimizar errores de interpretación y evitando costos futuros.

El alcance de este software se centra en proveer una plataforma web para la gestión integral de horarios docentes y restricciones educativas dentro de una institución escolar. Incluirá la generación automática de horarios basada en un algoritmo de restricciones, la edición manual de los horarios generados, y la visualización de los mismos para diferentes perfiles de usuario. El sistema busca optimizar el proceso de creación de horarios, facilitar el seguimiento de las funcionalidades y adaptarse a las necesidades de disponibilidad docente y las pautas educativas específicas.
2. Perspectiva del Producto
El sistema es una aplicación web independiente diseñada para gestionar y optimizar la asignación de franjas horarias a docentes y asignaturas en el Liceo Italiano, considerando restricciones como disponibilidad docente, asignaturas impartidas y horas semanales requeridas. Operará como una herramienta central para la coordinación académica, integrándose con procesos existentes como la recolección de aspiraciones horarias de docentes y la asignación de grupos y materias por la dirección. Su interfaz facilitará la interacción de administradores, dirección, coordinadores, docentes y padres.
3. Funciones Específicas
3.1. Funciones Esperables
Esta sección describe brevemente las capacidades actuales del sistema, detalladas como requerimientos funcionales en la sección 4.
Gestión de Horarios: Generación automática y edición manual de horarios basados en restricciones.
Gestión de Docentes y Disponibilidad: Registro y visualización de aspiraciones horarias de docentes.
Gestión de Usuarios y Roles: Soporte para roles con permisos específicos.
Interfaz de Usuario: Acceso y manipulación de datos para todos los roles.
3.2. Funciones Futuras
El sistema debe permitir la exportación de horarios en formatos PDF y Excel para distribución externa.
El sistema debe integrar notificaciones por correo electrónico para informar a docentes y padres sobre cambios en los horarios.
4. Requerimientos Funcionales
4.1. Gestión de Horarios
El sistema debe generar automáticamente horarios para todos los grupos y docentes basado en las restricciones de disponibilidad docente y las asignaturas impartidas.
El sistema debe aplicar las reglas de distribución de horas al generar los horarios (por ejemplo, repartir clases en 2, 3 o 4 días según la carga horaria).
El sistema debe permitir al coordinador editar manualmente los horarios generados.
El sistema debe permitir al usuario seleccionar, al crear o modificar una materia, cuál de las pautas de inspección específicas de ANEP aplica.
El sistema debe permitir al usuario seleccionar si una materia se impartirá en conjunto con otra.
El sistema debe solicitar al usuario el grupo con el que se compartirá la materia cuando se seleccione que se impartirá en conjunto.
El sistema debe permitir a usuarios autorizados (coordinador, administrador) solicitar la generación de horarios.
El sistema debe generar automáticamente horarios al inicio de cada semestre o período académico, según la configuración establecida.
El sistema debe permitir a usuarios autorizados consultar (ver) los horarios generados.
El sistema debe permitir a usuarios autorizados eliminar horarios obsoletos o incorrectos.
4.2. Gestión de Docentes y Disponibilidad
El sistema debe permitir al docente registrar su nombre completo.
El sistema debe permitir al docente registrar las asignaturas que imparte.
El sistema debe permitir al docente registrar su número de teléfono celular.
El sistema debe permitir al docente registrar sus franjas horarias disponibles para cada día de lunes a viernes.
El sistema debe permitir al docente indicar si trabaja en otros liceos.
El sistema debe permitir al docente registrar el nombre de cada liceo en el que trabaja, si corresponde.
El sistema debe registrar automáticamente la fecha de envío de las aspiraciones horarias de cada docente.
El sistema debe calcular el porcentaje de margen de cada docente como la relación entre horas disponibles y horas asignadas.
El sistema debe mostrar el porcentaje de margen de cada docente.


El sistema debe permitir filtrar la lista de docentes según el número de observaciones registradas.
El sistema debe permitir ordenar la lista de docentes según su prioridad, determinada por si trabaja en otro liceo o por su porcentaje de margen.
El sistema debe permitir al docente seleccionar observaciones predefinidas sobre su disponibilidad.
El sistema debe permitir al docente describir el motivo de sus observaciones.
El sistema debe mostrar simultáneamente, para cada docente, el porcentaje de margen, las observaciones y los motivos registrados.
El sistema debe permitir visualizar la carga horaria total de todas las materias de un docente seleccionado.
El sistema debe permitir visualizar, para cada grupo, los docentes asignados, las materias que imparten y la carga horaria correspondiente.
El sistema debe permitir a usuarios autorizados modificar los datos de un docente.
El sistema debe permitir a usuarios autorizados eliminar registros de docentes cuando sea necesario.
El sistema debe permitir al docente visualizar su disponibilidad horaria sin la posibilidad de gestionarla directamente, si así está definido en su rol.
El sistema debe incluir siempre una observación predefinida llamada “Otro”, que permita al docente especificar texto libre al seleccionarla.
El sistema debe incluir siempre una observación predefinida llamada “Otro Liceo”, que permita al docente especificar el nombre de los liceos correspondientes al seleccionarla.
El sistema debe permitir al rol Coordinador crear nuevas observaciones predefinidas.
El sistema debe permitir al rol Coordinador leer el listado completo de observaciones predefinidas.


El sistema debe permitir al rol Coordinador actualizar el texto de observaciones predefinidas existentes.
El sistema debe permitir al rol Coordinador eliminar observaciones predefinidas, excepto las observaciones “Otro” y “Otro Liceo”.
El sistema debe permitir al rol Coordinador Actualizar el texto de observaciones existentes
El sistema debe permitir al rol Coordinador Eliminar observaciones predefinidas (excepto "Otro" y "Otro Liceo")
4.3. Administración de Usuarios y Permisos
El sistema debe soportar los roles de usuario: Administrador, Dirección, Coordinador, Docente y Padre.
El sistema debe permitir al rol Dirección añadir nuevos docentes.
El sistema debe permitir al rol Dirección añadir nuevas materias.
El sistema debe permitir al rol Dirección publicar los horarios generados.
El sistema debe permitir al rol Coordinador generar horarios.
El sistema debe permitir al rol Coordinador editar horarios.
El sistema debe permitir al rol Coordinador gestionar la disponibilidad docente.
El sistema debe asignar roles a los usuarios según su autenticación al ingresar.
El sistema debe permitir al rol Docente gestionar su propia disponibilidad horaria.
El sistema debe permitir al rol Docente visualizar los horarios publicados.
El sistema debe permitir al rol Padre visualizar los horarios publicados únicamente para sus hijos.
El sistema debe permitir al rol Coordinador visualizar la asignación de grupos a docentes y las materias que imparten.
El sistema debe permitir al rol Coordinador editar la asignación de grupos a docentes y las materias que imparten.
El sistema debe impedir al rol Coordinador crear nuevas asignaciones de grupos a docentes o materias.
El sistema debe registrar constancias de trabajo en otra institución para docentes con más de 16 horas asignadas.
El sistema debe registrar constancias de actividades justificadas para docentes con más de 16 horas asignadas.
El sistema debe permitir únicamente al rol Dirección publicar los horarios generados.
El sistema debe impedir al rol Coordinador publicar horarios.
El sistema debe permitir únicamente al rol Dirección añadir nuevos docentes.
El sistema debe impedir al rol Coordinador añadir nuevos docentes.
El sistema debe permitir únicamente al rol Dirección añadir nuevas materias.
El sistema debe impedir al rol Coordinador añadir nuevas materias.
4.4. Interfaz de Usuario
La interfaz de usuario del sistema debe diseñarse para garantizar una experiencia intuitiva, eficiente y accesible para todos los roles de usuario. A continuación, se detallan los requerimientos funcionales específicos para la interfaz, organizados en categorías lógicas.
4.4.1 Requerimientos Generales de la Interfaz
El sistema debe proporcionar una interfaz web accesible desde navegadores modernos (Chrome, Firefox, Safari, Edge en sus últimas dos versiones).
El sistema debe mostrar todos los textos en el idioma seleccionado.
El sistema debe mantener un diseño visual consistente (colores, fuentes, iconos) en todas las pantallas.
El sistema debe cerrar automáticamente la sesión del usuario después de 30 minutos de inactividad.
El sistema debe mostrar un indicador de carga (spinner o barra de progreso) cuando una operación tarda más de 500 milisegundos.
4.4.2 Requerimientos de Responsividad y Accesibilidad
El sistema debe permitir el acceso a la interfaz desde dispositivos de escritorio y móviles.
El sistema debe adaptar automáticamente el diseño de la interfaz al tamaño de pantalla del dispositivo (diseño responsive).
El sistema debe ser usable en dispositivos móviles sin necesidad de zoom o scroll horizontal para contenido esencial.
El sistema debe cumplir con los estándares de accesibilidad WCAG 2.1 nivel AA para usuarios con discapacidades (ej. contraste adecuado, etiquetas ARIA).
4.4.3 Requerimientos de Navegación y Menús
El sistema debe permitir la navegación entre secciones mediante un menú principal lateral o superior.
El sistema debe mostrar menús y opciones según el rol autenticado del usuario, ocultando funciones no autorizadas.
El sistema debe proporcionar una ruta de navegación (breadcrumbs) en páginas con múltiples niveles.
El sistema debe incluir un botón de "Inicio" o "Dashboard" para volver a la página principal desde cualquier sección.
4.4.4 Requerimientos de Formularios y Validación
El sistema debe validar los datos ingresados en formularios (ej. campos obligatorios, formatos de email, teléfono) antes de enviarlos al servidor.
El sistema debe mostrar mensajes de error específicos y contextuales cuando falla una validación de formulario.
El sistema debe previsualizar los datos seleccionados en campos de elección múltiple (ej. materias, docentes).
El sistema debe permitir la cancelación de operaciones de formulario con un botón "Cancelar" que limpie los campos.
4.4.5 Requerimientos de Tablas y Visualización de Datos
El sistema debe presentar tablas de datos con opciones de filtrado por criterios definidos (ej. por nombre, fecha, estado).
El sistema debe permitir aplicar múltiples filtros simultáneamente en las tablas.
El sistema debe permitir ordenar tablas de datos en forma ascendente o descendente según las columnas seleccionadas.
El sistema debe implementar paginación en tablas con más de 10 registros, mostrando opciones de 10, 25, 50 registros por página.
El sistema debe proporcionar una función de búsqueda textual en tiempo real para listas largas de datos.
El sistema debe mostrar resúmenes o totales en la parte inferior de las tablas cuando sea aplicable (ej. total de horas).
4.4.6 Requerimientos de Mensajes y Feedback
El sistema debe mostrar mensajes de confirmación mediante modales antes de ejecutar operaciones críticas (ej. eliminación de registros).
El sistema debe mostrar mensajes de éxito (verde) o error (rojo) después de operaciones de creación, edición o eliminación de registros, con detalles claros.
El sistema debe proporcionar tooltips o información emergente para iconos y botones ambiguos.
El sistema debe notificar al usuario mediante una alerta suave cuando ocurra un evento importante (ej. horario publicado).
4.4.7 Requerimientos Específicos por Rol en la Interfaz
El sistema debe mostrar al rol Administrador opciones para crear, leer, actualizar y eliminar registros de docentes, materias y horarios en el menú.
El sistema debe mostrar al rol Dirección opciones para añadir nuevos docentes, añadir nuevas materias y publicar horarios en el menú.
El sistema debe ocultar al rol Coordinador las opciones de añadir docentes, añadir materias y publicar horarios en el menú.
El sistema debe mostrar al rol Coordinador opciones para generar horarios, editar horarios y gestionar disponibilidad docente en el menú.
El sistema debe mostrar al rol Docente opciones para gestionar su disponibilidad horaria y visualizar horarios publicados en el menú.
El sistema debe mostrar al rol Padre opciones para visualizar horarios publicados de sus hijos en el menú.
El sistema debe adaptar el dashboard inicial para cada rol, mostrando solo la información relevante (ej. para Docente: su horario y disponibilidad).
4.4.8 Requerimientos de Gestión de Horarios en la Interfaz
El sistema debe presentar los horarios en un formato tabular claro y legible con días de la semana y franjas horarias.
El sistema debe permitir la visualización de horarios por grupo, por docente o por grupo mediante pestañas o selectores.
El sistema debe permitir la impresión de horarios en formato optimizado para papel.
El sistema debe resaltar visualmente los conflictos de horario (ej. superposiciones) en rojo durante la edición.
El sistema debe proporcionar una vista de calendario semanal para la edición manual de horarios.
4.4.9 Requerimientos de Acceso y Autenticación
El sistema debe proporcionar una página de login con campos de usuario y contraseña, y un botón "Iniciar sesión".
El sistema debe permitir la recuperación de contraseña mediante un enlace "¿Olvidó su contraseña?" que envíe un email de reset.
El sistema debe mostrar un mensaje de error genérico (ej. "Credenciales incorrectas") en el login para evitar ataques de fuerza bruta.


5. Requerimientos No Funcionales
5.1. Rendimiento
El sistema debe generar un horario completo para 10 grupos y 20 docentes en menos de 5 minutos.
El sistema debe soportar al menos 60 usuarios concurrentes con tiempos de respuesta menores a 2 segundos para operaciones comunes (ej. consultar horarios).
5.2. Usabilidad
El sistema debe permitir a un usuario nuevo registrar su disponibilidad en menos de 5 minutos tras una capacitación de 10 minutos.
El sistema debe presentar los horarios en un formato tabular claro y legible.
5.3. Fiabilidad
El sistema debe estar disponible el 99% del tiempo durante el horario escolar (lunes a viernes, 8:00-17:00).
5.4. Seguridad
El sistema debe requerir autenticación con credenciales únicas (usuario y contraseña) para todos los usuarios.
El sistema debe implementar control de acceso basado en roles, restringiendo funciones y datos según el rol.
El sistema debe registrar todos los accesos y modificaciones críticas en un log auditable.
5.5. Mantenibilidad
El sistema debe permitir la corrección de errores en un módulo sin afectar otros en menos de 2 horas.
El sistema debe incluir documentación interna del código y configuraciones.
5.6. Restricciones
El backend del sistema debe desarrollarse en php:8.3.10-fpm-alpine.
La base de datos del sistema debe utilizar la imagen postgres:16.3-alpine para PostgreSQL.
La herramienta de gestión de la base de datos debe ser dpage/pgadmin4:8.10 para pgAdmin.
El sistema debe utilizar la imagen nginx:1.27.0-alpine para el servidor web Nginx.
El sistema debe utilizar la imagen node:20.15.0-alpine para Node.js en caso de requerir componentes frontend dinámicos o scripts adicionales.
El sistema debe ser desplegado y gestionado utilizando la tecnología Docker, asegurando la compatibilidad con las imágenes especificadas.
El sistema debe cumplir las pautas de inspección de ANEP y la política de asistencia del Liceo Italiano para docentes con más de 16 horas.
El sistema debe repartir las clases en 2 días si la carga horaria de un grupo supera las 2 horas, salvo que se apliquen pautas de inspección específicas definidas por ANEP.
El sistema debe distribuir las clases en 3 días si la carga horaria de un grupo excede las 4 horas, con las mismas excepciones.
El sistema debe cubrir las clases en 4 días si la carga horaria de un grupo sobrepasa las 6 horas, bajo criterios análogos.
El sistema debe situar las materias exclusivas de programas italianos al final del horario en grupos mixtos de II y III media, y I Liceo Italiano.
El sistema debe asignar Educación Física a las horas finales del turno, independientemente del grupo.
6. Interfaces Externas
6.1. Interfaces de Usuario
El sistema debe proporcionar una interfaz web para entrada de datos (ej. disponibilidad), visualización (ej. horarios) y manipulación de entidades (ej. edición de materias).
6.2. Interfaces de Software
El sistema debe interactuar con PostgreSQL para almacenamiento y recuperación de datos.
El sistema debe operar en un entorno Docker.
6.3. Interfaces de Comunicación
El sistema debe usar protocolos HTTP/HTTPS para comunicación cliente-servidor.
6.4. Interfaces de Hardware
El sistema debe operar en la infraestructura de hardware de Docker (servidores, red, almacenamiento).
7. Supuestos y Dependencias
7.1. Supuestos
Los usuarios tendrán acceso a dispositivos con navegador web y conexión a Internet.
La información inicial (disponibilidad, grupos, materias) será completa y oportuna.
Las pautas de inspección de ANEP serán estables y accesibles.
7.2. Dependencias
La generación de horarios depende de la calidad de las restricciones de entrada.
La edición de horarios depende de la usabilidad de la interfaz.
El despliegue depende de la configuración de Docker.

8. Verificación de Requisitos
Cada requerimiento funcional será verificado mediante pruebas de aceptación definidas en un plan de pruebas.
Los requerimientos no funcionales serán validados con métricas (ej. tiempo de respuesta, disponibilidad) durante pruebas de estrés y uso real.
9. Apéndices/Anexos
Glosario
Porcentaje de margen: Relación entre horas disponibles y horas asignadas de un docente.
Observaciones: Comentarios registrados por docentes sobre su disponibilidad o preferencias.
Prioridad: Criterio de ordenación basado en trabajo en otro liceo o porcentaje de margen.
Pautas de inspección específicas: Reglas de ANEP para la distribución de clases (ver enlace proporcionado).

