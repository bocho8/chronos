<?php
/**
 * Script to update existing BSL 1.1 license headers to the improved format
 * 
 * This script replaces the existing simple license headers with the more
 * comprehensive format that includes Change Date and Change License information.
 */

// Configuration
$config = [
    'project_root' => dirname(__DIR__),
    'licensor' => 'Agustín Roizen',
    'current_year' => date('Y'),
    'license_name' => 'Business Source License 1.1',
    'license_url' => 'https://github.com/bocho8/chronos/blob/main/LICENSE',
    'exclude_dirs' => [
        'node_modules',
        'vendor',
        'backups',
        'logs',
        '.git',
        'tests'
    ],
    'exclude_files' => [
        'styles.css', // Generated CSS file
        'package-lock.json',
        'composer.lock'
    ],
    'file_extensions' => [
        'php' => 'php',
        'js' => 'js',
        'css' => 'css'
    ]
];

// New improved license header templates
$newHeaders = [
    'php' => "<?php
/**
 * Copyright (C) {$config['current_year']} {$config['licensor']}
 * This software is licensed under the {$config['license_name']}. 
 * The license text is included in the LICENSE file or at {$config['license_url']}.
 * Change Date:          Three years from first public distribution
 * Change License:       Apache License, Version 2.0
 */

",
    'js' => "/**
 * Copyright (C) {$config['current_year']} {$config['licensor']}
 * This software is licensed under the {$config['license_name']}. 
 * The license text is included in the LICENSE file or at {$config['license_url']}.
 * Change Date:          Three years from first public distribution
 * Change License:       Apache License, Version 2.0
 */

",
    'css' => "/**
 * Copyright (C) {$config['current_year']} {$config['licensor']}
 * This software is licensed under the {$config['license_name']}. 
 * The license text is included in the LICENSE file or at {$config['license_url']}.
 * Change Date:          Three years from first public distribution
 * Change License:       Apache License, Version 2.0
 */

"
];

/**
 * Check if a file has the old simple license header
 */
function hasOldLicenseHeader($filePath, $fileType) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }
    
    // Check for the old simple format
    $lines = explode("\n", $content);
    $firstLines = array_slice($lines, 0, 10);
    $firstContent = implode("\n", $firstLines);
    
    return strpos($firstContent, 'Copyright (c)') !== false && 
           strpos($firstContent, 'Business Source License') !== false &&
           strpos($firstContent, 'Change Date') === false; // Old format doesn't have Change Date
}

/**
 * Update license header in a file
 */
function updateLicenseHeader($filePath, $fileType, $newHeader) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }
    
    // For PHP files, replace the old header
    if ($fileType === 'php') {
        // Pattern to match old header: <?php followed by /** ... */ followed by blank lines
        $pattern = '/^<\?php\s*\/\*\*.*?\*\/\s*\n\s*\n/ms';
        $content = preg_replace($pattern, $newHeader, $content, 1);
    } else {
        // For JS/CSS files, replace the old header
        $pattern = '/^\/\*\*.*?\*\/\s*\n\s*\n/ms';
        $content = preg_replace($pattern, $newHeader, $content, 1);
    }
    
    return file_put_contents($filePath, $content) !== false;
}

/**
 * Get file extension
 */
function getFileExtension($filePath) {
    return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
}

/**
 * Check if directory should be excluded
 */
function shouldExcludeDir($dirPath, $excludeDirs) {
    $relativePath = str_replace('\\', '/', $dirPath);
    foreach ($excludeDirs as $excludeDir) {
        if (strpos($relativePath, '/' . $excludeDir . '/') !== false || 
            strpos($relativePath, $excludeDir . '/') === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Check if file should be excluded
 */
function shouldExcludeFile($fileName, $excludeFiles) {
    return in_array($fileName, $excludeFiles);
}

/**
 * Process a single file
 */
function processFile($filePath, $config, $newHeaders) {
    $extension = getFileExtension($filePath);
    
    // Check if file type is supported
    if (!isset($config['file_extensions'][$extension])) {
        return ['status' => 'skipped', 'reason' => 'unsupported file type'];
    }
    
    $fileType = $config['file_extensions'][$extension];
    
    // Check if file has old license header
    if (!hasOldLicenseHeader($filePath, $fileType)) {
        return ['status' => 'skipped', 'reason' => 'no old header to update'];
    }
    
    // Update license header
    if (updateLicenseHeader($filePath, $fileType, $newHeaders[$fileType])) {
        return ['status' => 'success', 'reason' => 'license header updated'];
    } else {
        return ['status' => 'error', 'reason' => 'failed to write file'];
    }
}

/**
 * Recursively scan directory for files
 */
function scanDirectory($dir, $config, $newHeaders, &$results) {
    if (!is_dir($dir)) {
        return;
    }
    
    // Check if directory should be excluded
    if (shouldExcludeDir($dir, $config['exclude_dirs'])) {
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($filePath)) {
            scanDirectory($filePath, $config, $newHeaders, $results);
        } elseif (is_file($filePath)) {
            // Check if file should be excluded
            if (shouldExcludeFile($file, $config['exclude_files'])) {
                $results[] = [
                    'file' => $filePath,
                    'status' => 'skipped',
                    'reason' => 'excluded file'
                ];
                continue;
            }
            
            $result = processFile($filePath, $config, $newHeaders);
            $result['file'] = $filePath;
            $results[] = $result;
        }
    }
}

// Main execution
echo "Updating BSL 1.1 license headers to improved format...\n";
echo "Project root: {$config['project_root']}\n";
echo "Licensor: {$config['licensor']}\n";
echo "Year: {$config['current_year']}\n\n";

$results = [];

// Scan source directories
$sourceDirs = [
    $config['project_root'] . '/src',
    $config['project_root'] . '/public/js',
    $config['project_root'] . '/public/css',
    $config['project_root'] . '/public'
];

foreach ($sourceDirs as $dir) {
    if (is_dir($dir)) {
        echo "Scanning: $dir\n";
        scanDirectory($dir, $config, $newHeaders, $results);
    }
}

// Process root PHP files
$rootFiles = glob($config['project_root'] . '/*.php');
foreach ($rootFiles as $file) {
    $result = processFile($file, $config, $newHeaders);
    $result['file'] = $file;
    $results[] = $result;
}

// Summary
$summary = [
    'success' => 0,
    'skipped' => 0,
    'error' => 0
];

foreach ($results as $result) {
    $summary[$result['status']]++;
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Files processed: " . count($results) . "\n";
echo "Successfully updated headers: {$summary['success']}\n";
echo "Skipped: {$summary['skipped']}\n";
echo "Errors: {$summary['error']}\n\n";

if ($summary['error'] > 0) {
    echo "ERRORS:\n";
    foreach ($results as $result) {
        if ($result['status'] === 'error') {
            echo "- {$result['file']}: {$result['reason']}\n";
        }
    }
    echo "\n";
}

if ($summary['skipped'] > 0) {
    echo "SKIPPED FILES:\n";
    $skipReasons = [];
    foreach ($results as $result) {
        if ($result['status'] === 'skipped') {
            $reason = $result['reason'];
            if (!isset($skipReasons[$reason])) {
                $skipReasons[$reason] = [];
            }
            $skipReasons[$reason][] = basename($result['file']);
        }
    }
    
    foreach ($skipReasons as $reason => $files) {
        echo "- $reason (" . count($files) . " files): " . implode(', ', array_slice($files, 0, 10));
        if (count($files) > 10) {
            echo " ... and " . (count($files) - 10) . " more";
        }
        echo "\n";
    }
}

echo "\nLicense headers updated successfully!\n";

