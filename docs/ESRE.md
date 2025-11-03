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

#### 3.4.1 Gestión de Autenticación y Sesiones

**RF001**

El sistema debe permitir a los usuarios autenticarse mediante cédula (CI) y contraseña.

**RF002**

El sistema debe validar las credenciales contra la base de datos antes de permitir el acceso.

**RF003**

El sistema debe asignar automáticamente los roles del usuario según su perfil en la base de datos tras una autenticación exitosa.

**RF004**

El sistema debe permitir a los usuarios cerrar sesión mediante un botón o enlace dedicado.

**RF005**

El sistema debe proteger todas las rutas excepto login requiriendo autenticación válida para acceder.

**RF006**

El sistema debe mostrar un mensaje de error genérico ("Credenciales incorrectas") en caso de fallo de autenticación para prevenir ataques de fuerza bruta.

**RF007**

El sistema debe cerrar automáticamente la sesión después de 30 minutos de inactividad mostrando una alerta 5 minutos antes del cierre.

#### 3.4.2 Gestión de Usuarios y Roles

**RF008**

El sistema debe permitir a usuarios con rol ADMIN crear nuevos usuarios mediante un formulario que incluya: CI nombre apellido email teléfono y contraseña.

**RF009**

El sistema debe permitir a usuarios con rol ADMIN visualizar una lista de todos los usuarios del sistema en formato tabular.

**RF010**

El sistema debe permitir a usuarios con rol ADMIN ver los detalles completos de un usuario específico.

**RF011**

El sistema debe permitir a usuarios con rol ADMIN editar la información de un usuario existente excepto la CI.

**RF012**

El sistema debe permitir a usuarios con rol ADMIN eliminar usuarios previa confirmación.

**RF013**

El sistema debe permitir a usuarios con rol ADMIN asignar uno o múltiples roles a un usuario (ADMIN DIRECTOR COORDINADOR DOCENTE PADRE).

**RF014**

El sistema debe soportar los siguientes roles: ADMIN (Acceso completo) DIRECTOR (Añadir docentes materias y publicar horarios) COORDINADOR (Gestionar horarios disponibilidad docente observaciones predefinidas) DOCENTE (Gestionar su propia disponibilidad y ver horarios publicados) PADRE (Ver horarios publicados de sus hijos).

**RF015**

El sistema debe validar que la CI ingresada sea única en el sistema.

**RF016**

El sistema debe encriptar todas las contraseñas usando bcrypt antes de almacenarlas.

**RF017**

El sistema debe permitir buscar usuarios por CI nombre apellido o email.

#### 3.4.3 Gestión de Docentes

**RF018**

El sistema debe permitir a usuarios con rol ADMIN o DIRECTOR crear nuevos docentes mediante un formulario que incluya: información del usuario asociado si trabaja en otro liceo y otros datos relevantes.

**RF019**

El sistema debe permitir a usuarios autorizados visualizar una lista de todos los docentes con información relevante: nombre apellido materias que imparte porcentaje de margen observaciones.

**RF020**

El sistema debe permitir ver los detalles completos de un docente incluyendo: información personal materias asignadas disponibilidad carga horaria observaciones.

**RF021**

El sistema debe permitir a usuarios con rol ADMIN editar la información de un docente existente.

**RF022**

El sistema debe permitir a usuarios con rol ADMIN eliminar docentes previa confirmación y eliminando en cascada las relaciones asociadas.

**RF023**

El sistema debe permitir indicar si un docente trabaja en otro liceo mediante un campo booleano.

**RF024**

El sistema debe permitir registrar el nombre de cada liceo en el que trabaja un docente cuando la opción anterior esté marcada.

**RF025**

El sistema debe calcular automáticamente el porcentaje de margen de cada docente como la relación entre horas disponibles y horas asignadas mostrando el resultado con dos decimales.

**RF026**

El sistema debe mostrar el porcentaje de margen de cada docente en la lista de docentes.

**RF027**

El sistema debe permitir filtrar la lista de docentes según el número de observaciones registradas (0 1-2 3-5 6+).

**RF028**

El sistema debe permitir ordenar la lista de docentes según su prioridad determinada por si trabaja en otro liceo o por su porcentaje de margen.

