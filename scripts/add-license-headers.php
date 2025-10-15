<?php
/**
 * Script to add BSL 1.1 license headers to source files
 * 
 * This script scans the Chronos project and adds appropriate license headers
 * to all source files (PHP, JS, CSS) while skipping generated files and
 * files that already have license headers.
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

// License header templates
$headers = [
    'php' => "<?php
/**
 * Copyright (c) {$config['current_year']} {$config['licensor']}.
 * Distributed under the {$config['license_name']}
 * (See accompanying file LICENSE or copy at {$config['license_url']})
 */

",
    'js' => "/**
 * Copyright (c) {$config['current_year']} {$config['licensor']}.
 * Distributed under the {$config['license_name']}
 * (See accompanying file LICENSE or copy at {$config['license_url']})
 */

",
    'css' => "/**
 * Copyright (c) {$config['current_year']} {$config['licensor']}.
 * Distributed under the {$config['license_name']}
 * (See accompanying file LICENSE or copy at {$config['license_url']})
 */

"
];

/**
 * Check if a file already has a license header
 */
function hasLicenseHeader($filePath, $fileType) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }
    
    // Check for copyright notice in the first 20 lines
    $lines = explode("\n", $content);
    $firstLines = array_slice($lines, 0, 20);
    $firstContent = implode("\n", $firstLines);
    
    return strpos($firstContent, 'Copyright (c)') !== false && 
           strpos($firstContent, 'Business Source License') !== false;
}

/**
 * Add license header to a file
 */
function addLicenseHeader($filePath, $fileType, $header) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }
    
    // For PHP files, add after opening tag
    if ($fileType === 'php') {
        if (strpos($content, '<?php') === 0) {
            // Replace the opening tag and add header
            $content = preg_replace('/^<\?php\s*/', $header, $content, 1);
        } else {
            // Add header at the beginning
            $content = $header . $content;
        }
    } else {
        // For JS/CSS files, add at the beginning
        $content = $header . $content;
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
function processFile($filePath, $config, $headers) {
    $extension = getFileExtension($filePath);
    
    // Check if file type is supported
    if (!isset($config['file_extensions'][$extension])) {
        return ['status' => 'skipped', 'reason' => 'unsupported file type'];
    }
    
    $fileType = $config['file_extensions'][$extension];
    
    // Check if file already has license header
    if (hasLicenseHeader($filePath, $fileType)) {
        return ['status' => 'skipped', 'reason' => 'already has license header'];
    }
    
    // Add license header
    if (addLicenseHeader($filePath, $fileType, $headers[$fileType])) {
        return ['status' => 'success', 'reason' => 'license header added'];
    } else {
        return ['status' => 'error', 'reason' => 'failed to write file'];
    }
}

/**
 * Recursively scan directory for files
 */
function scanDirectory($dir, $config, $headers, &$results) {
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
            scanDirectory($filePath, $config, $headers, $results);
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
            
            $result = processFile($filePath, $config, $headers);
            $result['file'] = $filePath;
            $results[] = $result;
        }
    }
}

// Main execution
echo "Adding BSL 1.1 license headers to Chronos project files...\n";
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
        scanDirectory($dir, $config, $headers, $results);
    }
}

// Process root PHP files
$rootFiles = glob($config['project_root'] . '/*.php');
foreach ($rootFiles as $file) {
    $result = processFile($file, $config, $headers);
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
echo "Successfully added headers: {$summary['success']}\n";
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

echo "\nLicense headers added successfully!\n";
echo "You can re-run this script safely - it will skip files that already have headers.\n";
