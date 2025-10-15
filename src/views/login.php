<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../helpers/Translation.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../components/LanguageSwitcher.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();

$languageSwitcher->handleLanguageChange();

AuthHelper::redirectIfLoggedIn();

$errors = [];
$ci = '';
$password = '';
$role = '';
$loginMessage = '';
$successMessage = '';

if (isset($_GET['message'])) {
    $message = match ($_GET['message']) {
        'logout_success' => $translation->get('logout_success'),
        'logout_error' => $translation->get('logout_error'),
        'session_expired' => $translation->get('session_expired'),
        default => ''
    };
    
    if ($_GET['message'] === 'logout_success') {
        $successMessage = $message;
    } else {
        $errors['system'] = $message;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ci = trim($_POST['ci'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $role = trim($_POST['role'] ?? '');
  
  if (empty($ci)) {
    $errors['ci'] = $translation->get('validation_ci_required');
  } elseif (!preg_match('/^\d{7,8}$/', $ci)) {
    $errors['ci'] = $translation->get('validation_ci_format');
  }
  
  if (empty($password)) {
    $errors['password'] = $translation->get('validation_password_required');
  } elseif (strlen($password) < 6) {
    $errors['password'] = $translation->get('validation_password_length');
  }
  
  if (empty($role) || $role === 'Roles') {
    $errors['role'] = $translation->get('validation_role_required');
  }
  
  if (empty($errors)) {
    try {
      $dbConfig = require __DIR__ . '/../config/database.php';
      
      $database = new Database($dbConfig);
      $auth = new Auth($database->getConnection());
      
      $user = $auth->authenticate($ci, $password, $role);
      
      if ($user) {
        $_SESSION['user'] = $user;
        $_SESSION['logged_in'] = true;
        
        updateLastActivity();
        
        $redirectUrl = getRedirectUrl($role);
        header("Location: $redirectUrl");
        exit();
      } else {
        $errors['auth'] = $translation->get('validation_auth_failed');
      }
      
    } catch (Exception $e) {
      $errors['system'] = $translation->get('validation_system_error');
      error_log("Login error: " . $e->getMessage());
    }
  }
}

function getRedirectUrl($role) {
  return match ($role) {
    'ADMIN' => '/src/views/admin/',
    'DIRECTOR' => '/src/views/director/',
    'COORDINADOR' => '/src/views/coordinador/',
    'DOCENTE' => '/src/views/docente/',
    'PADRE' => '/src/views/padre/',
    default => '/src/views/login.php'
  };
}
?>
<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php _e('app_name'); ?> — <?php _e('login_title'); ?></title>
  <link rel="stylesheet" href="/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      width: 25px;
      height: 3px;
      background-color: white;
      margin: 3px 0;
      border-radius: 2px;
      transition: all 0.3s;
    }
  </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
  <header class="bg-navy text-white h-[60px] flex items-center">
    <div class="w-full grid grid-cols-3 items-center px-4 h-full">
      
      <div class="flex items-center gap-2.5">
          <img src="/assets\images/LogoScuola.png" alt="<?php _e('scuola_italiana'); ?>" class="h-9 w-auto">
          <span class="text-white font-semibold text-lg"><?php _e('scuola_italiana'); ?></span>
      </div>

      <h1 class="m-0 text-center text-xl md:text-[22px] font-bold"><?php _e('app_name'); ?></h1>

      <div class="flex items-center gap-2 justify-end">
        <?php echo $languageSwitcher->render(); ?>
        <button class="w-11 h-11 grid place-content-center gap-1.5 bg-transparent border-0 cursor-pointer hamburger" aria-label="Menú">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <main class="flex items-center justify-center min-h-[calc(100vh-60px)] p-6">
    <div class="w-full flex justify-center">
      <section class="bg-card rounded-3xl p-10 md:px-16 md:py-10 w-full max-w-[500px] shadow-lg" aria-labelledby="titulo-login">
      <h2 id="titulo-login" class="text-center text-2xl md:text-[28px] font-extrabold text-navy mb-6"><?php _e('login_title'); ?></h2>

      <form method="POST" autocomplete="off" id="loginForm" class="flex flex-col gap-5">

        <label class="flex flex-col gap-1.5">
          <span class="font-semibold"><?php _e('ci_label'); ?></span>
          <input type="text" id="ci" name="ci" placeholder="<?php _e('ci_placeholder'); ?>" autocomplete="off" 
                 value="<?php echo htmlspecialchars($ci); ?>"
                 class="h-[42px] px-3 py-2.5 border border-gray-300 rounded-lg bg-white font-sans <?php echo isset($errors['ci']) ? 'input-error' : ''; ?>">
          <div class="error-message text-red-600 text-sm mt-1" id="ciError">
            <?php echo isset($errors['ci']) ? htmlspecialchars($errors['ci']) : ''; ?>
          </div>
        </label>

        <label class="flex flex-col gap-1.5">
          <span class="font-semibold"><?php _e('password_label'); ?></span>
          <input type="password" id="password" name="password" placeholder="<?php _e('password_placeholder'); ?>" autocomplete="off"
                 class="h-[42px] px-3 py-2.5 border border-gray-300 rounded-lg bg-white font-sans <?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
          <div class="error-message text-red-600 text-sm mt-1" id="passwordError">
            <?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : ''; ?>
          </div>
        </label>

        <label class="flex flex-col gap-1.5">
          <span class="font-semibold"><?php _e('role_label'); ?></span>
          <div class="relative">
            <select name="role" id="role" class="appearance-none w-full h-[42px] px-3 py-2.5 pr-10 border border-gray-300 rounded-lg bg-white font-sans text-gray-900 <?php echo isset($errors['role']) ? 'input-error' : ''; ?>">
              <option value=""><?php _e('role_placeholder'); ?></option>
              <option value="ADMIN" <?php echo $role === 'ADMIN' ? 'selected' : ''; ?>><?php _e('role_admin'); ?></option>
              <option value="DIRECTOR" <?php echo $role === 'DIRECTOR' ? 'selected' : ''; ?>><?php _e('role_director'); ?></option>
              <option value="COORDINADOR" <?php echo $role === 'COORDINADOR' ? 'selected' : ''; ?>><?php _e('role_coordinator'); ?></option>
              <option value="DOCENTE" <?php echo $role === 'DOCENTE' ? 'selected' : ''; ?>><?php _e('role_teacher'); ?></option>
              <option value="PADRE" <?php echo $role === 'PADRE' ? 'selected' : ''; ?>><?php _e('role_parent'); ?></option>
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
        
        <?php if (!empty($successMessage)): ?>
          <div class="text-green-600 p-2 bg-green-100 rounded text-center">
            <?php echo htmlspecialchars($successMessage); ?>
          </div>
        <?php endif; ?>

        <button type="submit" class="h-11 px-4 py-2.5 rounded-lg border-0 font-bold cursor-pointer bg-navy text-white w-full hover:bg-[#142852]"><?php _e('login'); ?></button>

        <div class="mt-1.5 flex items-center justify-end">
          <a class="text-navy no-underline font-semibold text-sm hover:underline" href="#" tabindex="0"><?php _e('forgot_password'); ?></a>
        </div>
      </form>
      </section>
    </div>
  </main>

  <script>

    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('loginForm');
      const ciInput = document.getElementById('ci');
      const passwordInput = document.getElementById('password');
      const roleSelect = document.getElementById('role');
      
      const ciError = document.getElementById('ciError');
      const passwordError = document.getElementById('passwordError');
      const roleError = document.getElementById('roleError');
      
      /**
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
       * @param {HTMLElement} input - Elemento de entrada que se validó correctamente
       * @param {HTMLElement} errorElement - Elemento de error que se ocultará
       */
      function showSuccess(input, errorElement) {
        input.classList.remove('input-error');
        input.classList.add('input-success');
        errorElement.style.display = 'none';
      }

      const translations = {
        ci_required: '<?php _e('validation_ci_required'); ?>',
        ci_format: '<?php _e('validation_ci_format'); ?>',
        password_required: '<?php _e('validation_password_required'); ?>',
        password_length: '<?php _e('validation_password_length'); ?>',
        role_required: '<?php _e('validation_role_required'); ?>',
        correct_errors: '<?php _e('validation_correct_errors'); ?>'
      };
      
      /**
       * @param {string} ci - Valor del C.I a validar
       * @returns {string} - Mensaje de error vacío si es válido, o mensaje de error si no es válido
       */
      function validateCI(ci) {
        if (ci.trim() === '') {
          return translations.ci_required;
        }
        if (!/^\d{7,8}$/.test(ci.trim())) {
          return translations.ci_format;
        }
        return '';
      }
      
      /**
       * @param {string} password - Contraseña a validar
       * @returns {string} - Mensaje de error vacío si es válida, o mensaje de error si no es válida
       */
      function validatePassword(password) {
        if (password.trim() === '') {
          return translations.password_required;
        }
        if (password.length < 6) {
          return translations.password_length;
        }
        return '';
      }
      
      /**
       * @param {string} role - Rol seleccionado a validar
       * @returns {string} - Mensaje de error vacío si es válido, o mensaje de error si no es válido
       */
      function validateRole(role) {
        if (role === '' || role === '<?php _e('role_placeholder'); ?>') {
          return translations.role_required;
        }
        return '';
      }

      ciInput.addEventListener('blur', function() {
        const error = validateCI(this.value);
        if (error) {
          showError(this, ciError, error);
        } else {
          showSuccess(this, ciError);
        }
      });
      
      passwordInput.addEventListener('blur', function() {
        const error = validatePassword(this.value);
        if (error) {
          showError(this, passwordError, error);
        } else {
          showSuccess(this, passwordError);
        }
      });
      
      roleSelect.addEventListener('change', function() {
        const error = validateRole(this.value);
        if (error) {
          showError(this, roleError, error);
        } else {
          showSuccess(this, roleError);
        }
      });
      
      /**
       * @param {Event} e - Evento de envío del formulario
       */
      form.addEventListener('submit', function(e) {
        let hasErrors = false;

        const ciErrorMsg = validateCI(ciInput.value);
        if (ciErrorMsg) {
          showError(ciInput, ciError, ciErrorMsg);
          hasErrors = true;
        } else {
          showSuccess(ciInput, ciError);
        }

        const passwordErrorMsg = validatePassword(passwordInput.value);
        if (passwordErrorMsg) {
          showError(passwordInput, passwordError, passwordErrorMsg);
          hasErrors = true;
        } else {
          showSuccess(passwordInput, passwordError);
        }

        const roleErrorMsg = validateRole(roleSelect.value);
        if (roleErrorMsg) {
          showError(roleSelect, roleError, roleErrorMsg);
          hasErrors = true;
        } else {
          showSuccess(roleSelect, roleError);
        }

        if (hasErrors) {
          e.preventDefault();
          alert(translations.correct_errors);
        }
      });

      ciInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
          const error = validateCI(this.value);
          if (!error) {
            showSuccess(this, ciError);
          }
        }
      });
      
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