**RF029**

El sistema debe permitir asignar materias que un docente imparte mediante selección múltiple de una lista predefinida.

**RF030**

El sistema debe permitir visualizar la carga horaria total de todas las materias de un docente seleccionado en horas.

**RF031**

El sistema debe permitir visualizar para cada grupo los docentes asignados las materias que imparten y la carga horaria correspondiente en formato tabular.

**RF032**

El sistema debe permitir buscar docentes por nombre apellido CI o materias que imparte.

**RF033**

El sistema debe permitir a un docente visualizar sus horarios asignados en formato tabular o calendario.

**RF034**

El sistema debe permitir al rol DOCENTE visualizar los horarios publicados en formato tabular.

#### 3.4.4 Gestión de Disponibilidad Docente

**RF035**

El sistema debe permitir al docente registrar sus franjas horarias disponibles para cada día de lunes a viernes mediante un calendario interactivo.

**RF036**

El sistema debe proporcionar una interfaz de calendario semanal donde el docente puede marcar/desmarcar bloques horarios como disponibles o no disponibles.

**RF037**

El sistema debe registrar automáticamente la fecha de envío de las aspiraciones horarias de cada docente con timestamp preciso.

**RF038**

El sistema debe permitir al docente visualizar su disponibilidad horaria registrada.

**RF039**

El sistema debe permitir al docente editar su disponibilidad horaria en cualquier momento.

**RF040**

El sistema debe permitir a usuarios con rol ADMIN gestionar la disponibilidad de todos los docentes desde la sección correspondiente.

**RF041**

El sistema debe permitir a usuarios con rol COORDINADOR gestionar la disponibilidad docente mediante formularios de edición.

**RF042**

El sistema debe verificar la disponibilidad del docente antes de asignar un horario mostrando advertencias si el docente no está disponible.

**RF043**

El sistema debe resaltar visualmente los bloques horarios disponibles del docente en la interfaz de gestión de horarios.

**RF044**

El sistema debe permitir visualizar la disponibilidad combinada de múltiples docentes cuando se selecciona una materia que puede ser impartida por varios docentes.

#### 3.4.5 Gestión de Materias y Asignaturas

**RF045**

El sistema debe permitir a usuarios con rol ADMIN o DIRECTOR crear nuevas materias mediante un formulario que incluya: nombre horas semanales pauta ANEP asociada si se imparte en conjunto y grupo compartido si aplica.

**RF046**

El sistema debe permitir visualizar una lista de todas las materias del sistema con información relevante: nombre horas semanales pauta ANEP docentes asignados.

**RF047**

El sistema debe permitir ver los detalles completos de una materia incluyendo: información básica pauta ANEP grupos asignados docentes que la imparten horas semanales.

**RF048**

El sistema debe permitir a usuarios con rol ADMIN editar la información de una materia existente.

**RF049**

El sistema debe permitir a usuarios con rol ADMIN eliminar materias previa confirmación y validando que no tenga asignaciones activas.

**RF050**

El sistema debe permitir al usuario seleccionar al crear o modificar una materia cuál de las pautas de inspección específicas de ANEP aplica desde una lista predefinida.

**RF051**

El sistema debe permitir al usuario seleccionar si una materia se impartirá en conjunto con otra mediante un campo booleano.

**RF052**

El sistema debe solicitar al usuario el grupo con el que se compartirá la materia cuando se seleccione que se impartirá en conjunto.

**RF053**

El sistema debe permitir marcar una materia como perteneciente al programa italiano mediante un campo booleano.

**RF054**

El sistema debe validar que las horas semanales de una materia sean un número positivo mayor que cero.

**RF055**

El sistema debe permitir asignar materias a grupos específicos mediante el módulo de asignaciones.

**RF056**

El sistema debe permitir buscar materias por nombre o por pauta ANEP asociada.

#### 3.4.6 Gestión de Grupos

**RF057**

El sistema debe permitir a usuarios con rol ADMIN crear nuevos grupos mediante un formulario que incluya: nombre y nivel.

**RF058**

El sistema debe permitir visualizar una lista de todos los grupos del sistema con información relevante: nombre nivel número de estudiantes materias asignadas.

**RF059**

