<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Docente.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';

initSecureSession();

$translation = Translation::getInstance();
$languageSwitcher = new LanguageSwitcher();
$sidebar = new Sidebar('admin-docentes.php');

$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header('Location: /src/views/login.php');
    exit();
}

$dbConfig = require __DIR__ . '/../../config/database.php';
$database = new Database($dbConfig);

$docenteModel = new Docente($database->getConnection());
$docentes = $docenteModel->getAllDocentes();

// Load subjects and assignments data
require_once __DIR__ . '/../../models/Materia.php';
require_once __DIR__ . '/../../app/Models/Assignment.php';

$materiaModel = new Materia($database->getConnection());
$materias = $materiaModel->getAllMaterias();

$assignmentModel = new \App\Models\Assignment($database->getConnection());
$assignments = $assignmentModel->getAllAssignments();

function getUserInitials($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}

function getTeacherAssignments($teacherId, $assignments) {
    return array_filter($assignments, function($assignment) use ($teacherId) {
        return $assignment['id_docente'] == $teacherId;
    });
}
?>

<!DOCTYPE html>
<html lang="<?php echo $translation->getCurrentLanguage(); ?>"<?php echo $translation->isRTL() ? ' dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php _e('app_name'); ?> — <?php _e('teachers_management'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <?php echo Sidebar::getStyles(); ?>
  <style type="text/css">
    body {
      overflow-x: hidden;
    }
    .sidebar-link {
      position: relative;
      transition: all 0.3s;
    }
    .sidebar-link.active {
      background-color: #e4e6eb;
      font-weight: 600;
    }
    .sidebar-link.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background-color: #1f366d;
    }
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
      padding: 16px 20px;
      border-radius: 8px;
      color: white;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transform: translateX(100%);
      transition: transform 0.3s ease-in-out;
      max-width: 400px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .toast.show {
      transform: translateX(0);
    }
    .toast-success {
      background: linear-gradient(135deg, #10b981, #059669);
    }
    .toast-error {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    .toast-warning {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .toast-info {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
    }
    #toastContainer {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
    }
    
    /* Modal styles - removed conflicting general styles */

    #docenteModal {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      z-index: 10000 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      background-color: rgba(0, 0, 0, 0.2) !important;
      backdrop-filter: blur(8px) !important;
      -webkit-backdrop-filter: blur(8px) !important;
      padding: 1rem !important;
      width: 100vw !important;
      height: 100vh !important;
    }
    
    #docenteModal.hidden {
      display: none !important;
    }
    
    
#docenteModal .modal-content {
  position: relative !important;
  z-index: 10001 !important;
  background: white !important;
  border-radius: 12px !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
  max-height: 90vh !important;
  max-width: 500px !important;
  width: 100% !important;
  overflow-y: auto !important;
  margin: 0 auto !important;
  animation: modalSlideIn 0.3s ease-out !important;
  transform: none !important;
  top: auto !important;
  left: auto !important;
}

#docenteModal button[type="submit"],
#docenteModal button[type="button"],
#assignmentModal button[type="submit"],
#assignmentModal button[type="button"] {
  z-index: 10002 !important;
  position: relative !important;
  background-color: #1f366d !important;
  color: white !important;
}

/* Mejoras específicas para el modal de asignaciones */
#assignmentModal {
  backdrop-filter: blur(4px) !important;
}

#assignmentModal .modal-content {
  animation: modalSlideIn 0.3s ease-out !important;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive modal behavior */
@media (max-width: 640px) {
  #docenteModal {
    padding: 0.5rem !important;
  }
  
  #docenteModal .modal-content {
    max-height: 95vh !important;
    border-radius: 8px !important;
  }
}
  </style>
