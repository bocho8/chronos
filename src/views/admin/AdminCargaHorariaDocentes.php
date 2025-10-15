<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/Translation.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../components/LanguageSwitcher.php';
require_once __DIR__ . '/../../components/Sidebar.php';
require_once __DIR__ . '/../../models/Database.php';

$languageSwitcher = new LanguageSwitcher();
$languageSwitcher->handleLanguageChange();

AuthHelper::requireRole('ADMIN');

if (!AuthHelper::checkSessionTimeout()) {
    header("Location: /src/views/login.php?message=session_expired");
    exit();
}

$db = new Database();
$query = "
    SELECT 
        d.id_docente,
        CONCAT(u.nombre, ' ', u.apellido) as nombre,
        COUNT(h.id_horario) as horas,
        STRING_AGG(DISTINCT m.nombre, ', ') as materias,
        STRING_AGG(DISTINCT g.nombre, ', ') as grupos
    FROM docente d
    INNER JOIN usuario u ON d.id_usuario = u.id_usuario
    LEFT JOIN horario h ON d.id_docente = h.id_docente
    LEFT JOIN materia m ON h.id_materia = m.id_materia
    LEFT JOIN grupo g ON h.id_grupo = g.id_grupo
    GROUP BY d.id_docente, u.nombre, u.apellido
    ORDER BY horas DESC, nombre ASC
";

$stmt = $db->getConnection()->prepare($query);
$stmt->execute();
$docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processedTeachers = [];
foreach ($docentes as $teacher) {
    $processedTeachers[] = [
        'id' => $teacher['id_docente'],
        'name' => $teacher['nombre'],
        'hours' => (int)$teacher['horas'],
        'subjects' => $teacher['materias'] ? array_filter(explode(', ', $teacher['materias'])) : [],
        'groups' => $teacher['grupos'] ? array_filter(explode(', ', $teacher['grupos'])) : []
    ];
}

$totalTeachers = count($processedTeachers);
$totalHours = array_sum(array_column($processedTeachers, 'hours'));
$averageHours = $totalTeachers > 0 ? round($totalHours / $totalTeachers, 1) : 0;
$overloadedCount = count(array_filter($processedTeachers, function($t) { return $t['hours'] > 20; }));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('teacher_workload'); ?> - Chronos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../components/Sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="flex-1 px-6 py-6">
            <div class="max-w-6xl mx-auto">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Carga Horaria por Docente</h1>
                    <p class="text-gray-600">Análisis de distribución de horas y materias</p>
                </div>
                
                <!-- Controles -->
                <div class="bg-white rounded-lg shadow border p-4 mb-6">
                    <div class="flex justify-between items-center">
                        <div class="flex gap-3">
                            <input type="text" 
                                   id="searchInput" 
                                   placeholder="Buscar docente, materia o grupo..."
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm w-80">
                            <select id="filterSelect" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="all">Todos</option>
                                <option value="overloaded">Sobrecargados</option>
                                <option value="underloaded">Poca Carga</option>
                                <option value="optimal">Carga Óptima</option>
                            </select>
                        </div>
                        <button id="exportBtn" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                            <i class="fas fa-download mr-2"></i>Exportar
                        </button>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $totalTeachers; ?></div>
                        <div class="text-sm text-blue-700">Total Docentes</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $totalHours; ?></div>
                        <div class="text-sm text-green-700">Horas Totales</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $averageHours; ?></div>
                        <div class="text-sm text-purple-700">Promedio</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-red-600"><?php echo $overloadedCount; ?></div>
                        <div class="text-sm text-red-700">Sobrecarga</div>
                    </div>
                </div>
                
                <!-- Lista de docentes -->
                <div class="bg-white rounded-lg shadow border">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Docentes</h3>
                    </div>
                    <div id="teachersList" class="divide-y divide-gray-200">
                        <?php foreach ($processedTeachers as $teacher): ?>
                            <?php
                            $isOverloaded = $teacher['hours'] > 20;
                            $isUnderloaded = $teacher['hours'] < 10;
                            $isOptimal = $teacher['hours'] >= 10 && $teacher['hours'] <= 20;
                            
                            $statusColor = 'text-green-600';
                            $statusText = 'Óptima';
                            $cardBg = 'bg-green-50';
                            
                            if ($isOverloaded) {
                                $statusColor = 'text-red-600';
                                $statusText = 'Sobrecarga';
                                $cardBg = 'bg-red-50';
                            } elseif ($isUnderloaded) {
                                $statusColor = 'text-yellow-600';
                                $statusText = 'Poca Carga';
                                $cardBg = 'bg-yellow-50';
                            }
                            ?>
                            <div class="p-4 hover:bg-gray-50 teacher-card" 
                                 data-name="<?php echo htmlspecialchars($teacher['name']); ?>"
                                 data-hours="<?php echo $teacher['hours']; ?>"
                                 data-subjects="<?php echo htmlspecialchars(implode(',', $teacher['subjects'])); ?>"
                                 data-groups="<?php echo htmlspecialchars(implode(',', $teacher['groups'])); ?>"
                                 data-status="<?php echo $isOverloaded ? 'overloaded' : ($isUnderloaded ? 'underloaded' : 'optimal'); ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($teacher['name']); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <strong>Materias:</strong> <?php echo htmlspecialchars(implode(', ', $teacher['subjects'])); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <strong>Grupos:</strong> <?php echo htmlspecialchars(implode(', ', $teacher['groups'])); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold <?php echo $statusColor; ?>"><?php echo $teacher['hours']; ?>h</div>
                                        <div class="text-sm <?php echo $statusColor; ?>"><?php echo $statusText; ?></div>
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mt-2">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo min(($teacher['hours'] / 20) * 100, 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>

        const searchInput = document.getElementById('searchInput');
        const filterSelect = document.getElementById('filterSelect');
        const teachersList = document.getElementById('teachersList');
        const teacherCards = document.querySelectorAll('.teacher-card');

        function filterTeachers() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterValue = filterSelect.value;
            
            teacherCards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const subjects = card.dataset.subjects.toLowerCase();
                const groups = card.dataset.groups.toLowerCase();
                const status = card.dataset.status;
                
                const matchesSearch = name.includes(searchTerm) || 
                                    subjects.includes(searchTerm) || 
                                    groups.includes(searchTerm);
                
                const matchesFilter = filterValue === 'all' || status === filterValue;
                
                card.style.display = (matchesSearch && matchesFilter) ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterTeachers);
        filterSelect.addEventListener('change', filterTeachers);

        document.getElementById('exportBtn').addEventListener('click', function() {
            const visibleCards = Array.from(teacherCards).filter(card => card.style.display !== 'none');
            const data = visibleCards.map(card => ({
                nombre: card.dataset.name,
                horas: card.dataset.hours,
                materias: card.dataset.subjects,
                grupos: card.dataset.groups,
                estado: card.dataset.status
            }));
            
            const csv = [
                ['Nombre', 'Horas', 'Materias', 'Grupos', 'Estado'],
                ...data.map(row => [row.nombre, row.horas, row.materias, row.grupos, row.estado])
            ].map(row => row.join(',')).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'carga_horaria_docentes.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        });
    </script>
</body>
</html>
