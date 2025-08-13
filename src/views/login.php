<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de Sesión | SIM</title>
  <link rel="stylesheet" href="/css/estilos.css">
  <style>
    /* Ajuste visual para el logo placeholder */
    .logo-placeholder {
      width: 48px;
      height: 48px;
      background: #fff;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #22397A;
      font-size: 1.1rem;
      border: 2px solid #22397A;
      box-sizing: border-box;
    }
    .header-logo-text {
      font-size: 1.1rem;
      font-weight: bold;
      line-height: 1.1;
      letter-spacing: 0.01em;
    }
    
    /* Estilos para mensajes de error */
    .error-message {
      color: #dc3545;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: none;
    }
    
    .input-error {
      border-color: #dc3545 !important;
    }
    
    .input-success {
      border-color: #28a745 !important;
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-logo">
      <div class="logo-placeholder">SIM</div>
      <div class="header-logo-text">
        Scuola<br>Italiana di<br>Montevideo
      </div>
    </div>
    <div class="header-title">Sistema de Horarios SIM</div>
    <div class="menu-icon" style="visibility:hidden;">
      <span></span><span></span><span></span>
    </div>
  </header>
  <div class="login-bg">
    <section class="card login-card">
      <h2>Inicio de Sesión</h2>
      
      <?php
      $errors = [];
      $ci = '';
      $password = '';
      $role = '';
      
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ci = trim($_POST['ci'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        
        if (empty($ci)) {
          $errors['ci'] = 'El C.I es obligatorio';
        } elseif (!preg_match('/^\d{7,8}$/', $ci)) {
          // Patrón regex: ^\d{7,8}$
          // ^ = inicio de la cadena
          // \d = cualquier dígito del 0-9
          // {7,8} = entre 7 y 8 caracteres
          // $ = fin de la cadena
          // Valida que el CI tenga exactamente 7 u 8 dígitos numéricos
          $errors['ci'] = 'El C.I debe tener 7 u 8 dígitos numéricos';
        }
        
        if (empty($password)) {
          $errors['password'] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 6) {
          $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if (empty($role) || $role === 'Roles') {
          $errors['role'] = 'Debe seleccionar un rol';
        }
        
        if (empty($errors)) {
          // Aquí iría la lógica de autenticación
          // Por ahora solo mostramos un mensaje de éxito
          echo '<div style="color: #28a745; margin-bottom: 1rem; padding: 0.5rem; background: #d4edda; border-radius: 4px;">Datos válidos. Procesando login...</div>';
        }
      }
      ?>
      
      <form method="POST" autocomplete="off" id="loginForm">
        <label for="ci">C.I</label>
        <input type="text" id="ci" name="ci" placeholder="C.I" autocomplete="off" 
               value="<?php echo htmlspecialchars($ci); ?>"
               class="<?php echo isset($errors['ci']) ? 'input-error' : ''; ?>">
        <div class="error-message" id="ciError">
          <?php echo isset($errors['ci']) ? htmlspecialchars($errors['ci']) : ''; ?>
        </div>
        
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="Contraseña" autocomplete="off"
               class="<?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
        <div class="error-message" id="passwordError">
          <?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : ''; ?>
        </div>
        
        <button type="submit">Iniciar Sesión</button>
        
        <div class="flex" style="justify-content:space-between;align-items:center;">
          <a class="link" href="#">¿Olvidaste tu contraseña?</a>
          <select name="role" id="role" class="<?php echo isset($errors['role']) ? 'input-error' : ''; ?>">
            <option value="">Roles</option>
            <option value="Admin" <?php echo $role === 'Admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="Coordinador" <?php echo $role === 'Coordinador' ? 'selected' : ''; ?>>Coordinador</option>
            <option value="Docente" <?php echo $role === 'Docente' ? 'selected' : ''; ?>>Docente</option>
            <option value="Padre/Madre" <?php echo $role === 'Padre/Madre' ? 'selected' : ''; ?>>Padre/Madre</option>
            <option value="Director" <?php echo $role === 'Director' ? 'selected' : ''; ?>>Director</option>
          </select>
        </div>
        <div class="error-message" id="roleError">
          <?php echo isset($errors['role']) ? htmlspecialchars($errors['role']) : ''; ?>
        </div>
      </form>
    </section>
  </div>

  <script>
    // Validación JavaScript del lado del cliente
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('loginForm');
      const ciInput = document.getElementById('ci');
      const passwordInput = document.getElementById('password');
      const roleSelect = document.getElementById('role');
      
      const ciError = document.getElementById('ciError');
      const passwordError = document.getElementById('passwordError');
      const roleError = document.getElementById('roleError');
      
      /**
       * Función para mostrar errores de validación en los campos del formulario
       * @param {HTMLElement} input - Elemento de entrada que contiene el error
       * @param {HTMLElement} errorElement - Elemento donde se mostrará el mensaje de error
       * @param {string} message - Mensaje de error a mostrar
       */
      function showError(input, errorElement, message) {
        input.classList.add('input-error');
        input.classList.remove('input-success');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
      }
      
      /**
       * Función para mostrar éxito en la validación de los campos del formulario
       * @param {HTMLElement} input - Elemento de entrada que se validó correctamente
       * @param {HTMLElement} errorElement - Elemento de error que se ocultará
       */
      function showSuccess(input, errorElement) {
        input.classList.remove('input-error');
        input.classList.add('input-success');
        errorElement.style.display = 'none';
      }
      
      /**
       * Función para validar el formato del C.I (Cédula de Identidad)
       * @param {string} ci - Valor del C.I a validar
       * @returns {string} - Mensaje de error vacío si es válido, o mensaje de error si no es válido
       */
      function validateCI(ci) {
        if (ci.trim() === '') {
          return 'El C.I es obligatorio';
        }
        if (!/^\d{7,8}$/.test(ci.trim())) {
          return 'El C.I debe tener 7 u 8 dígitos numéricos';
        }
        return '';
      }
      
      /**
       * Función para validar la contraseña del usuario
       * @param {string} password - Contraseña a validar
       * @returns {string} - Mensaje de error vacío si es válida, o mensaje de error si no es válida
       */
      function validatePassword(password) {
        if (password.trim() === '') {
          return 'La contraseña es obligatoria';
        }
        if (password.length < 6) {
          return 'La contraseña debe tener al menos 6 caracteres';
        }
        return '';
      }
      
      /**
       * Función para validar la selección del rol del usuario
       * @param {string} role - Rol seleccionado a validar
       * @returns {string} - Mensaje de error vacío si es válido, o mensaje de error si no es válido
       */
      function validateRole(role) {
        if (role === '' || role === 'Roles') {
          return 'Debe seleccionar un rol';
        }
        return '';
      }
      
      // Event listeners para validación en tiempo real
      /**
       * Event listener para validar el C.I cuando el usuario sale del campo (evento blur)
       * Realiza validación inmediata y muestra/oculta errores según corresponda
       */
      ciInput.addEventListener('blur', function() {
        const error = validateCI(this.value);
        if (error) {
          showError(this, ciError, error);
        } else {
          showSuccess(this, ciError);
        }
      });
      
      /**
       * Event listener para validar la contraseña cuando el usuario sale del campo (evento blur)
       * Realiza validación inmediata y muestra/oculta errores según corresponda
       */
      passwordInput.addEventListener('blur', function() {
        const error = validatePassword(this.value);
        if (error) {
          showError(this, passwordError, error);
        } else {
          showSuccess(this, passwordError);
        }
      });
      
      /**
       * Event listener para validar el rol cuando el usuario cambia la selección (evento change)
       * Realiza validación inmediata y muestra/oculta errores según corresponda
       */
      roleSelect.addEventListener('change', function() {
        const error = validateRole(this.value);
        if (error) {
          showError(this, roleError, error);
        } else {
          showSuccess(this, roleError);
        }
      });
      
      /**
       * Event listener para validar todo el formulario antes del envío (evento submit)
       * Previene el envío si hay errores de validación y muestra alerta al usuario
       * @param {Event} e - Evento de envío del formulario
       */
      form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Validar CI
        const ciErrorMsg = validateCI(ciInput.value);
        if (ciErrorMsg) {
          showError(ciInput, ciError, ciErrorMsg);
          hasErrors = true;
        } else {
          showSuccess(ciInput, ciError);
        }
        
        // Validar contraseña
        const passwordErrorMsg = validatePassword(passwordInput.value);
        if (passwordErrorMsg) {
          showError(passwordInput, passwordError, passwordErrorMsg);
          hasErrors = true;
        } else {
          showSuccess(passwordInput, passwordError);
        }
        
        // Validar rol
        const roleErrorMsg = validateRole(roleSelect.value);
        if (roleErrorMsg) {
          showError(roleSelect, roleError, roleErrorMsg);
          hasErrors = true;
        } else {
          showSuccess(roleSelect, roleError);
        }
        
        // Si hay errores, prevenir el envío del formulario
        if (hasErrors) {
          e.preventDefault();
          alert('Por favor, corrija los errores antes de continuar.');
        }
      });
      
      // Limpiar errores cuando el usuario empiece a escribir/seleccionar
      /**
       * Event listener para limpiar errores del C.I mientras el usuario escribe (evento input)
       * Si el campo tenía error y ahora es válido, se muestra el estado de éxito
       */
      ciInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
          const error = validateCI(this.value);
          if (!error) {
            showSuccess(this, ciError);
          }
        }
      });
      
      /**
       * Event listener para limpiar errores de la contraseña mientras el usuario escribe (evento input)
       * Si el campo tenía error y ahora es válido, se muestra el estado de éxito
       */
      passwordInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
          const error = validatePassword(this.value);
          if (!error) {
            showSuccess(this, passwordError);
          }
        }
      });
    });
  </script>
</body>
</html> 