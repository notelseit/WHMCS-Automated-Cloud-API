<?php
/**
 * Installation and Configuration Helper for Hetzner VPS Module
 * 
 * This script helps with the initial setup and configuration of the module
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Module installation helper
 */
function hetznervps_install()
{
    $moduleDir = __DIR__;
    $requiredDirs = ['cache', 'logs', 'data', 'scripts'];
    
    echo "<h3>Hetzner VPS Module Installation</h3>";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    
    // Create required directories
    foreach ($requiredDirs as $dir) {
        $dirPath = $moduleDir . '/' . $dir;
        
        if (!is_dir($dirPath)) {
            if (mkdir($dirPath, 0755, true)) {
                echo "✓ Created directory: $dir<br>";
            } else {
                echo "✗ Failed to create directory: $dir<br>";
            }
        } else {
            echo "✓ Directory exists: $dir<br>";
        }
    }
    
    // Set file permissions
    $files = [
        'cache' => 0755,
        'logs' => 0755,
        'data' => 0755,
        'scripts/update_cache.php' => 0644,
        'scripts/update_cache.sh' => 0755
    ];
    
    foreach ($files as $file => $permission) {
        $filePath = $moduleDir . '/' . $file;
        if (file_exists($filePath)) {
            if (chmod($filePath, $permission)) {
                echo "✓ Set permissions for: $file (" . decoct($permission) . ")<br>";
            } else {
                echo "✗ Failed to set permissions for: $file<br>";
            }
        }
    }
    
    echo "<br><strong>Installation Steps:</strong><br>";
    echo "1. Configure your Hetzner API token in WHMCS product configuration<br>";
    echo "2. Set up cron job for cache updates: <code>0 */6 * * * " . $moduleDir . "/scripts/update_cache.sh</code><br>";
    echo "3. Test the connection using the 'Test Connection' button<br>";
    echo "4. Run initial cache update: <code>php " . $moduleDir . "/scripts/update_cache.php</code><br>";
    
    echo "</div>";
}

/**
 * Configuration validation
 */
function hetznervps_validateConfig($params)
{
    $errors = [];
    
    // Check API token
    if (empty($params['api_token'])) {
        $errors[] = "API Token is required";
    } elseif (strlen($params['api_token']) < 20) {
        $errors[] = "API Token appears to be invalid (too short)";
    }
    
    // Check default location
    $validLocations = ['fsn1', 'nbg1', 'hel1', 'ash', 'hil'];
    if (!empty($params['default_location']) && !in_array($params['default_location'], $validLocations)) {
        $errors[] = "Invalid default location. Valid options: " . implode(', ', $validLocations);
    }
    
    return $errors;
}

/**
 * System requirements check
 */
function hetznervps_checkRequirements()
{
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'Write Permission (cache)' => is_writable(__DIR__ . '/cache') || is_writable(__DIR__),
        'Write Permission (logs)' => is_writable(__DIR__ . '/logs') || is_writable(__DIR__),
        'Write Permission (data)' => is_writable(__DIR__ . '/data') || is_writable(__DIR__),
    ];
    
    echo "<h3>System Requirements Check</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Requirement</th><th>Status</th></tr>";
    
    $allPassed = true;
    foreach ($requirements as $requirement => $status) {
        $statusText = $status ? "<span style='color: green;'>✓ Pass</span>" : "<span style='color: red;'>✗ Fail</span>";
        echo "<tr><td>$requirement</td><td>$statusText</td></tr>";
        
        if (!$status) {
            $allPassed = false;
        }
    }
    
    echo "</table>";
    
    if ($allPassed) {
        echo "<p style='color: green;'><strong>All requirements passed!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>Some requirements failed. Please fix them before proceeding.</strong></p>";
    }
    
    return $allPassed;
}

/**
 * Generate configuration template
 */
function hetznervps_generateConfigTemplate()
{
    return [
        'api_token' => [
            'description' => 'Your Hetzner Cloud API Token',
            'example' => 'abc123def456...',
            'required' => true
        ],
        'default_location' => [
            'description' => 'Default server location',
            'example' => 'fsn1',
            'options' => ['fsn1', 'nbg1', 'hel1', 'ash', 'hil'],
            'required' => false
        ],
        'enable_backups' => [
            'description' => 'Enable backups by default',
            'type' => 'boolean',
            'default' => false,
            'required' => false
        ],
        'enable_monitoring' => [
            'description' => 'Enable monitoring by default', 
            'type' => 'boolean',
            'default' => false,
            'required' => false
        ]
    ];
}

// If accessed directly, show installation interface
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Hetzner VPS Module Setup</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .container { max-width: 800px; margin: 0 auto; }
            .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .error { color: red; }
            .success { color: green; }
            .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Hetzner VPS Module Setup</h1>
            
            <div class="section">
                <?php hetznervps_checkRequirements(); ?>
            </div>
            
            <div class="section">
                <?php hetznervps_install(); ?>
            </div>
            
            <div class="section">
                <h3>Configuration Template</h3>
                <p>Use these settings when configuring your WHMCS product:</p>
                
                <?php
                $template = hetznervps_generateConfigTemplate();
                foreach ($template as $key => $config) {
                    echo "<div style='margin-bottom: 15px;'>";
                    echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                    if ($config['required']) echo " <span style='color: red;'>*</span>";
                    echo "<br>";
                    echo "<em>" . $config['description'] . "</em><br>";
                    
                    if (isset($config['example'])) {
                        echo "Example: <code>" . $config['example'] . "</code><br>";
                    }
                    
                    if (isset($config['options'])) {
                        echo "Options: " . implode(', ', $config['options']) . "<br>";
                    }
                    echo "</div>";
                }
                ?>
            </div>
            
            <div class="section">
                <h3>Next Steps</h3>
                <ol>
                    <li>Copy the module files to your WHMCS modules/servers/hetznervps/ directory</li>
                    <li>Create a new product in WHMCS and select "Hetzner Cloud VPS (Auto-Configured)" as the module</li>
                    <li>Configure the module settings with your API token</li>
                    <li>Set up the cron job for automatic cache updates</li>
                    <li>Test the connection and create your first VPS</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>