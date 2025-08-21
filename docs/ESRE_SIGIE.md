## S.I.G.I.E — Chronos

### E.S.R.E — Especificación de Requerimientos de Software

- **Fecha de entrega**: 28/07/2025

### Equipo

| Rol | Apellido | Nombre | Cédula Identidad | Email | Tel./Cel. |
|---|---|---|---|---|---|
| Coordinador | Chapuis | Juan | 5.630.283-4 | jchapuis@scuolaitaliana.edu.uy | 098844043 |
| Sub-Coordinador | Roizen | Agustin | 6.339.592-9 | aroizen@scuolaitaliana.edu.uy | 097259510 |
| Integrante | Marino | Bruno | 5.707.620-6 | bmarino@scuolaitaliana.edu.uy | 092617596 |

---

### Tabla de Contenidos

- **1. Propósito y Alcance del Software**
- **2. Perspectiva del Producto**
- **3. Funciones Específicas**
  - 3.1. Funciones Esperables
  - 3.2. Funciones Futuras
- **4. Requerimientos Funcionales**
  - 4.1. Gestión de Horarios
  - 4.2. Gestión de Docentes y Disponibilidad
  - 4.3. Administración de Usuarios y Permisos
  - 4.4. Interfaz de Usuario
- **5. Requerimientos No Funcionales**
  - 5.1. Rendimiento
  - 5.2. Usabilidad
  - 5.3. Fiabilidad
  - 5.4. Seguridad
  - 5.5. Mantenibilidad
  - 5.6. Restricciones
- **6. Interfaces Externas**
  - 6.1. Interfaces de Usuario
  - 6.2. Interfaces de Software
  - 6.3. Interfaces de Comunicación
  - 6.4. Interfaces de Hardware
- **7. Supuestos y Dependencias**
  - 7.1. Supuestos
  - 7.2. Dependencias
- **8. Verificación de Requisitos**
- **9. Apéndices/Anexos — Glosario**

---

## 1. Propósito y Alcance del Software

El presente documento, la Especificación de Requerimientos de Software (ESRE), tiene como propósito describir de manera clara, precisa y verificable todas las funciones y restricciones del sistema web de gestión de horarios escolares antes de que comience su programación o diseño. Este documento funcionará como un contrato de software y servirá como base para las fases de diseño, desarrollo, prueba y mantenimiento, permitiendo minimizar errores de interpretación y evitando costos futuros.

El alcance de este software se centra en proveer una plataforma web para la gestión integral de horarios docentes y restricciones educativas dentro de una institución escolar. Incluirá la generación automática de horarios basada en un algoritmo de restricciones, la edición manual de los horarios generados, y la visualización de los mismos para diferentes perfiles de usuario. El sistema busca optimizar el proceso de creación de horarios, facilitar el seguimiento de las funcionalidades y adaptarse a las necesidades de disponibilidad docente y las pautas educativas específicas.

## 2. Perspectiva del Producto

El sistema es una aplicación web independiente diseñada para gestionar y optimizar la asignación de franjas horarias a docentes y asignaturas en el Liceo Italiano, considerando restricciones como disponibilidad docente, asignaturas impartidas y horas semanales requeridas. Operará como una herramienta central para la coordinación académica, integrándose con procesos existentes como la recolección de aspiraciones horarias de docentes y la asignación de grupos y materias por la dirección. Su interfaz facilitará la interacción de administradores, dirección, coordinadores, docentes y padres.

## 3. Funciones Específicas

### 3.1. Funciones Esperables

- **Gestión de Horarios**: Generación automática y edición manual de horarios basados en restricciones.
- **Gestión de Docentes y Disponibilidad**: Registro y visualización de aspiraciones horarias de docentes.
- **Gestión de Usuarios y Roles**: Soporte para roles con permisos específicos.
- **Interfaz de Usuario**: Acceso y manipulación de datos para todos los roles.

### 3.2. Funciones Futuras

- **Exportaciones**: Exportación de horarios en PDF y Excel.
- **Notificaciones**: Envíos por correo electrónico ante cambios de horarios.

## 4. Requerimientos Funcionales

### 4.1. Gestión de Horarios