El sistema debe permitir ver los detalles completos de un grupo incluyendo: información básica materias asignadas docentes asignados horario completo.

**RF060**

El sistema debe permitir a usuarios con rol ADMIN editar la información de un grupo existente.

**RF061**

El sistema debe permitir a usuarios con rol ADMIN eliminar grupos previa confirmación y validando que no tenga asignaciones activas.

**RF062**

El sistema debe permitir asignar materias a un grupo mediante el módulo de asignaciones grupo-materia.

**RF063**

El sistema debe permitir asignar padres a grupos (para visualizar horarios de sus hijos).

**RF064**

El sistema debe permitir visualizar el horario completo de un grupo en formato tabular o calendario.

**RF065**

El sistema debe permitir buscar grupos por nombre o nivel.

#### 3.4.7 Gestión de Horarios

**RF066**

El sistema debe permitir a usuarios autorizados (COORDINADOR ADMIN) crear nuevas asignaciones de horario especificando: grupo docente materia bloque horario y día de la semana.

**RF067**

El sistema debe generar automáticamente horarios completos considerando: restricciones de disponibilidad docente asignaturas impartidas por cada docente reglas de distribución ANEP carga horaria semanal de cada materia y porcentaje de margen de cada docente.

**RF068**

El sistema debe verificar todos los grupos y docentes considerando las restricciones de disponibilidad docente las asignaturas impartidas y las reglas de distribución ANEP garantizando que cada docente tenga asignaciones compatibles con su disponibilidad registrada.

**RF069**

El sistema debe aplicar las reglas de distribución de horas al generar los horarios: Materias con 1-2 horas semanales máximo 2 días Materias con 3-4 horas semanales máximo 3 días Materias con 5+ horas semanales máximo 4 días.

**RF070**

El sistema debe permitir al coordinador editar manualmente los horarios generados mediante una interfaz de calendario semanal con funcionalidad de arrastrar y soltar.

**RF071**

El sistema debe proporcionar una vista de calendario semanal para la edición manual de horarios con arrastrar y soltar mostrando días de la semana (Lunes a Viernes) y bloques horarios.

**RF072**

El sistema debe permitir a usuarios autorizados consultar (ver) los horarios en formato tabular con columnas para días de la semana y filas para bloques horarios.

**RF073**

El sistema debe permitir visualizar horarios filtrados por grupo específico.

**RF074**

El sistema debe permitir visualizar horarios filtrados por docente específico.

**RF075**

El sistema debe permitir visualizar horarios filtrados por materia específica.

**RF076**

El sistema debe permitir a usuarios autorizados eliminar asignaciones de horario obsoletas o incorrectas previa confirmación.

**RF077**

El sistema debe permitir modificar una asignación de horario existente (cambiar docente bloque día o materia).

**RF078**

El sistema debe permitir intercambiar (swap) dos asignaciones de horario entre sí.

**RF079**

El sistema debe permitir mover rápidamente una asignación de horario a otro bloque o día mediante una operación de arrastrar y soltar.

**RF080**

El sistema debe permitir crear rápidamente una asignación de horario mediante un formulario contextual que aparezca al hacer clic en una celda vacía del calendario.

**RF081**

El sistema debe proporcionar una función de selección automática de docente que escoja el docente más adecuado para una materia y horario basándose en: disponibilidad carga de trabajo historial de enseñanza conflictos.

**RF082**

El sistema debe resaltar visualmente en el calendario los bloques donde el docente seleccionado está disponible.

**RF083**

El sistema debe validar que las asignaciones de una materia no excedan las horas semanales requeridas.

**RF084**

El sistema debe permitir filtrar la vista de horarios por múltiples criterios simultáneamente (grupo docente materia día).

**RF085**

El sistema debe permitir buscar asignaciones de horario por docente materia o grupo.

**RF086**

El sistema debe permitir seleccionar un grupo específico para visualizar su horario completo en la interfaz de gestión.

#### 3.4.8 Detección y Resolución de Conflictos

**RF087**

El sistema debe detectar automáticamente cuando un grupo tiene múltiples clases asignadas en el mismo bloque horario y día.

**RF088**

El sistema debe detectar automáticamente cuando un docente está asignado a múltiples clases en el mismo bloque horario y día.

