<?php
// Move all PHP logic to the top before any HTML output
      // Include required files
      require_once __DIR__ . '/../models/Database.php';
      require_once __DIR__ . '/../models/Auth.php';
      
      $errors = [];
      $ci = '';
      $password = '';
      $role = '';
      $loginMessage = '';
      
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
          try {
            // Load database configuration
            $dbConfig = require __DIR__ . '/../config/database.php';
            
            // Initialize database and auth
            $database = new Database($dbConfig);
            $auth = new Auth($database->getConnection());
            
            // Attempt authentication
            $user = $auth->authenticate($ci, $password, $role);
            
            if ($user) {
              // Start session and store user data
              session_start();
              $_SESSION['user'] = $user;
              $_SESSION['logged_in'] = true;
              
              // Redirect based on role
              $redirectUrl = getRedirectUrl($role);
              header("Location: $redirectUrl");
              exit();
            } else {
              $errors['auth'] = 'C.I, contraseña o rol incorrectos';
            }
            
          } catch (Exception $e) {
            $errors['system'] = 'Error del sistema. Por favor, intente más tarde.';
            error_log("Login error: " . $e->getMessage());
          }
        }
      }
      
      // Helper function to get redirect URL based on role
      function getRedirectUrl($role) {
        switch ($role) {
          case 'ADMIN':
            return '/src/views/admin/';
          case 'DIRECTOR':
            return '/src/views/director/';
          case 'COORDINADOR':
            return '/src/views/coordinador/';
          case 'DOCENTE':
            return '/src/views/docente/';
          case 'PADRE':
            return '/src/views/padre/';
          default:
            return '/src/views/login.php';
        }
      }
      ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de Sesión | SIM</title>
  <link rel="stylesheet" href="/css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="m-0 font-['Merriweather_Sans'] bg-gray-100 text-blue-900">
  <header class="bg-blue-900 text-white h-[70px] min-h-[70px] flex items-center justify-between px-8 box-border">
    <div class="flex items-center gap-2.5">
      <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center font-bold text-blue-900 text-lg border-2 border-blue-900 box-border">
        SIM
      </div>
      <div class="text-lg font-bold leading-tight tracking-tight">
        Scuola<br>Italiana di<br>Montevideo
      </div>
    </div>
    <div class="text-3xl font-bold flex-1 text-center tracking-wide">Sistema de Horarios SIM</div>
    <div class="w-11 h-11 flex flex-col justify-center gap-2 cursor-pointer items-end" style="visibility:hidden;">
      <span class="block h-1 w-full bg-white rounded-sm"></span>
      <span class="block h-1 w-full bg-white rounded-sm"></span>
      <span class="block h-1 w-full bg-white rounded-sm"></span>
    </div>
  </header>
  <div class="min-h-[calc(100vh-70px)] bg-gray-800 flex items-center justify-center">
    <section class="bg-gray-200 rounded-[32px] p-12 pb-8 w-[420px] shadow-lg flex flex-col items-center">
      <h2 class="text-blue-900 text-[1.7rem] font-bold mb-6">Inicio de Sesión</h2>
      
      <form method="POST" autocomplete="off" id="loginForm" class="w-full">
        <label for="ci" class="text-base text-blue-900 font-medium mb-1 mt-2.5 self-start block">C.I</label>
        <input type="text" id="ci" name="ci" placeholder="C.I" autocomplete="off" 
               value="<?php echo htmlspecialchars($ci); ?>"
               class="w-full px-4 py-3 rounded-lg border-[1.5px] border-gray-300 text-base mb-4 bg-white text-blue-900 box-border focus:border-blue-900 <?php echo isset($errors['ci']) ? 'input-error' : ''; ?>">
        <div class="error-message text-red-600 text-sm mt-1" id="ciError">
          <?php echo isset($errors['ci']) ? htmlspecialchars($errors['ci']) : ''; ?>
        </div>
        
        <label for="password" class="text-base text-blue-900 font-medium mb-1 mt-2.5 self-start block">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="Contraseña" autocomplete="off"
               class="w-full px-4 py-3 rounded-lg border-[1.5px] border-gray-300 text-base mb-4 bg-white text-blue-900 box-border focus:border-blue-900 <?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
        <div class="error-message text-red-600 text-sm mt-1" id="passwordError">
          <?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : ''; ?>
        </div>
        
        <button type="submit" class="w-full bg-blue-900 text-white border-none rounded-lg py-3 text-base font-bold mb-3 mt-2 cursor-pointer transition-colors duration-200 hover:bg-blue-800">Iniciar Sesión</button>
        
        <div class="w-full flex justify-between items-center mt-0">
          <a class="text-blue-900 underline text-[0.97rem] cursor-pointer mr-2" href="#">¿Olvidaste tu contraseña?</a>
          <select name="role" id="role" class="px-4 py-3 rounded-lg border-[1.5px] border-gray-300 text-base bg-white text-blue-900 box-border focus:border-blue-900 <?php echo isset($errors['role']) ? 'input-error' : ''; ?>">
            <option value="">Roles</option>
            <option value="ADMIN" <?php echo $role === 'ADMIN' ? 'selected' : ''; ?>>Admin</option>
            <option value="DIRECTOR" <?php echo $role === 'DIRECTOR' ? 'selected' : ''; ?>>Director</option>
            <option value="COORDINADOR" <?php echo $role === 'COORDINADOR' ? 'selected' : ''; ?>>Coordinador</option>
            <option value="DOCENTE" <?php echo $role === 'DOCENTE' ? 'selected' : ''; ?>>Docente</option>
            <option value="PADRE" <?php echo $role === 'PADRE' ? 'selected' : ''; ?>>Padre/Madre</option>
          </select>
        </div>
        <div class="error-message text-red-600 text-sm mt-1" id="roleError">
          <?php echo isset($errors['role']) ? htmlspecialchars($errors['role']) : ''; ?>
        </div>
        
        <?php if (isset($errors['auth'])): ?>
          <div class="text-red-600 mb-4 p-2 bg-red-100 rounded text-center">
            <?php echo htmlspecialchars($errors['auth']); ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($errors['system'])): ?>
          <div class="text-red-600 mb-4 p-2 bg-red-100 rounded text-center">
            <?php echo htmlspecialchars($errors['system']); ?>
          </div>
        <?php endif; ?>
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