</head>
<body class="bg-bg font-sans text-gray-800 leading-relaxed">
    <div class="flex min-h-screen">
        <?php echo $sidebar->render(); ?>

    <!-- Main -->
    <main class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="bg-darkblue px-6 h-[60px] flex justify-between items-center shadow-sm border-b border-lightborder">
        <!-- Espacio para el botón de menú hamburguesa -->
        <div class="w-8"></div>
        
        <!-- Título centrado -->
        <div class="text-white text-xl font-semibold text-center"><?php _e('welcome'); ?>, <?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?> (<?php _e('role_admin'); ?>)</div>
        
        <!-- Contenedor de iconos a la derecha -->
        <div class="flex items-center">
                    <?php echo $languageSwitcher->render('', 'mr-4'); ?>
                    <button class="mr-4 p-2 rounded-full hover:bg-navy" title="<?php _e('notifications'); ?>">
                        <span class="text-white text-sm">🔔</span>
                    </button>
          
          <!-- User Menu Dropdown -->
          <div class="relative group">
            <button class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-darkblue font-semibold hover:bg-gray-100 transition-colors" id="userMenuButton">
              <?php echo htmlspecialchars(AuthHelper::getUserInitials()); ?>
            </button>
            
            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block" id="userMenu">
              <div class="px-4 py-2 text-sm text-gray-700 border-b">
                <div class="font-medium"><?php echo htmlspecialchars(AuthHelper::getUserDisplayName()); ?></div>
                <div class="text-gray-500"><?php _e('role_admin'); ?></div>
              </div>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="profileLink">
                <span class="inline mr-2 text-xs">👤</span>
                <?php _e('profile'); ?>
              </a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsLink">
                <span class="inline mr-2 text-xs">⚙</span>
                <?php _e('settings'); ?>
              </a>
              <div class="border-t"></div>
              <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" id="logoutButton">
                <span class="inline mr-2 text-xs">🚪</span>
                <?php _e('logout'); ?>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Contenido principal - Centrado -->
      <section class="flex-1 px-6 py-8">
        <div class="max-w-6xl mx-auto">
          <div class="mb-8">
            <h2 class="text-darktext text-2xl font-semibold mb-2.5"><?php _e('teachers_management'); ?></h2>
            <p class="text-muted mb-6 text-base"><?php _e('teachers_management_description'); ?></p>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-lightborder mb-8">
            <!-- Header de la tabla -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
              <h3 class="font-medium text-darktext"><?php _e('teachers'); ?></h3>
              <div class="flex gap-2">
                <button onclick="showAddDocenteModal()" class="py-2 px-4 border border-gray-300 rounded cursor-pointer font-medium transition-all text-sm bg-white text-gray-700 hover:bg-gray-50 flex items-center">
                  <span class="mr-1 text-sm">+</span>
                  <?php _e('add_teacher'); ?>
                </button>
              </div>
            </div>

            <!-- Lista de docentes -->
            <div class="divide-y divide-gray-200">
              <?php if (!empty($docentes)): ?>
                <?php foreach ($docentes as $docente): ?>
                  <article class="flex items-center justify-between p-4 transition-colors hover:bg-lightbg">
                    <div class="flex items-center">
                      <div class="avatar w-10 h-10 rounded-full bg-darkblue mr-3 flex items-center justify-center flex-shrink-0 text-white font-semibold">
                        <?php echo getUserInitials($docente['nombre'], $docente['apellido']); ?>
                      </div>
                      <div class="meta">
                        <div class="font-semibold text-darktext mb-1">
                          <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                        </div>
                        <div class="text-muted text-sm">
                          <?php echo htmlspecialchars($docente['email']); ?> • 
                          CI: <?php echo htmlspecialchars($docente['cedula']); ?>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <button onclick="manageAssignments(<?php echo $docente['id_docente']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>')" 
                              class="text-green-600 hover:text-green-800 text-sm font-medium transition-colors">
                        <?php _e('manage_assignments'); ?>
                      </button>
                      <button onclick="editDocente(<?php echo $docente['id_docente']; ?>)" 
                              class="text-darkblue hover:text-navy text-sm font-medium transition-colors">
                        <?php _e('edit'); ?>
                      </button>
                      <button onclick="deleteDocente(<?php echo $docente['id_docente']; ?>, '<?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>')" 
                              class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                        <?php _e('delete'); ?>
                      </button>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-8 text-center">
                  <div class="text-gray-500 text-lg mb-2"><?php _e('no_teachers_found'); ?></div>
                  <div class="text-gray-400 text-sm"><?php _e('add_first_teacher'); ?></div>
                </div>
              <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Modal para agregar/editar docente -->
  <div id="docenteModal" class="hidden">
    <div class="modal-content p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"><?php _e('add_teacher'); ?></h3>
                <button onclick="closeDocenteModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="text-sm">×</span>
                </button>
            </div>

            <form id="docenteForm" onsubmit="handleFormSubmit(event)" class="space-y-4">
                <input type="hidden" id="id_docente" name="id_docente" value="">
                
                <div>
                    <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('cedula'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="cedula" name="cedula" required maxlength="8" pattern="[0-9]{8}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="12345678" aria-describedby="cedulaError cedulaHelp">
                    <p id="cedulaHelp" class="text-xs text-gray-500 mt-1"><?php _e('cedula_help'); ?></p>
                    <p id="cedulaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('name'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="<?php _e('name_placeholder'); ?>" aria-describedby="nombreError">
                    <p id="nombreError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('lastname'); ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="apellido" name="apellido" required maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="<?php _e('lastname_placeholder'); ?>" aria-describedby="apellidoError">
                    <p id="apellidoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('email'); ?></label>
                    <input type="email" id="email" name="email" maxlength="150"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="usuario@ejemplo.com" aria-describedby="emailError">
                    <p id="emailError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('phone'); ?></label>
                    <input type="tel" id="telefono" name="telefono" maxlength="20" pattern="[0-9\s\-\+\(\)]+"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                           placeholder="099123456" aria-describedby="telefonoError telefonoHelp">
                    <p id="telefonoHelp" class="text-xs text-gray-500 mt-1"><?php _e('phone_help'); ?></p>
                    <p id="telefonoError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>
                
                <div>
                    <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-2"><?php _e('password'); ?> <span id="passwordRequired" class="text-red-500" style="display: none;">*</span></label>
                    <div class="relative">
                        <input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="255"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:ring-darkblue focus:border-darkblue sm:text-sm"
                               placeholder="<?php _e('password_placeholder'); ?>" aria-describedby="contrasenaError contrasenaHelp">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors" aria-label="<?php _e('toggle_password_visibility'); ?>">
                            <svg id="passwordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p id="contrasenaHelp" class="text-xs text-gray-500 mt-1"><?php _e('password_help'); ?></p>
                    <p id="contrasenaError" class="text-xs text-red-600 mt-1" role="alert" aria-live="polite"></p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeDocenteModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('cancel'); ?>
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-darkblue hover:bg-navy focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-darkblue">
                        <?php _e('save'); ?>
                    </button>
                </div>
            </form>
    </div>
  </div>

  <!-- Modal para gestionar asignaciones -->
  <div id="assignmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 20px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #111827;"><?php _e('manage_assignments'); ?></h3>
        <button onclick="closeAssignmentModal()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 5px;">×</button>
      </div>

      <div style="margin-bottom: 20px;">
        <h4 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 500; color: #374151;" id="teacherNameDisplay"></h4>
        
        <!-- Formulario para agregar nueva asignación -->
        <div style="background: #f9fafb; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
          <h5 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 500; color: #374151;"><?php _e('add_new_assignment'); ?></h5>
          <form id="assignmentForm" onsubmit="handleAssignmentSubmit(event)" style="display: flex; gap: 10px;">
            <input type="hidden" id="assignment_teacher_id" name="teacher_id" value="">
            
            <select id="assignment_subject_id" name="subject_id" required
                    style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;">
              <option value=""><?php _e('select_subject'); ?></option>
              <?php foreach ($materias as $materia): ?>
                <option value="<?php echo $materia['id_materia']; ?>">
                  <?php echo htmlspecialchars($materia['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            
            <button type="submit" 
                    style="padding: 8px 16px; background: #059669; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; white-space: nowrap;">
              <?php _e('assign'); ?>
            </button>
          </form>
        </div>

        <!-- Lista de asignaciones actuales -->
        <div>
          <h5 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 500; color: #374151;"><?php _e('current_assignments'); ?></h5>
          <div id="assignmentsList" style="display: flex; flex-direction: column; gap: 8px;">
            <!-- Las asignaciones se cargarán dinámicamente -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer">    </div>

    <script src="/js/toast.js"></script>
    <script>
        let isEditMode = false;

        function showAddDocenteModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = '<?php _e('add_teacher'); ?>';
            document.getElementById('docenteForm').reset();
            
            document.getElementById('contrasena').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            
            clearErrors();
            document.getElementById('docenteModal').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('cedula').focus();
            }, 100);
        }

        function editDocente(id) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = '<?php _e('edit_teacher'); ?>';
            
            fetch(`/admin/teachers/${id}/edit`, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const teacher = data.data.teacher;
                    document.getElementById('id_docente').value = teacher.id_docente;
                    document.getElementById('cedula').value = teacher.cedula;
                    document.getElementById('nombre').value = teacher.nombre;
                    document.getElementById('apellido').value = teacher.apellido;
                    document.getElementById('email').value = teacher.email;
                    document.getElementById('telefono').value = teacher.telefono || '';
                    
                    document.getElementById('contrasena').required = false;
                    document.getElementById('contrasena').value = '';
                    document.getElementById('passwordRequired').style.display = 'none';
                    
                    clearErrors();
                    document.getElementById('docenteModal').classList.remove('hidden');
                    
                    setTimeout(() => {
                        document.getElementById('cedula').focus();
                    }, 100);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error cargando datos del docente', 'error');
            });
        }

        function deleteDocente(id, nombre) {
            const confirmMessage = `¿Está seguro de que desea eliminar al docente "${nombre}"?`;
            if (confirm(confirmMessage)) {
                fetch(`/admin/teachers/${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Docente eliminado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error eliminando docente', 'error');
                });
            }
        }

        function closeDocenteModal() {
            const modal = document.getElementById('docenteModal');
            modal.classList.add('hidden');
            clearErrors();
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            if (!validateDocenteForm()) {
                showToast('<?php _e('please_correct_errors'); ?>', 'error');
                return;
            }
            
            const url = isEditMode 
                ? `/admin/teachers/${document.getElementById('id_docente').value}`
                : '/admin/teachers';
            const method = isEditMode ? 'PUT' : 'POST';
            
            let requestBody;
            let contentType;
            
            if (isEditMode) {
                const formData = new FormData(e.target);
                const urlEncodedData = new URLSearchParams();
                for (let [key, value] of formData.entries()) {
                    urlEncodedData.append(key, value);
                }
                requestBody = urlEncodedData.toString();
                contentType = 'application/x-www-form-urlencoded';
            } else {
                requestBody = new FormData(e.target);
                contentType = null;
            }
            
            const fetchOptions = {
                method: method,
                body: requestBody
            };
            
            if (contentType) {
                fetchOptions.headers = {
                    'Content-Type': contentType
                };
            }
            
            fetch(url, fetchOptions)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeDocenteModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (data.data && typeof data.data === 'object') {
                        Object.keys(data.data).forEach(field => {
                            showFieldError(field, data.data[field]);
                        });
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('<?php _e('error_processing_request'); ?>', 'error');
            });
        }

        function clearErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
            
            const inputElements = document.querySelectorAll('input, select, textarea');
            inputElements.forEach(element => {
                element.classList.remove('error-input');
            });
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('contrasena');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                passwordInput.type = 'password';
                passwordIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function validateDocenteForm() {
            let isValid = true;
            clearErrors();
            
            const cedula = document.getElementById('cedula').value.trim();
            if (!cedula) {
                showFieldError('cedula', '<?php _e('cedula_required'); ?>');
                isValid = false;
            } else if (!/^[0-9]{8}$/.test(cedula)) {
                showFieldError('cedula', '<?php _e('cedula_invalid_format'); ?>');
                isValid = false;
            }
            
            const nombre = document.getElementById('nombre').value.trim();
            if (!nombre) {
                showFieldError('nombre', '<?php _e('name_required'); ?>');
                isValid = false;
            } else if (nombre.length < 2) {
                showFieldError('nombre', '<?php _e('name_too_short'); ?>');
                isValid = false;
            }
            
            const apellido = document.getElementById('apellido').value.trim();
            if (!apellido) {
                showFieldError('apellido', '<?php _e('lastname_required'); ?>');
                isValid = false;
            } else if (apellido.length < 2) {
                showFieldError('apellido', '<?php _e('lastname_too_short'); ?>');
                isValid = false;
            }
            
            const email = document.getElementById('email').value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('email', '<?php _e('email_invalid_format'); ?>');
                isValid = false;
            }
            
            const telefono = document.getElementById('telefono').value.trim();
            if (telefono && !/^[0-9\s\-\+\(\)]+$/.test(telefono)) {
                showFieldError('telefono', '<?php _e('phone_invalid_format'); ?>');
                isValid = false;
            }
            
            const contrasena = document.getElementById('contrasena').value;
            if (!isEditMode && (!contrasena || contrasena.length < 8)) {
                showFieldError('contrasena', '<?php _e('password_required_min_length'); ?>');
                isValid = false;
            } else if (contrasena && contrasena.length < 8) {
                showFieldError('contrasena', '<?php _e('password_min_length'); ?>');
                isValid = false;
            }
            
            return isValid;
        }
        
        function showFieldError(fieldName, message) {
            const errorElement = document.getElementById(fieldName + 'Error');
            const inputElement = document.getElementById(fieldName);
            
            if (errorElement) {
                errorElement.textContent = message;
            }
            
            if (inputElement) {
                inputElement.classList.add('error-input');
            }
        }

        document.getElementById('docenteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocenteModal();
            }
        });

        const togglePasswordBtn = document.getElementById('togglePassword');
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
        }

        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('<?php _e('confirm_logout'); ?>')) {
                window.location.href = '/src/controllers/LogoutController.php';
            }
        });

        // Assignment Management Functions
        function manageAssignments(teacherId, teacherName) {
            document.getElementById('assignment_teacher_id').value = teacherId;
            document.getElementById('teacherNameDisplay').textContent = teacherName;
            loadTeacherAssignments(teacherId);
            
            const modal = document.getElementById('assignmentModal');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
            document.getElementById('assignmentForm').reset();
        }

        function loadTeacherAssignments(teacherId) {
            fetch(`/admin/assignments?teacher_id=${teacherId}`)
                .then(response => response.json())
                .then(data => {
                    const assignmentsList = document.getElementById('assignmentsList');
                    if (data.success && data.data.length > 0) {
                        assignmentsList.innerHTML = data.data.map(assignment => `
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px;">
                                <div>
                                    <span style="font-weight: 500; color: #111827;">${assignment.materia_nombre}</span>
                                </div>
                                <button onclick="removeAssignment('${assignment.id}', ${teacherId})" 
                                        style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                    <?php _e('remove'); ?>
                                </button>
                            </div>
                        `).join('');
                    } else {
                        assignmentsList.innerHTML = '<p style="color: #6b7280; font-size: 14px; text-align: center; padding: 20px;"><?php _e('no_assignments_found'); ?></p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading assignments:', error);
                    if (typeof showToast === 'function') {
                        showToast('<?php _e('error_loading_assignments'); ?>', 'error');
                    } else {
                        alert('<?php _e('error_loading_assignments'); ?>');
                    }
                });
        }

        function handleAssignmentSubmit(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const teacherId = formData.get('teacher_id');
            const subjectId = formData.get('subject_id');
            
            fetch('/admin/assignments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    subject_id: subjectId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('<?php _e('assignment_created_successfully'); ?>', 'success');
                    } else {
                        alert('<?php _e('assignment_created_successfully'); ?>');
                    }
                    const teacherId = document.getElementById('assignment_teacher_id').value;
                    loadTeacherAssignments(teacherId);
                    document.getElementById('assignmentForm').reset();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || '<?php _e('error_creating_assignment'); ?>', 'error');
                    } else {
                        alert(data.message || '<?php _e('error_creating_assignment'); ?>');
                    }
                }
            })
            .catch(error => {
                console.error('Error creating assignment:', error);
                if (typeof showToast === 'function') {
                    showToast('<?php _e('error_creating_assignment'); ?>', 'error');
                } else {
                    alert('<?php _e('error_creating_assignment'); ?>');
                }
            });
        }

        function removeAssignment(assignmentId, teacherId) {
            if (confirm('<?php _e('confirm_remove_assignment'); ?>')) {
                fetch(`/admin/assignments/${assignmentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') {
                            showToast('<?php _e('assignment_removed_successfully'); ?>', 'success');
                        } else {
                            alert('<?php _e('assignment_removed_successfully'); ?>');
                        }
                        loadTeacherAssignments(teacherId);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || '<?php _e('error_removing_assignment'); ?>', 'error');
                        } else {
                            alert(data.message || '<?php _e('error_removing_assignment'); ?>');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error removing assignment:', error);
                    if (typeof showToast === 'function') {
                        showToast('<?php _e('error_removing_assignment'); ?>', 'error');
                    } else {
                        alert('<?php _e('error_removing_assignment'); ?>');
                    }
                });
            }
        }
    </script>
</body>
</html>
