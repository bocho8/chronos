# Gestión de Proyecto - Segunda Entrega

## Metodología de Desarrollo

### Enfoque Adoptado
- **Metodología:** Desarrollo Ágil con elementos de Scrum
- **Iteraciones:** Sprints de 2 semanas
- **Reuniones:** Daily standups, Sprint planning, Retrospectivas
- **Documentación:** Continua y actualizada

### Información del Proyecto
- **Nombre del Proyecto:** S.I.G.I.E Chronos E.S.R.E
- **Institución:** Liceo Italiano
- **Fecha de Entrega:** 28/07/2025
- **Equipo de Desarrollo:**
  - **Coordinador:** Juan Chapuis (5.630.283-4)
  - **Sub-Coordinador:** Agustin Roizen (6.339.592-9)
  - **Integrante:** Bruno Marino (5.707.620-6)

## Diagrama de Metodología de Desarrollo

```mermaid
graph TD
    A[Inicio del Proyecto] --> B[Sprint Planning]
    B --> C[Daily Standups]
    C --> D[Desarrollo]
    D --> E[Testing]
    E --> F[Sprint Review]
    F --> G[Sprint Retrospective]
    G --> H{¿Más Sprints?}
    H -->|Sí| B
    H -->|No| I[Release]
    I --> J[Monitoreo]
```

## Estructura del Equipo

### Roles y Responsabilidades

```mermaid
graph LR
    A[Product Owner] --> B[Requisitos]
    A --> C[Priorización]
    
    D[Scrum Master] --> E[Facilitación]
    D --> F[Remoción de Obstáculos]
    
    G[Desarrolladores] --> H[Desarrollo]
    G --> I[Testing]
    G --> J[Documentación]
    
    K[QA Tester] --> L[Testing]
    K --> M[Validación]
```

## Planificación del Proyecto

### Fases del Proyecto

```mermaid
gantt
    title Cronograma del Proyecto Chronos - Liceo Italiano
    dateFormat  YYYY-MM-DD
    section Primera Entrega
    Análisis y Diseño    :done, fase1, 2024-01-01, 2024-01-15
    section Segunda Entrega
    Desarrollo Backend   :active, fase2, 2024-01-16, 2024-02-15
    Modelo de Datos      :active, fase2b, 2024-01-16, 2024-02-15
    section Tercera Entrega
    Desarrollo Frontend  :fase3, 2024-02-16, 2024-03-15
    Integración          :fase3b, 2024-02-16, 2024-03-15
    section Cuarta Entrega
    Testing y Deploy     :fase4, 2024-03-16, 2024-03-30
    Entrega Final        :fase5, 2024-07-28, 2024-07-28
```

### Entregables por Fase

#### Primera Entrega: Análisis y Diseño
- [x] Especificación de requisitos (ESRE)
- [x] Modelo de datos conceptual
- [x] Arquitectura del sistema
- [x] Casos de uso
- [x] Diagramas UML

#### Segunda Entrega: Desarrollo Backend
- [x] Configuración de base de datos PostgreSQL
- [x] Modelos de datos (Usuario, Materia, Grupo, Horario, Asignacion)
- [x] Gestión de disponibilidad docente
- [x] Sistema de observaciones
- [x] Controladores API
- [x] Autenticación y autorización por roles
- [x] Validaciones de negocio (pautas ANEP)

#### Tercera Entrega: Desarrollo Frontend
- [ ] Interfaces de usuario por rol
- [ ] Generación automática de horarios
- [ ] Edición manual de horarios
- [ ] Gestión de disponibilidad docente
- [ ] Integración con backend
- [ ] Responsive design
- [ ] Validaciones del lado cliente

#### Cuarta Entrega: Testing y Deploy
- [ ] Pruebas unitarias
- [ ] Pruebas de integración
- [ ] Pruebas de usuario (60 usuarios concurrentes)
- [ ] Configuración Docker (PHP 8.3, PostgreSQL 16, Nginx)
- [ ] Documentación final
- [ ] Entrega final (28/07/2025)

## Gestión de Riesgos

### Matriz de Riesgos