**RF089**

El sistema debe detectar cuando una asignación de horario está en un bloque donde el docente no está disponible.

**RF090**

El sistema debe detectar cuando una materia excede las horas semanales asignadas en un grupo.

**RF091**

El sistema debe detectar cuando una asignación no cumple con las pautas de distribución ANEP asociadas a la materia.

**RF092**

El sistema debe clasificar los conflictos según severidad: Error (crítico) - Conflicto de grupo o docente (rojo) Advertencia (warning) - Conflictos de disponibilidad o carga horaria (amarillo) Información (info) - No cumple recomendaciones ANEP (azul).

**RF093**

El sistema debe mostrar los conflictos detectados con códigos de color en la interfaz: rojo para críticos amarillo para advertencias azul para información.

**RF094**

El sistema debe resaltar visualmente las celdas del calendario que contienen conflictos durante la edición con tooltip explicativo.

**RF095**

El sistema debe verificar conflictos antes de crear una nueva asignación de horario y prevenir la creación si hay conflictos críticos.

**RF096**

El sistema debe permitir resolución manual de conflictos de horarios permitiendo al usuario decidir si continuar a pesar de las advertencias.

**RF097**

El sistema debe proporcionar sugerencias para resolver conflictos como: buscar horarios alternativos para el grupo buscar docentes alternativos actualizar disponibilidad del docente.

**RF098**

El sistema debe mostrar un resumen de todos los conflictos detectados en el horario actual con contadores por tipo de conflicto.

**RF099**

El sistema debe permitir validar un horario completo y generar un reporte de todos los conflictos encontrados.

**RF100**

El sistema debe permitir forzar una asignación a pesar de conflictos no críticos (advertencias) previa confirmación del usuario.

**RF101**

El sistema debe detectar y mostrar conflictos en tiempo real mientras el usuario edita el horario.

#### 3.4.9 Publicación de Horarios

**RF102**

El sistema debe permitir a usuarios con rol COORDINADOR o ADMIN crear una solicitud de publicación del horario actual.

**RF103**

El sistema debe generar automáticamente un snapshot (captura) del estado actual de todos los horarios al crear una solicitud de publicación.

**RF104**

El sistema debe generar un hash único del estado de los horarios para verificar la integridad del snapshot.

**RF105**

El sistema debe gestionar los siguientes estados para las solicitudes de publicación: Pendiente (Solicitud creada esperando aprobación) Aprobado (Solicitud aprobada por DIRECTOR horario publicado) Rechazado (Solicitud rechazada por DIRECTOR).

**RF106**

El sistema debe permitir a usuarios con rol DIRECTOR aprobar una solicitud de publicación pendiente.

**RF107**

El sistema debe permitir a usuarios con rol DIRECTOR rechazar una solicitud de publicación opcionalmente agregando notas explicativas.

**RF108**

El sistema debe permitir a usuarios con rol DIRECTOR publicar horarios directamente sin necesidad de solicitud previa.

**RF109**

El sistema debe desactivar automáticamente todas las publicaciones anteriores al activar una nueva publicación.

**RF110**

El sistema debe permitir a todos los usuarios autorizados ver los horarios publicados en formato de solo lectura.

**RF111**

El sistema debe permitir a coordinadores ver el estado de sus solicitudes de publicación pendientes.

**RF112**

El sistema debe permitir a directores ver una lista de todas las solicitudes de publicación pendientes.

**RF113**

El sistema debe registrar automáticamente quién y cuándo aprobó o rechazó una solicitud de publicación.

**RF114**

El sistema debe permitir a usuarios con rol DIRECTOR eliminar una publicación activa previa confirmación.

**RF115**

El sistema debe impedir al rol COORDINADOR publicar horarios directamente mostrando un mensaje de error si intenta hacerlo.

#### 3.4.10 Gestión de Asignaciones

**RF116**

El sistema debe permitir asignar docentes a materias que imparten mediante el módulo de asignaciones.

**RF117**

El sistema debe permitir asignar materias a grupos específicos con horas semanales determinadas.

**RF118**

El sistema debe permitir visualizar todas las asignaciones de docentes a materias en formato tabular.

**RF119**

