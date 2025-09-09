# Chronos - Project Structure

This document describes the organized structure of the Chronos project.

## 📁 Directory Structure

```
chronos/
├── academic/                          # Academic deliverables and coursework
│   └── primera_entrega/              # First delivery materials
│       ├── CIBERSEGURIDAD/
│       ├── EMPRENDEDURISMO Y GESTION/
│       ├── GESTION DE PROYECTO UTULAB/
│       ├── INGENIERIA DE SOFTWARE/
│       ├── ITALIANO/
│       ├── PROGRAMACION FULLSTACK/
│       ├── SISTEMAS OPERATIVOS/
│       └── SOCIOLOGIA/
├── config/                           # Configuration files
│   └── environment/                  # Environment-specific configs
│       └── ngrok.env                 # Ngrok tunnel configuration
├── docker/                           # Docker configuration files
│   ├── nginx/                        # Nginx configuration
│   │   └── default.conf
│   ├── php/                          # PHP Docker configuration
│   │   └── Dockerfile
│   └── postgres/                     # PostgreSQL initialization
│       └── init.sql
├── docs/                             # Project documentation
│   ├── database/                     # Database-related documentation
│   │   └── database_schema.sql       # Database schema definition
│   └── PROJECT_STRUCTURE.md          # This file
├── public/                           # Web-accessible files
│   ├── assets/                       # Static assets
│   │   └── images/                   # Image files
│   │       └── LogoScuola.png
│   ├── css/                          # Compiled CSS files
│   │   └── styles.css
│   ├── js/                           # JavaScript files
│   │   └── menu.js
│   └── index.php                     # Main entry point
├── src/                              # Source code
│   ├── components/                   # Reusable components
│   │   └── LanguageSwitcher.php
│   ├── config/                       # Application configuration
│   │   ├── database.php
│   │   ├── session.php
│   │   └── translations.php
│   ├── controllers/                  # Request handlers
│   │   └── LogoutController.php
│   ├── helpers/                      # Utility functions
│   │   ├── AuthHelper.php
│   │   └── Translation.php
│   ├── lang/                         # Language files
│   │   ├── en.php
│   │   ├── es.php
│   │   └── it.php
│   ├── models/                       # Data models
│   │   ├── Auth.php
│   │   └── Database.php
│   ├── views/                        # View templates
│   │   ├── admin/
│   │   │   └── index.php
│   │   ├── error_404.php
│   │   ├── login.php
│   │   └── logout.php
│   └── tailwind.css                  # Tailwind CSS source
├── docker-compose.yml                # Docker services configuration
├── package.json                      # Node.js dependencies
├── package-lock.json                 # Node.js lock file
└── README.md                         # Project documentation
```

## 🎯 Organization Principles

### 1. **Separation of Concerns**
- **`src/`**: Contains all application source code
- **`public/`**: Contains only web-accessible files
- **`config/`**: Contains environment and deployment configurations
- **`docs/`**: Contains all project documentation

### 2. **Academic Work Separation**
- **`academic/`**: All coursework and academic deliverables are isolated from the main project code

### 3. **Asset Management**
- **`public/assets/`**: Organized static assets with subdirectories for different types
- **`public/assets/images/`**: All image files in one location

### 4. **Configuration Management**
- **`config/environment/`**: Environment-specific configuration files
- **`docker/`**: Docker-related configurations remain in their own directory

### 5. **Documentation Structure**
- **`docs/database/`**: Database-related documentation
- **`docs/`**: General project documentation

## 🔄 Migration Changes

The following changes were made during the reorganization:

1. **Academic Files**: Moved `primera_entrega/` to `academic/primera_entrega/`
2. **Assets**: Moved `public/upload/` to `public/assets/images/`
3. **Configuration**: Moved `docker/ngrok.env` to `config/environment/ngrok.env`
4. **Documentation**: Moved `docs/database_schema.sql` to `docs/database/database_schema.sql`
5. **Cleanup**: Removed empty `public/src/` directory

## 📝 Updated References

The following files were updated to reflect the new structure:
- `docker-compose.yml`: Updated ngrok.env path
- `README.md`: Updated database schema path

## 🚀 Benefits of This Organization

1. **Clarity**: Clear separation between different types of files
2. **Maintainability**: Easier to locate and manage files
3. **Scalability**: Structure supports future growth
4. **Best Practices**: Follows common PHP project organization patterns
5. **Academic Separation**: Keeps coursework separate from project code