```mermaid
quadrantChart
    title Matriz de Riesgos del Proyecto
    x-axis Bajo --> Alto
    y-axis Bajo --> Alto
    quadrant-1 Alto Impacto, Alta Probabilidad
    quadrant-2 Alto Impacto, Baja Probabilidad
    quadrant-3 Bajo Impacto, Alta Probabilidad
    quadrant-4 Bajo Impacto, Baja Probabilidad
    
    "Pérdida de datos" : [0.8, 0.9]
    "Retraso en entrega" : [0.6, 0.7]
    "Cambios de requisitos" : [0.4, 0.8]
    "Problemas de rendimiento" : [0.3, 0.6]
    "Falta de recursos" : [0.2, 0.4]
```

### Plan de Contingencia

#### Riesgos Críticos
1. **Pérdida de datos**
   - **Mitigación:** Respaldo automático diario
   - **Contingencia:** Procedimiento de recuperación documentado

2. **Retraso en entrega**
   - **Mitigación:** Buffer de tiempo en cronograma
   - **Contingencia:** Priorización de funcionalidades

3. **Cambios de requisitos**
   - **Mitigación:** Reuniones regulares con stakeholders
   - **Contingencia:** Proceso de cambio controlado

## Control de Calidad

### Proceso de Revisión de Código

```mermaid
flowchart TD
    A[Desarrollador] --> B[Push a Feature Branch]
    B --> C[Pull Request]
    C --> D[Revisión Automática]
    D --> E{Tests Pass?}
    E -->|No| F[Corregir Issues]
    F --> B
    E -->|Sí| G[Revisión Manual]
    G --> H{Approved?}
    H -->|No| I[Comentarios]
    I --> F
    H -->|Sí| J[Merge a Develop]
    J --> K[Deploy a Staging]
```

### Criterios de Aceptación

#### Funcionalidad
- [ ] Cumple con los requisitos especificados
- [ ] Maneja casos de error apropiadamente
- [ ] Validaciones de entrada implementadas
- [ ] Respuesta en tiempo aceptable

#### Código
- [ ] Sigue estándares de codificación
- [ ] Documentación actualizada
- [ ] Tests unitarios incluidos
- [ ] Sin código duplicado

#### Seguridad
- [ ] Validación de entrada
- [ ] Sanitización de datos
- [ ] Autenticación implementada
- [ ] Autorización verificada

## Métricas del Proyecto

### KPIs de Desarrollo
- **Velocidad del equipo:** [Story points por sprint]
- **Tiempo de resolución de bugs:** [Días promedio]
- **Cobertura de tests:** [Porcentaje]
- **Tiempo de deploy:** [Minutos]

### Métricas de Calidad
- **Bugs por funcionalidad:** [Número]
- **Tiempo de revisión de código:** [Horas]
- **Satisfacción del usuario:** [Puntuación 1-10]
- **Disponibilidad del sistema:** [Porcentaje]

## Comunicación del Proyecto

### Flujo de Comunicación

```mermaid
graph TD
    A[Stakeholders] --> B[Product Owner]
    B --> C[Scrum Master]
    C --> D[Equipo de Desarrollo]
    D --> E[QA Tester]
    E --> F[DevOps]
    F --> G[Producción]
    
    H[Daily Standup] --> D
    I[Sprint Review] --> A
    J[Retrospective] --> D
```

### Herramientas de Comunicación
- **Reuniones:** Microsoft Teams / Zoom
- **Documentación:** Confluence / Notion
- **Código:** GitHub / GitLab
- **Proyecto:** Azure DevOps / Jira
- **Comunicación:** Slack / Discord

## Documentación del Proyecto

### Tipos de Documentación
1. **Técnica**
   - Especificación de requisitos
   - Diseño de arquitectura
   - Documentación de API
   - Guías de instalación

2. **Funcional**
   - Manual de usuario
   - Casos de uso
   - Flujos de trabajo
   - Guías de administración

3. **Proyecto**
   - Plan de proyecto
   - Cronograma
   - Matriz de riesgos
   - Reportes de estado

### Estándares de Documentación
- **Formato:** Markdown para documentación técnica
- **Idioma:** Español para usuarios, Inglés para código
- **Versionado:** Controlado con Git
- **Actualización:** Continua y sincronizada con el código