El sistema debe permitir visualizar todas las asignaciones de materias a grupos en formato tabular.

**RF120**

El sistema debe permitir eliminar asignaciones de docentes a materias previa confirmación.

**RF121**

El sistema debe permitir eliminar asignaciones de materias a grupos previa confirmación.

**RF122**

El sistema debe permitir editar las horas semanales de una materia en un grupo específico.

**RF123**

El sistema debe validar que no se puedan crear asignaciones duplicadas (mismo docente-materia o grupo-materia).

**RF124**

El sistema debe permitir asignar un docente a múltiples materias y una materia a múltiples grupos.

**RF125**

El sistema debe impedir al rol COORDINADOR crear nuevas asignaciones ocultando los botones correspondientes.

**RF126**

El sistema debe permitir al rol COORDINADOR editar asignaciones existentes mediante formularios de edición.

#### 3.4.11 Gestión de Coordinadores

**RF127**

El sistema debe permitir a usuarios con rol ADMIN crear nuevos coordinadores asignando el rol COORDINADOR a un usuario existente.

**RF128**

El sistema debe permitir visualizar una lista de todos los coordinadores del sistema.

**RF129**

El sistema debe permitir ver los detalles completos de un coordinador.

**RF130**

El sistema debe permitir a usuarios con rol ADMIN editar la información de un coordinador.

**RF131**

El sistema debe permitir a usuarios con rol ADMIN eliminar el rol de coordinador de un usuario previa confirmación.

**RF132**

El sistema debe proporcionar un dashboard específico para coordinadores con acceso a: gestión de horarios disponibilidad docente observaciones predefinidas.

#### 3.4.12 Gestión de Padres y Estudiantes

**RF133**

El sistema debe permitir a usuarios con rol ADMIN crear nuevos padres asignando el rol PADRE a un usuario existente.

**RF134**

El sistema debe permitir asignar padres a grupos específicos (para que puedan ver horarios de sus hijos).

**RF135**

El sistema debe permitir visualizar una lista de todos los padres del sistema con sus grupos asignados.

**RF136**

El sistema debe permitir al rol PADRE visualizar los horarios publicados únicamente para los grupos asignados mediante un selector de estudiante/grupo.

**RF137**

El sistema debe proporcionar un dashboard específico para padres con acceso a: visualización de horarios de sus hijos.

**RF138**

El sistema debe permitir ver los detalles completos de un padre incluyendo grupos asignados.

**RF139**

El sistema debe permitir a usuarios con rol ADMIN editar la información de un padre y sus asignaciones a grupos.

**RF140**

El sistema debe permitir a usuarios con rol ADMIN eliminar el rol de padre de un usuario previa confirmación.

#### 3.4.13 Gestión de Observaciones

**RF141**

El sistema debe permitir al docente registrar observaciones sobre su disponibilidad seleccionando observaciones predefinidas desde una lista desplegable.

**RF142**

El sistema debe permitir al docente describir el motivo de sus observaciones en un campo de texto libre de máximo 500 caracteres.

**RF143**

El sistema debe incluir siempre una observación predefinida llamada "Otro" que permita al docente especificar texto libre al seleccionarla.

**RF144**

El sistema debe incluir siempre una observación predefinida llamada "Otro Liceo" que permita al docente especificar el nombre de los liceos correspondientes al seleccionarla.

**RF145**

El sistema debe mostrar simultáneamente para cada docente el porcentaje de margen las observaciones y los motivos registrados en una vista consolidada.

**RF146**

El sistema debe permitir al rol COORDINADOR crear nuevas observaciones predefinidas mediante un formulario de gestión.

**RF147**

El sistema debe permitir al rol COORDINADOR leer el listado completo de observaciones predefinidas en formato tabular.

**RF148**

El sistema debe permitir al rol COORDINADOR actualizar el texto de observaciones predefinidas existentes mediante formularios de edición.

**RF149**

El sistema debe permitir al rol COORDINADOR eliminar observaciones predefinidas excepto las observaciones "Otro" y "Otro Liceo" previa confirmación.

**RF150**

El sistema debe distinguir entre observaciones predefinidas del sistema (no eliminables) y observaciones personalizadas (eliminables).

