# S.I.G.I.E
## Chronos
### E.S.R.E

**Equipo de Desarrollo:**
- **Coordinador:** Chapuis, Juan
- **Sub-Coordinador:** Roizen, Agustin  
- **Integrante:** Marino, Bruno

**Fecha de entrega:** 28/07/2025  
**Primera Entrega**

---

## Índice

1. [Propósito y Alcance del Software](#31-propósito-y-alcance-del-software)
2. [Perspectiva del Producto](#32-perspectiva-del-producto)
3. [Funciones Específicas](#33-funciones-específicas)
4. [Requerimientos Funcionales](#34-requerimientos-funcionales)
5. [Requerimientos No Funcionales](#355-requerimientos-no-funcionales)
6. [Interfaces Externas](#36-interfaces-externas)
7. [Supuestos y Dependencias](#37-supuestos-y-dependencias)
8. [Verificación de Requisitos](#38-verificación-de-requisitos)
9. [Apéndices/Anexos](#39-apéndicesanexos)

---

## 3. Documento de Especificación de Requerimientos (ESRE)

### 3.1 Propósito y Alcance del Software

El presente documento, la Especificación de Requerimientos de Software (ESRE), tiene como propósito describir de manera clara, precisa y verificable todas las funciones y restricciones del sistema web de gestión de horarios escolares antes de que comience su programación o diseño. Este documento funcionará como contrato de software y servirá como base para las fases de diseño, desarrollo, prueba y mantenimiento, permitiendo minimizar errores de interpretación y evitando costos futuros.

El alcance de este software se centra en proveer una plataforma web para la gestión integral de horarios docentes y restricciones educativas dentro de una institución escolar. Incluirá la generación automática de horarios basada en un algoritmo de restricciones, la edición manual de los horarios generados, y la visualización de los mismos para diferentes perfiles de usuario. El sistema busca optimizar el proceso de creación de horarios, facilitar el seguimiento de las funcionalidades y adaptarse a las necesidades de disponibilidad docente y las pautas educativas específicas.

### 3.2 Perspectiva del Producto

El sistema es una aplicación web independiente diseñada para gestionar y optimizar la asignación de franjas horarias a docentes y asignaturas en el Liceo Italiano, considerando restricciones como disponibilidad docente, asignaturas impartidas y horas semanales requeridas. Operará como una herramienta central para la coordinación académica, integrándose con procesos existentes como la recolección de aspiraciones horarias de docentes y la asignación de grupos y materias por la dirección. Su interfaz facilitará la interacción de administradores, dirección, coordinadores, docentes y padres.

### 3.3. Funciones Específicas

#### 3.3.1 Funciones Esperables

Esta sección describe brevemente las capacidades actuales del sistema, detalladas como requerimientos funcionales en la sección 3.4.

- **Gestión de Horarios:** Generación automática y edición manual de horarios basados en restricciones.
- **Gestión de Docentes y Disponibilidad:** Registro y visualización de aspiraciones horarias de docentes.
- **Gestión de Usuarios y Roles:** Soporte para roles con permisos específicos.
- **Interfaz de Usuario:** Acceso y manipulación de datos para todos los roles.

#### 3.3.2. Funciones Futuras

**FF01**

El sistema debe permitir la exportación de horarios en formatos PDF y Excel para distribución externa.

**FF02**

El sistema debe integrar notificaciones por correo electrónico para informar a docentes y padres sobre cambios en los horarios.

**FF03**

El sistema debe implementar autenticación de dos factores (2FA) para roles administrativos.

**FF04**

El sistema debe proporcionar una API REST para integración con sistemas externos.

**FF05**

El sistema debe soportar múltiples instituciones (multi-tenancy) en una sola instalación.

**FF06**

El sistema debe implementar un sistema de backup automático con rotación de 30 días.

**FF07**

El sistema debe proporcionar un dashboard de métricas y estadísticas en tiempo real.

**FF08**

El sistema debe implementar un sistema de auditoría completo para todas las operaciones críticas.

### 3.4. Requerimientos Funcionales

#### 3.4.1 Gestión de Horarios

**RF001**

El sistema debe verificar todos los grupos y docentes considerando las restricciones de disponibilidad docente, las asignaturas impartidas y las reglas de distribución ANEP, garantizando que cada docente tenga asignaciones compatibles con su disponibilidad registrada.

**RF002**

El sistema debe aplicar las reglas de distribución de horas al generar los horarios, distribuyendo las materias con 1-2 horas semanales en máximo 2 días, las materias con 3-4 horas semanales en máximo 3 días, y las materias con 5 o más horas semanales en máximo 4 días, registrando cada asignación con su justificación correspondiente.

**RF003**

El sistema debe permitir al coordinador editar manualmente los horarios generados mediante una interfaz de calendario semanal.

**RF004**

El sistema debe permitir al usuario seleccionar, al crear o modificar una materia, cuál de las pautas de inspección específicas de ANEP aplica desde una lista predefinida.

**RF005**

El sistema debe permitir al usuario seleccionar si una materia se impartirá en conjunto con otra mediante un campo booleano.

**RF006**

El sistema debe solicitar al usuario el grupo con el que se compartirá la materia cuando se seleccione que se impartirá en conjunto.

**RF007**

El sistema debe permitir a usuarios autorizados (coordinador, administrador) crear los horarios mediante un botón en la interfaz.

**RF009**

El sistema debe permitir a usuarios autorizados consultar (ver) los horarios en formato tabular.

**RF010**

El sistema debe permitir a usuarios autorizados eliminar horarios obsoletos o incorrectos previa confirmación.

**RF011**

El sistema debe detectar automáticamente conflictos de horario (docente doble asignado) y mostrarlos con códigos de color (rojo: crítico, amarillo: advertencia).

**RF012**

El sistema debe permitir resolución manual de conflictos de horarios.

#### 3.4.2 Gestión de Docentes y Disponibilidad

**RF016**

El sistema debe permitir al docente registrar las asignaturas que imparte seleccionando de una lista predefinida.

**RF018**

El sistema debe permitir al docente registrar sus franjas horarias disponibles para cada día de lunes a viernes mediante un calendario interactivo.

**RF019**

El sistema debe permitir al docente indicar si trabaja en otros liceos mediante un campo booleano.

**RF020**

El sistema debe permitir al docente registrar el nombre de cada liceo en el que trabaja cuando la opción anterior esté marcada como verdadera.

**RF021**

El sistema debe registrar automáticamente la fecha de envío de las aspiraciones horarias de cada docente con timestamp preciso.

**RF022**

El sistema debe calcular el porcentaje de margen de cada docente como la relación entre horas disponibles y horas asignadas, mostrando el resultado con dos decimales.

**RF023**

El sistema debe mostrar el porcentaje de margen de cada docente en la lista de docentes.

**RF024**

El sistema debe permitir filtrar la lista de docentes según el número de observaciones registradas (0, 1-2, 3-5, 6+).

**RF025**

El sistema debe permitir ordenar la lista de docentes según su prioridad, determinada por si trabaja en otro liceo o por su porcentaje de margen.

**RF026**

El sistema debe permitir al docente seleccionar observaciones predefinidas sobre su disponibilidad desde una lista desplegable.

**RF027**

El sistema debe permitir al docente describir el motivo de sus observaciones en un campo de texto libre de máximo 500 caracteres.

**RF028**

El sistema debe mostrar simultáneamente, para cada docente, el porcentaje de margen, las observaciones y los motivos registrados en una vista consolidada.

**RF029**

El sistema debe permitir visualizar la carga horaria total de todas las materias de un docente seleccionado en horas.

**RF030**

El sistema debe permitir visualizar, para cada grupo, los docentes asignados, las materias que imparten y la carga horaria correspondiente en formato tabular.

**RF031**

El sistema debe permitir a usuarios autorizados modificar los datos de un docente mediante formularios de edición.

**RF033**

El sistema debe permitir al docente visualizar su disponibilidad horaria sin la posibilidad de gestionarla directamente, si así está definido en su rol.

**RF034**

El sistema debe incluir siempre una observación predefinida llamada "Otro", que permita al docente especificar texto libre al seleccionarla.

**RF035**

El sistema debe incluir siempre una observación predefinida llamada "Otro Liceo", que permita al docente especificar el nombre de los liceos correspondientes al seleccionarla.

**RF036**

El sistema debe permitir al rol Coordinador crear nuevas observaciones predefinidas mediante un formulario de gestión.

**RF037**

El sistema debe permitir al rol Coordinador leer el listado completo de observaciones predefinidas en formato tabular.

**RF038**

El sistema debe permitir al rol Coordinador actualizar el texto de observaciones predefinidas existentes mediante formularios de edición.

**RF039**

El sistema debe permitir al rol Coordinador eliminar observaciones predefinidas, excepto las observaciones "Otro" y "Otro Liceo", previa confirmación.

**RF040**

El sistema debe registrar constancias de trabajo en otra institución para docentes con más de 16 horas asignadas, generando un documento PDF con la información del docente.

**RF041**

El sistema debe registrar constancias de actividades justificadas para docentes con más de 16 horas asignadas, generando un documento PDF con la información del docente.

#### 3.4.3 Administración de Usuarios y Permisos

**RF042**

El sistema debe soportar los roles de usuario: Administrador, Dirección, Coordinador, Docente y Padre.

**RF043**

El sistema debe permitir al rol Dirección añadir nuevos docentes mediante un formulario de registro.

**RF044**

El sistema debe permitir al rol Dirección añadir nuevas materias mediante un formulario de registro.

**RF045**

El sistema debe permitir al rol Dirección publicar los horarios generados mediante un botón de confirmación.

**RF047**

El sistema debe permitir al rol Coordinador editar horarios mediante una interfaz de calendario semanal.

**RF048**

El sistema debe permitir al rol Coordinador gestionar la disponibilidad docente mediante formularios de edición.

**RF049**

El sistema debe asignar roles a los usuarios según su autenticación al ingresar, validando las credenciales contra la base de datos.

**RF050**

El sistema debe permitir al rol Docente gestionar su propia disponibilidad horaria mediante un calendario interactivo.

**RF051**

El sistema debe permitir al rol Docente visualizar los horarios publicados en formato tabular.

**RF052**

El sistema debe permitir al rol Padre visualizar los horarios publicados únicamente para sus hijos mediante un selector de estudiante.

**RF053**

El sistema debe permitir al rol Coordinador visualizar la asignación de grupos a docentes y las materias que imparten en formato tabular.

**RF054**

El sistema debe permitir al rol Coordinador editar la asignación de grupos a docentes y las materias que imparten mediante formularios de edición.

**RF055**

El sistema debe impedir al rol Coordinador crear nuevas asignaciones de grupos a docentes o materias, ocultando los botones correspondientes.

**RF057**

El sistema debe impedir al rol Coordinador publicar horarios, mostrando un mensaje de error si intenta hacerlo.

**RF060**

El sistema debe permitir únicamente al rol Dirección añadir nuevas materias, validando el rol antes de mostrar el formulario.

**RF061**

El sistema debe impedir al rol Coordinador añadir nuevas materias, ocultando la opción del menú.

#### 3.4.4 Interfaz de Usuario

##### 3.4.4.1 Requerimientos Generales de la Interfaz

**RF062**

El sistema debe proporcionar una interfaz web accesible desde navegadores Chrome 90+, Firefox 88+, Safari 14+, y Edge 90+.

**RF063**

El sistema debe mostrar todos los textos en el idioma seleccionado (español, inglés, italiano).

**RF064**

El sistema debe mantener un diseño visual consistente (colores, fuentes, iconos) en todas las pantallas utilizando un sistema de diseño unificado.

**RF065**

El sistema debe cerrar automáticamente la sesión del usuario después de 30 minutos de inactividad, mostrando una alerta 5 minutos antes.

##### 3.4.4.2 Requerimientos de Responsividad y Accesibilidad

**RF067**

El sistema debe permitir el acceso a la interfaz desde dispositivos de escritorio (1024px+) y móviles (320px-768px).

**RF068**

El sistema debe adaptar automáticamente el diseño de la interfaz al tamaño de pantalla del dispositivo (diseño responsive) utilizando CSS Grid y Flexbox.

**RF069**

El sistema debe ser usable en dispositivos móviles sin necesidad de zoom o scroll horizontal para contenido esencial.

**RF070**

El sistema debe cumplir con los estándares de accesibilidad WCAG 2.1 nivel AA para usuarios con discapacidades, incluyendo contraste mínimo 4.5:1, etiquetas ARIA, y navegación por teclado.

##### 3.4.4.3 Requerimientos de Navegación y Menús

**RF071**

El sistema debe permitir la navegación entre secciones mediante un menú principal lateral o superior.

**RF072**

El sistema debe mostrar menús y opciones según el rol autenticado del usuario, ocultando funciones no autorizadas.

**RF073**

El sistema debe proporcionar una ruta de navegación (breadcrumbs) en páginas con múltiples niveles, mostrando la jerarquía completa.

**RF074**

El sistema debe incluir un botón de "Inicio" o "Dashboard" para volver a la página principal desde cualquier sección.

##### 3.4.4.4 Requerimientos de Formularios y Validación

**RF075**

El sistema debe validar los datos ingresados en formularios (campos obligatorios, formatos de email, teléfono) antes de enviarlos al servidor.

**RF076**

El sistema debe mostrar mensajes de error específicos y contextuales cuando falla una validación de formulario, indicando el campo exacto y la corrección necesaria.

**RF077**

El sistema debe previsualizar los datos seleccionados en campos de elección múltiple (materias, docentes) en una lista desplegable.

**RF078**

El sistema debe permitir la cancelación de operaciones de formulario con un botón "Cancelar" que limpie los campos y regrese a la vista anterior.

##### 3.4.4.5 Requerimientos de Tablas y Visualización de Datos

**RF080**

El sistema debe permitir aplicar múltiples filtros simultáneamente en las tablas, mostrando el número de resultados filtrados.

**RF082**

El sistema debe implementar paginación en tablas con más de 10 registros, mostrando opciones de 10, 25, 50 registros por página.

**RF083**

El sistema debe mostrar resúmenes o totales en la parte inferior de las tablas cuando sea aplicable (total de horas, promedio de carga).

##### 3.4.4.6 Requerimientos de Mensajes y Feedback

**RF085**

El sistema debe mostrar mensajes de confirmación mediante modales antes de ejecutar operaciones críticas (eliminación de registros), con botones "Confirmar" y "Cancelar".

**RF086**

El sistema debe mostrar mensajes de éxito (verde) o error (rojo) después de operaciones de creación, edición o eliminación de registros, con detalles claros y auto-ocultación después de 5 segundos.

##### 3.4.4.7 Requerimientos Específicos por Rol en la Interfaz

**RF089**

El sistema debe mostrar al rol Administrador opciones para crear, leer, actualizar y eliminar registros de docentes, materias y horarios en el menú principal.

**RF090**

El sistema debe mostrar al rol Dirección opciones para añadir nuevos docentes, añadir nuevas materias y publicar horarios en el menú principal.

**RF091**

El sistema debe ocultar al rol Coordinador las opciones de añadir docentes, añadir materias y publicar horarios en el menú principal.

**RF092**

El sistema debe mostrar al rol Coordinador opciones para generar horarios, editar horarios y gestionar disponibilidad docente en el menú principal.

**RF093**

El sistema debe mostrar al rol Docente opciones para gestionar su disponibilidad horaria y visualizar horarios publicados en el menú principal.

**RF094**

El sistema debe mostrar al rol Padre opciones para visualizar horarios publicados de sus hijos en el menú principal.

**RF095**

El sistema debe adaptar el dashboard inicial para cada rol, mostrando solo la información relevante (para Docente: su horario y disponibilidad; para Coordinador: estadísticas de generación).

##### 3.4.4.8 Requerimientos de Gestión de Horarios en la Interfaz

**RF096**

El sistema debe presentar los horarios en una tabla con columnas para días de la semana (Lunes a Viernes) y filas para bloques horarios, utilizando fuente Arial 12pt, contraste mínimo 4.5:1, y celdas con dimensiones mínimas de 120px de ancho y 40px de alto.

**RF097**

El sistema debe permitir la visualización de horarios por grupo, por docente o por materia mediante pestañas o selectores.

**RF098**

El sistema debe permitir la impresión de horarios en formato optimizado para papel A4, con márgenes de 1cm.

**RF099**

El sistema debe resaltar visualmente los conflictos de horario (superposiciones) en rojo durante la edición, con tooltip explicativo.

**RF100**

El sistema debe proporcionar una vista de calendario semanal para la edición manual de horarios, con arrastrar y soltar.

##### 3.4.4.9 Requerimientos de Acceso y Autenticación

**RF101**

El sistema debe proporcionar una página de login con campos de usuario y contraseña, y un botón "Iniciar sesión".

**RF102**

El sistema debe mostrar un mensaje de error genérico ("Credenciales incorrectas") en el login para evitar ataques de fuerza bruta.

### 3.5.5 Requerimientos No Funcionales

**RNF001**

El sistema debe generar un horario completo para 10 grupos y 20 docentes en menos de 5 minutos, utilizando un algoritmo de optimización eficiente.

**RNF002**

El sistema debe soportar al menos 100 usuarios concurrentes con tiempos de respuesta menores a 2 segundos para operaciones comunes (consultar horarios).

**RNF003**

El sistema debe cargar cualquier página en menos de 2 segundos en conexión de 10 Mbps.

**RNF004**

El sistema debe realizar búsquedas de docentes en menos de 500 ms para 1000 registros.

**RNF005**

El sistema debe exportar horarios en PDF en menos de 30 segundos para horario completo.

**RNF006**

El sistema debe permitir a un usuario nuevo registrar su disponibilidad en menos de 5 minutos tras una capacitación de 10 minutos.

**RNF007**

El sistema debe presentar los horarios en una tabla con columnas para días de la semana (Lunes a Viernes) y filas para bloques horarios, utilizando fuente Arial 12pt, contraste mínimo 4.5:1, y celdas con dimensiones mínimas de 120 px de ancho y 40 px de alto.

**RNF008**

El sistema debe permitir navegación completa por teclado (Tab, Enter, Escape) en todas las funcionalidades.

**RNF009**

El sistema debe soportar zoom hasta 200 % sin pérdida de funcionalidad.

**RNF010**

El sistema debe mantener una disponibilidad del 99 % del tiempo durante el horario escolar (lunes a viernes, 8:00–17:00), calculada mensualmente, con un tiempo de inactividad máximo de 3.6 horas por mes durante el período especificado.

**RNF011**

El sistema debe implementar backup automático diario con retención de 30 días.

**RNF012**

El sistema debe recuperarse automáticamente de fallos de conexión a base de datos en menos de 30 segundos.

**RNF013**

El sistema debe requerir autenticación con credenciales únicas (usuario y contraseña) para todos los usuarios, con encriptación de contraseñas usando bcrypt.

**RNF014**

El sistema debe implementar control de acceso basado en roles, restringiendo funciones y datos según el rol, validando permisos en cada operación.

**RNF015**

El sistema debe registrar todos los accesos y modificaciones críticas en un log auditable con timestamp, usuario, acción y resultado.

**RNF016**

El sistema debe implementar rate limiting de máximo 5 intentos de login por minuto por IP.

**RNF017**

El sistema debe encriptar datos sensibles (contraseñas, datos personales) usando AES-256.

**RNF018**

El sistema debe permitir la corrección de errores en un módulo sin afectar otros en menos de 2 horas.

**RNF019**

El sistema debe implementar logging estructurado para facilitar el debugging.

**RNF020**

El backend del sistema debe desarrollarse en php:8.3-fpm-alpine.

**RNF021**

La base de datos del sistema debe utilizar la imagen postgres:16-alpine para PostgreSQL.

**RNF022**

El sistema debe utilizar la imagen nginx:alpine para el servidor web Nginx.

**RNF023**

El sistema debe utilizar la imagen node:20-alpine para Node.js en caso de requerir componentes frontend dinámicos o scripts adicionales.

**RNF024**

El sistema debe incluir Zabbix para monitoreo utilizando las imágenes zabbix/zabbix-server-pgsql:alpine-7.4-latest, zabbix/zabbix-web-nginx-pgsql:alpine-7.4-latest y zabbix/zabbix-agent2:alpine-7.4-latest.

**RNF025**

El sistema debe ser desplegado y gestionado utilizando la tecnología Docker, asegurando la compatibilidad con las imágenes especificadas.

**RNF026**

El sistema debe cumplir las pautas de inspección de ANEP.

**RNF027**

El sistema debe cumplir con la política de asistencia del Liceo Italiano para docentes con más de 16 horas.

**RNF028**

El sistema debe permitir repartir las clases en 2 días si la carga horaria de un grupo supera las 2 horas, salvo que se apliquen pautas de inspección específicas definidas por ANEP.

**RNF029**

El sistema debe permitir distribuir las clases en 3 días si la carga horaria de un grupo excede las 4 horas, con las mismas excepciones.

**RNF030**

El sistema debe permitir cubrir las clases en 4 días si la carga horaria de un grupo sobrepasa las 6 horas, bajo criterios análogos.

**RNF031**

El sistema debe permitir situar las materias exclusivas de programas italianos al final del horario en grupos mixtos de II y III media, y I Liceo Italiano.

**RNF032**

El sistema debe asignar Educación Física a las horas finales del turno, independientemente del grupo.

### 3.6 Interfaces externas

**IE001**

El sistema debe proporcionar una interfaz web responsive accesible desde navegadores Chrome 90+, Firefox 88+, Safari 14+ y Edge 90+.

**IE002**

El sistema debe proporcionar una interfaz móvil optimizada para dispositivos con pantallas de 320 px a 768 px de ancho.

**IE003**

El sistema debe interactuar con PostgreSQL 16 para almacenamiento y recuperación de datos.

**IE004**

El sistema debe operar en un entorno Docker utilizando las imágenes especificadas en la sección 3.5.5 (RNF020-RNF025).

**IE005**

El sistema debe incluir monitoreo mediante Zabbix Server, Zabbix Web y Zabbix Agent para supervisión de rendimiento y disponibilidad.

**IE006**

El sistema debe usar protocolos HTTP/HTTPS para la comunicación cliente-servidor.

**IE007**

El sistema debe implementar WebSockets para actualizaciones en tiempo real del dashboard.

**IE008**

El sistema debe soportar comunicación segura mediante TLS 1.3 o superior.

**IE009**

El sistema debe operar en la infraestructura de hardware de Docker con un mínimo de 4 GB RAM y 2 CPU cores.

**IE010**

El sistema debe soportar almacenamiento persistente de al menos 100 GB para datos y logs.

**IE011**

El sistema debe ser compatible con arquitecturas x86_64 y ARM64.

**IE012**

El sistema debe exponer los siguientes puertos: 80 (Nginx), 5432 (PostgreSQL), 8080 (Zabbix Web), 10051 (Zabbix Server) y 10050 (Zabbix Agent).

### 3.7 Supuestos y Dependencias

#### 3.7.1 Supuestos

Los usuarios tendrán acceso a dispositivos con navegador web y conexión a Internet de al menos 10 Mbps.

La información inicial (disponibilidad, grupos, materias) será completa y oportuna al inicio de cada período académico.

Las pautas de inspección de ANEP serán estables y accesibles durante el período de desarrollo.

Los docentes tendrán conocimientos básicos de informática para usar la interfaz.

#### 3.7.2 Dependencias

La generación de horarios depende de la calidad de las restricciones de entrada (disponibilidad docente, materias, grupos).

La edición de horarios depende de la usabilidad de la interfaz de calendario semanal.

El despliegue depende de la configuración correcta de Docker y las imágenes especificadas.

El rendimiento depende de la infraestructura de hardware disponible.

### 3.8 Verificación de Requisitos

Cada requerimiento funcional será verificado mediante pruebas de aceptación definidas en un plan de pruebas detallado.

Los requerimientos no funcionales serán validados con métricas específicas (tiempo de respuesta, disponibilidad, carga de usuarios) durante pruebas de estrés y uso real.

El sistema debe pasar todas las pruebas de accesibilidad WCAG 2.1 nivel AA utilizando herramientas automatizadas.

El sistema debe mantener una cobertura de pruebas unitarias del 80% mínimo.

### 3.9 Apéndices/Anexos

#### Glosario

**Porcentaje de margen:** Relación entre horas disponibles y horas asignadas de un docente, expresada como porcentaje con dos decimales.

**Observaciones:** Comentarios registrados por docentes sobre su disponibilidad o preferencias, limitados a 500 caracteres.

**Prioridad:** Criterio de ordenación basado en trabajo en otro liceo o porcentaje de margen, utilizado para la generación automática de horarios.

**Pautas de inspección específicas:** Reglas de ANEP para la distribución de clases, incluyendo límites de días según carga horaria.

**Conflicto de horario:** Situación donde un docente está asignado a múltiples clases en el mismo bloque horario.

**Constancia de trabajo:** Documento PDF generado automáticamente para docentes con más de 16 horas asignadas, indicando trabajo en otra institución.

**Multi-tenancy:** Capacidad del sistema para soportar múltiples instituciones en una sola instalación.

**Rate limiting:** Limitación del número de intentos de acceso por IP para prevenir ataques de fuerza bruta.

**WCAG 2.1 AA:** Estándar de accesibilidad web que el sistema debe cumplir para usuarios con discapacidades.

**API REST:** Interfaz de programación que permite la integración del sistema con aplicaciones externas.

**Zabbix:** Sistema de monitoreo de red que supervisa el rendimiento y disponibilidad del sistema Chronos, incluyendo servidor, base de datos y aplicaciones.

**Docker Compose:** Herramienta para definir y ejecutar aplicaciones Docker multi-contenedor, utilizada para orquestar todos los servicios del sistema Chronos.

---

**Versión del documento:** 1.0  
**Fecha de última actualización:** 28/07/2025  
**Próxima revisión:** 28/08/2025