- El sistema debe generar automáticamente horarios para todos los grupos y docentes basado en las restricciones de disponibilidad docente y las asignaturas impartidas.
- El sistema debe aplicar las reglas de distribución de horas al generar los horarios (por ejemplo, repartir clases en 2, 3 o 4 días según la carga horaria).
- El sistema debe permitir al coordinador editar manualmente los horarios generados.
- El sistema debe permitir al usuario seleccionar, al crear o modificar una materia, cuál de las pautas de inspección específicas de ANEP aplica.
- El sistema debe permitir al usuario seleccionar si una materia se impartirá en conjunto con otra.
- El sistema debe solicitar al usuario el grupo con el que se compartirá la materia cuando se seleccione que se impartirá en conjunto.
- El sistema debe permitir a usuarios autorizados (coordinador, administrador) solicitar la generación de horarios.
- El sistema debe generar automáticamente horarios al inicio de cada semestre o período académico, según la configuración establecida.
- El sistema debe permitir a usuarios autorizados consultar (ver) los horarios generados.
- El sistema debe permitir a usuarios autorizados eliminar horarios obsoletos o incorrectos.

### 4.2. Gestión de Docentes y Disponibilidad

- El sistema debe permitir al docente registrar su nombre completo.
- El sistema debe permitir al docente registrar las asignaturas que imparte.
- El sistema debe permitir al docente registrar su número de teléfono celular.
- El sistema debe permitir al docente registrar sus franjas horarias disponibles para cada día de lunes a viernes.
- El sistema debe permitir al docente indicar si trabaja en otros liceos.
- El sistema debe permitir al docente registrar el nombre de cada liceo en el que trabaja, si corresponde.
- El sistema debe registrar automáticamente la fecha de envío de las aspiraciones horarias de cada docente.
- El sistema debe calcular el porcentaje de margen de cada docente como la relación entre horas disponibles y horas asignadas.
- El sistema debe mostrar el porcentaje de margen de cada docente.
- El sistema debe permitir filtrar la lista de docentes según el número de observaciones registradas.
- El sistema debe permitir ordenar la lista de docentes según su prioridad, determinada por si trabaja en otro liceo o por su porcentaje de margen.
- El sistema debe permitir al docente seleccionar observaciones predefinidas sobre su disponibilidad.
- El sistema debe permitir al docente describir el motivo de sus observaciones.
- El sistema debe mostrar simultáneamente, para cada docente, el porcentaje de margen, las observaciones y los motivos registrados.
- El sistema debe permitir visualizar la carga horaria total de todas las materias de un docente seleccionado.
- El sistema debe permitir visualizar, para cada grupo, los docentes asignados, las materias que imparten y la carga horaria correspondiente.
- El sistema debe permitir a usuarios autorizados modificar los datos de un docente.
- El sistema debe permitir a usuarios autorizados eliminar registros de docentes cuando sea necesario.
- El sistema debe permitir al docente visualizar su disponibilidad horaria sin la posibilidad de gestionarla directamente, si así está definido en su rol.
- El sistema debe incluir siempre una observación predefinida llamada “Otro”, que permita al docente especificar texto libre al seleccionarla.
- El sistema debe incluir siempre una observación predefinida llamada “Otro Liceo”, que permita al docente especificar el nombre de los liceos correspondientes al seleccionarla.
- El sistema debe permitir al rol Coordinador crear nuevas observaciones predefinidas.
- El sistema debe permitir al rol Coordinador leer el listado completo de observaciones predefinidas.
- El sistema debe permitir al rol Coordinador actualizar el texto de observaciones predefinidas existentes.
- El sistema debe permitir al rol Coordinador eliminar observaciones predefinidas, excepto las observaciones “Otro” y “Otro Liceo”.
- El sistema debe permitir al rol Coordinador actualizar el texto de observaciones existentes.
- El sistema debe permitir al rol Coordinador eliminar observaciones predefinidas (excepto "Otro" y "Otro Liceo").

### 4.3. Administración de Usuarios y Permisos