**RF151**

El sistema debe permitir activar o desactivar observaciones predefinidas sin eliminarlas.

#### 3.4.14 Gestión de Bloques Horarios

**RF152**

El sistema debe permitir visualizar una lista de todos los bloques horarios del sistema con: ID hora de inicio hora de fin.

**RF153**

El sistema debe permitir a usuarios con rol ADMIN crear nuevos bloques horarios especificando hora de inicio y hora de fin.

**RF154**

El sistema debe permitir a usuarios con rol ADMIN editar bloques horarios existentes.

**RF155**

El sistema debe permitir a usuarios con rol ADMIN eliminar bloques horarios previa confirmación y validando que no tenga asignaciones activas.

**RF156**

El sistema debe validar que la hora de fin sea posterior a la hora de inicio en un bloque horario.

**RF157**

El sistema debe incluir bloques horarios predeterminados al inicializar la base de datos.

#### 3.4.15 Gestión de Pautas ANEP

**RF158**

El sistema debe permitir a usuarios con rol ADMIN crear nuevas pautas ANEP especificando: nombre días mínimos días máximos condiciones especiales.

**RF159**

El sistema debe permitir visualizar una lista de todas las pautas ANEP del sistema.

**RF160**

El sistema debe permitir a usuarios con rol ADMIN editar pautas ANEP existentes.

**RF161**

El sistema debe permitir a usuarios con rol ADMIN eliminar pautas ANEP previa confirmación y validando que no esté asociada a materias.

**RF162**

El sistema debe validar que los días máximos sean mayores o iguales a los días mínimos y que ambos sean mayores que cero.

**RF163**

El sistema debe permitir definir condiciones especiales en las pautas ANEP como: no días consecutivos solo horario de mañana solo horario de tarde.

**RF164**

El sistema debe permitir asociar una pauta ANEP a una materia al crear o editar la materia.

#### 3.4.16 Reportes y Estadísticas

**RF165**

El sistema debe permitir generar reportes de docentes con información: nombre apellido materias carga horaria porcentaje de margen observaciones.

**RF166**

El sistema debe permitir generar reportes de grupos con información: nombre nivel materias asignadas docentes asignados carga horaria total.

**RF167**

El sistema debe permitir generar reportes de horarios en formato tabular o de calendario.

**RF168**

El sistema debe proporcionar estadísticas generales del sistema: total de usuarios docentes grupos materias asignaciones de horario.

**RF169**

El sistema debe permitir generar un reporte de todos los conflictos detectados en los horarios actuales.

**RF170**

El sistema debe permitir generar reportes de disponibilidad docente.

**RF171**

El sistema debe mostrar en los dashboards estadísticas relevantes según el rol del usuario.

#### 3.4.17 Interfaz de Usuario

##### 3.4.17.1 Requerimientos Generales de la Interfaz

**RF172**

El sistema debe proporcionar una interfaz web accesible desde navegadores Chrome 90+ Firefox 88+ Safari 14+ y Edge 90+.

**RF173**

El sistema debe adaptar automáticamente el diseño de la interfaz al tamaño de pantalla del dispositivo (diseño responsive) utilizando CSS Grid y Flexbox soportando dispositivos de escritorio (1024px+) y móviles (320px-768px).

**RF174**

El sistema debe permitir la navegación entre secciones mediante un menú principal lateral o superior.

**RF175**

El sistema debe mostrar menús y opciones según el rol autenticado del usuario ocultando funciones no autorizadas.

**RF176**

El sistema debe proporcionar una ruta de navegación (breadcrumbs) en páginas con múltiples niveles mostrando la jerarquía completa.

**RF177**

El sistema debe adaptar el dashboard inicial para cada rol mostrando solo la información relevante.

##### 3.4.17.2 Requerimientos de Formularios y Validación

**RF178**

El sistema debe validar los datos ingresados en formularios (campos obligatorios formatos de email teléfono) antes de enviarlos al servidor.

**RF179**

El sistema debe mostrar mensajes de error específicos y contextuales cuando falla una validación de formulario indicando el campo exacto y la corrección necesaria.

**RF180**

El sistema debe mostrar mensajes de confirmación mediante modales antes de ejecutar operaciones críticas (eliminación de registros) con botones "Confirmar" y "Cancelar".

