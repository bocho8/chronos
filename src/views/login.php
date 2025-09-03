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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sistema de Horarios SIM — Inicio de Sesión</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            navy: '#1f366d',
            bg: '#f5f6f8',
            card: '#d9d9d9',
            muted: '#475569',
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <style type="text/css">
    .select__icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      width: 0;
      height: 0;
      border-left: 6px solid transparent;
      border-right: 6px solid transparent;
      border-top: 8px solid #111827;
    }
    .hamburger span {
      width: 26px;
      height: 3px;
      background: #fff;
      display: block;
      border-radius: 2px;
    }
    .input-error {
      border-color: #e53e3e;
    }
    .input-success {
      border-color: #38a169;
    }
    .error-message {
      color: #e53e3e;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: none;
    }
  </style>
</head>
<body class="bg-bg font-sans text-[#0f172a]">
  <!-- BARRA SUPERIOR -->
  <header class="bg-navy text-white h-18 flex items-center justify-center">
    <div class="w-full grid grid-cols-3 items-center px-4">
      <!-- IZQUIERDA -->
      <div class="flex items-center gap-3 justify-self-start">
        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center border-2 border-navy box-border overflow-hidden">
          <img src="/upload/LogoScuola.png" alt="Logo Scuola Italiana" class="w-full h-full object-contain">
        </div>
        <span class="text-base font-semibold">Scuola Italiana di Montevideo</span>
      </div>

      <!-- CENTRO -->
      <h1 class="m-0 text-center text-xl md:text-[22px] font-bold">Sistema de Horarios SIM</h1>

      <!-- DERECHA -->
      <button class="w-11 h-11 grid place-content-center gap-1.5 bg-transparent border-0 cursor-pointer justify-self-end hamburger" aria-label="Menú">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- CONTENEDOR CENTRAL -->
  <main class="flex items-center justify-center min-h-[calc(100vh-72px)] p-6">
    <section class="bg-card rounded-3xl p-10 md:px-16 md:py-10 w-full max-w-[500px] shadow-lg" aria-labelledby="titulo-login">
      <h2 id="titulo-login" class="text-center text-2xl md:text-[28px] font-extrabold text-navy mb-6">Inicio de Sesión</h2>

      <form method="POST" autocomplete="off" id="loginForm" class="flex flex-col gap-5">
        <!-- C.I -->
        <label class="flex flex-col gap-1.5">
          <span class="font-semibold">C.I</span>
          <input type="text" id="ci" name="ci" placeholder="C.I" autocomplete="off" 
                 value="<?php echo htmlspecialchars($ci); ?>"
                 class="h-[42px] px-3 py-2.5 border border-gray-300 rounded-lg bg-white font-sans <?php echo isset($errors['ci']) ? 'input-error' : ''; ?>">
          <div class="error-message text-red-600 text-sm mt-1" id="ciError">
            <?php echo isset($errors['ci']) ? htmlspecialchars($errors['ci']) : ''; ?>
          </div>
        </label>

        <!-- Contraseña -->
        <label class="flex flex-col gap-1.5">
          <span class="font-semibold">Contraseña</span>
          <input type="password" id="password" name="password" placeholder="Contraseña" autocomplete="off"
                 class="h-[42px] px-3 py-2.5 border border-gray-300 rounded-lg bg-white font-sans <?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
          <div class="error-message text-red-600 text-sm mt-1" id="passwordError">
            <?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : ''; ?>
          </div>
        </label>

        <!-- Selección de Rol -->
        <label class="flex flex-col gap-1.5">
          <span class="font-semibold">Seleccione su rol:</span>
          <div class="relative">
            <select name="role" id="role" class="appearance-none w-full h-[42px] px-3 py-2.5 pr-10 border border-gray-300 rounded-lg bg-white font-sans text-gray-900 <?php echo isset($errors['role']) ? 'input-error' : ''; ?>">
              <option value="">Seleccione un rol</option>
              <option value="ADMIN" <?php echo $role === 'ADMIN' ? 'selected' : ''; ?>>Administrador</option>
              <option value="DIRECTOR" <?php echo $role === 'DIRECTOR' ? 'selected' : ''; ?>>Director</option>
              <option value="COORDINADOR" <?php echo $role === 'COORDINADOR' ? 'selected' : ''; ?>>Coordinador</option>
              <option value="DOCENTE" <?php echo $role === 'DOCENTE' ? 'selected' : ''; ?>>Docente</option>
              <option value="PADRE" <?php echo $role === 'PADRE' ? 'selected' : ''; ?>>Padre/Madre</option>
            </select>
            <span class="select__icon" aria-hidden="true"></span>
          </div>
          <div class="error-message text-red-600 text-sm mt-1" id="roleError">
            <?php echo isset($errors['role']) ? htmlspecialchars($errors['role']) : ''; ?>
          </div>
        </label>

        <?php if (isset($errors['auth'])): ?>
          <div class="text-red-600 p-2 bg-red-100 rounded text-center">
            <?php echo htmlspecialchars($errors['auth']); ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($errors['system'])): ?>
          <div class="text-red-600 p-2 bg-red-100 rounded text-center">
            <?php echo htmlspecialchars($errors['system']); ?>
          </div>
        <?php endif; ?>

        <!-- Botón -->
        <button type="submit" class="h-11 px-4 py-2.5 rounded-lg border-0 font-bold cursor-pointer bg-navy text-white w-full hover:bg-[#142852]">Iniciar Sesión</button>

        <!-- Link para olvidar contraseña -->
        <div class="mt-1.5 flex items-center justify-end">
          <a class="text-navy no-underline font-semibold text-sm hover:underline" href="#" tabindex="0">¿Olvidaste tu contraseña?</a>
        </div>
      </form>
    </section>
  </main>

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
        if (role === '' || role === 'Seleccione un rol') {
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