- El sistema debe soportar los roles de usuario: Administrador, Dirección, Coordinador, Docente y Padre.
- El sistema debe permitir al usuario seleccionar su rol durante el proceso de login.
- El sistema debe permitir al rol Dirección añadir nuevos docentes.
- El sistema debe permitir al rol Dirección añadir nuevas materias.
- El sistema debe permitir al rol Dirección publicar los horarios generados.
- El sistema debe permitir al rol Coordinador generar horarios.
- El sistema debe permitir al rol Coordinador editar horarios.
- El sistema debe permitir al rol Coordinador gestionar la disponibilidad docente.
- El sistema debe asignar roles a los usuarios según su autenticación al ingresar.
- El sistema debe permitir al rol Docente gestionar su propia disponibilidad horaria.
- El sistema debe permitir al rol Docente visualizar los horarios publicados.
- El sistema debe permitir al rol Padre visualizar los horarios publicados únicamente para sus hijos.
- El sistema debe permitir al rol Coordinador visualizar la asignación de grupos a docentes y las materias que imparten.
- El sistema debe permitir al rol Coordinador editar la asignación de grupos a docentes y las materias que imparten.
- El sistema debe impedir al rol Coordinador crear nuevas asignaciones de grupos a docentes o materias.
- El sistema debe registrar constancias de trabajo en otra institución para docentes con más de 16 horas asignadas.
- El sistema debe registrar constancias de actividades justificadas para docentes con más de 16 horas asignadas.

### 4.4. Interfaz de Usuario

- El sistema debe proporcionar una interfaz web accesible desde navegadores modernos.
- El sistema debe permitir el acceso a la interfaz desde dispositivos de escritorio y móviles.
- El sistema debe adaptar automáticamente el diseño de la interfaz al tamaño de pantalla del dispositivo.
- El sistema debe mantener un diseño visual consistente en todas las pantallas.
- El sistema debe mostrar menús y opciones según el rol autenticado del usuario.
- El sistema debe ocultar funciones y opciones para las que el usuario no tenga autorización.
- El sistema debe permitir la navegación entre secciones mediante un menú principal.
- El sistema debe presentar tablas de datos con opciones de filtrado por criterios definidos.
- El sistema debe permitir ordenar tablas de datos en forma ascendente o descendente según las columnas seleccionadas.
- El sistema debe mostrar mensajes de confirmación antes de ejecutar operaciones críticas como la eliminación de registros.
- El sistema debe mostrar mensajes de éxito o error después de operaciones de creación, edición o eliminación de registros.
- El sistema debe permitir al rol Administrador crear registros de docentes.
- El sistema debe permitir al rol Administrador crear registros de materias.
- El sistema debe permitir al rol Administrador crear registros de horarios.
- El sistema debe permitir al rol Administrador leer registros de docentes.
- El sistema debe permitir al rol Administrador leer registros de materias.
- El sistema debe permitir al rol Administrador leer registros de horarios.
- El sistema debe permitir al rol Administrador actualizar registros de docentes.
- El sistema debe permitir al rol Administrador actualizar registros de materias.
- El sistema debe permitir al rol Administrador actualizar registros de horarios.
- El sistema debe permitir al rol Administrador eliminar registros de docentes.
- El sistema debe permitir al rol Administrador eliminar registros de materias.
- El sistema debe permitir al rol Administrador eliminar registros de horarios.
- El sistema debe permitir al rol Coordinador leer registros de horarios.
- El sistema debe permitir al rol Coordinador leer registros de disponibilidad docente.
- El sistema debe permitir al rol Coordinador actualizar registros de horarios.
- El sistema debe permitir al rol Coordinador actualizar registros de disponibilidad docente.
- El sistema debe permitir al rol Docente leer su disponibilidad horaria.
- El sistema debe permitir al rol Docente leer los horarios publicados.
- El sistema debe permitir al rol Docente actualizar su disponibilidad horaria.
- El sistema debe permitir al rol Padre leer los horarios publicados de sus hijos.

## 5. Requerimientos No Funcionales

### 5.1. Rendimiento

- El sistema debe generar un horario completo para 10 grupos y 20 docentes en menos de 5 minutos.
- El sistema debe soportar al menos 60 usuarios concurrentes con tiempos de respuesta menores a 2 segundos para operaciones comunes (ej. consultar horarios).

### 5.2. Usabilidad

- El sistema debe permitir a un usuario nuevo registrar su disponibilidad en menos de 5 minutos tras una capacitación de 10 minutos.
- El sistema debe presentar los horarios en un formato tabular claro y legible.