**RF181**

El sistema debe mostrar mensajes de éxito (verde) o error (rojo) después de operaciones de creación edición o eliminación de registros con detalles claros y auto-ocultación después de 5 segundos.

##### 3.4.17.3 Requerimientos de Tablas y Visualización de Datos

**RF182**

El sistema debe permitir aplicar múltiples filtros simultáneamente en las tablas mostrando el número de resultados filtrados.

**RF183**

El sistema debe implementar paginación en tablas con más de 10 registros mostrando opciones de 10 25 50 registros por página.

**RF184**

El sistema debe mostrar resúmenes o totales en la parte inferior de las tablas cuando sea aplicable (total de horas promedio de carga).

**RF185**

El sistema debe permitir arrastrar y soltar asignaciones de horario en el calendario semanal para reordenarlas.

**RF186**

El sistema debe permitir seleccionar múltiples elementos en listas para realizar operaciones en lote.

**RF187**

El sistema debe implementar auto-guardado para cambios en formularios extensos evitando pérdida de datos.

##### 3.4.17.4 Requerimientos de Accesibilidad

**RF188**

El sistema debe cumplir con los estándares de accesibilidad WCAG 2.1 nivel AA para usuarios con discapacidades incluyendo contraste mínimo 4.5:1 etiquetas ARIA y navegación por teclado.

**RF189**

El sistema debe permitir navegación completa por teclado (Tab Enter Escape) en todas las funcionalidades.

**RF190**

El sistema debe soportar zoom hasta 200% sin pérdida de funcionalidad.

**RF191**

El sistema debe permitir la impresión de horarios en formato optimizado para papel A4 con márgenes de 1cm.

#### 3.4.18 Traducciones y Multiidioma

**RF192**

El sistema debe permitir a los usuarios seleccionar el idioma de la interfaz entre: Español Inglés Italiano.

**RF193**

El sistema debe mantener la selección de idioma del usuario durante toda la sesión.

**RF194**

El sistema debe mostrar todos los textos de la interfaz en el idioma seleccionado.

**RF195**

El sistema debe permitir a usuarios con rol ADMIN gestionar las traducciones del sistema mediante una interfaz de administración.

**RF196**

El sistema debe permitir agregar nuevas claves de traducción y sus valores en cada idioma.

**RF197**

El sistema debe permitir editar traducciones existentes.

**RF198**

El sistema debe detectar y mostrar claves de traducción que faltan en algún idioma.

**RF199**

El sistema debe proporcionar estadísticas de cobertura de traducciones por idioma.

**RF200**

El sistema debe permitir exportar traducciones para edición externa.

#### 3.4.19 Operaciones en Lote

**RF201**

El sistema debe permitir eliminar múltiples asignaciones de horario seleccionadas en una sola operación.

**RF202**

El sistema debe permitir copiar múltiples asignaciones de horario en lote (funcionalidad en desarrollo).

**RF203**

El sistema debe permitir mover múltiples asignaciones de horario en lote (funcionalidad en desarrollo).

**RF204**

El sistema debe permitir editar propiedades comunes de múltiples asignaciones de horario seleccionadas.

**RF205**

El sistema debe permitir seleccionar múltiples elementos mediante checkboxes o selección de rango.

**RF206**

El sistema debe solicitar confirmación antes de ejecutar operaciones en lote que modifiquen múltiples registros.

**RF207**

El sistema debe mostrar un reporte después de una operación en lote indicando cuántos elementos se procesaron exitosamente y cuántos fallaron.

#### 3.4.20 Registro de Actividad (Logging)

**RF208**

El sistema debe registrar todos los accesos de usuarios autenticados en un log.

**RF209**

El sistema debe registrar todas las modificaciones críticas (creación edición eliminación) de entidades importantes (horarios docentes materias) en un log auditable.

**RF210**

El sistema debe registrar en cada entrada de log: timestamp usuario acción resultado y detalles relevantes.

**RF211**

El sistema debe permitir a usuarios con rol ADMIN consultar los logs del sistema con filtros por usuario fecha acción.

**RF212**

El sistema debe permitir exportar logs para análisis externo.

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