### 5.3. Fiabilidad

- El sistema debe estar disponible el 99% del tiempo durante el horario escolar (lunes a viernes, 8:00-17:00).

### 5.4. Seguridad

- El sistema debe requerir autenticación con credenciales únicas (usuario y contraseña) para todos los usuarios.
- El sistema debe implementar control de acceso basado en roles, restringiendo funciones y datos según el rol.
- El sistema debe registrar todos los accesos y modificaciones críticas en un log auditable.

### 5.5. Mantenibilidad

- El sistema debe permitir la corrección de errores en un módulo sin afectar otros en menos de 2 horas.
- El sistema debe incluir documentación interna del código y configuraciones.

### 5.6. Restricciones

- El backend del sistema debe desarrollarse en `php:8.3.10-fpm-alpine`.
- La base de datos del sistema debe utilizar la imagen `postgres:16.3-alpine` para PostgreSQL.
- El sistema debe utilizar la imagen `nginx:1.27.0-alpine` para el servidor web Nginx.
- El sistema debe utilizar la imagen `node:20.15.0-alpine` para Node.js en caso de requerir componentes frontend dinámicos o scripts adicionales.
- El sistema debe ser desplegado y gestionado utilizando la tecnología Docker, asegurando la compatibilidad con las imágenes especificadas.
- El sistema debe cumplir las pautas de inspección de ANEP y la política de asistencia del Liceo Italiano para docentes con más de 16 horas.
- El sistema debe repartir las clases en 2 días si la carga horaria de un grupo supera las 2 horas, salvo que se apliquen pautas de inspección específicas definidas por ANEP.
- El sistema debe distribuir las clases en 3 días si la carga horaria de un grupo excede las 4 horas, con las mismas excepciones.
- El sistema debe cubrir las clases en 4 días si la carga horaria de un grupo sobrepasa las 6 horas, bajo criterios análogos.
- El sistema debe situar las materias exclusivas de programas italianos al final del horario en grupos mixtos de II y III media, y I Liceo Italiano.
- El sistema debe asignar Educación Física a las horas finales del turno, independientemente del grupo.

## 6. Interfaces Externas

### 6.1. Interfaces de Usuario

- El sistema debe proporcionar una interfaz web para entrada de datos (ej. disponibilidad), visualización (ej. horarios) y manipulación de entidades (ej. edición de materias).

### 6.2. Interfaces de Software

- El sistema debe interactuar con PostgreSQL para almacenamiento y recuperación de datos.
- El sistema debe integrarse con pgAdmin para gestión de la base de datos.
- El sistema debe operar en un entorno Docker.

### 6.3. Interfaces de Comunicación

- El sistema debe usar protocolos HTTP/HTTPS para comunicación cliente-servidor.

### 6.4. Interfaces de Hardware

- El sistema debe operar en la infraestructura de hardware de Docker (servidores, red, almacenamiento).

## 7. Supuestos y Dependencias

### 7.1. Supuestos

- Los usuarios tendrán acceso a dispositivos con navegador web y conexión a Internet.
- La información inicial (disponibilidad, grupos, materias) será completa y oportuna.
- Las pautas de inspección de ANEP serán estables y accesibles.

### 7.2. Dependencias

- La generación de horarios depende de la calidad de las restricciones de entrada.
- La edición de horarios depende de la usabilidad de la interfaz.
- El despliegue depende de la configuración de Docker.

## 8. Verificación de Requisitos

- Cada requerimiento funcional será verificado mediante pruebas de aceptación definidas en un plan de pruebas.
- Los requerimientos no funcionales serán validados con métricas (ej. tiempo de respuesta, disponibilidad) durante pruebas de estrés y uso real.

## 9. Apéndices/Anexos — Glosario

- **Porcentaje de margen**: Relación entre horas disponibles y horas asignadas de un docente.
- **Observaciones**: Comentarios registrados por docentes sobre su disponibilidad o preferencias.
- **Prioridad**: Criterio de ordenación basado en trabajo en otro liceo o porcentaje de margen.
- **Pautas de inspección específicas**: Reglas de ANEP para la distribución de clases (ver enlace proporcionado).