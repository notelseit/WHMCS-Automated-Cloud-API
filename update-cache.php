<?php
/**
 * Cache Update Script for Hetzner VPS Module
 * 
 * This script updates the API cache data
 * Can be run via cron job or manually
 */

// Include WHMCS configuration
require_once(__DIR__ . '/../../configuration.php');
require_once(__DIR__ . '/../hetznervps/hetznervps.php');

/**
 * Load API token from WHMCS configuration
 */
function getApiTokenFromConfig()
{
    try {
        // Connect to WHMCS database
        $pdo = new PDO(
            "mysql:host=" . $db_host . ";dbname=" . $db_name,
            $db_username,
            $db_password
        );
        
        // Get API token from module configuration
        $stmt = $pdo->prepare("
            SELECT setting, value 
            FROM tblproductconfigoptions pco
            JOIN tblproductconfiglinks pcl ON pco.gid = pcl.gid
            JOIN tblproducts p ON pcl.pid = p.id
            WHERE p.servertype = 'hetznervps' AND pco.optionname = 'api_token'
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['value'] : null;
        
    } catch (Exception $e) {
        error_log("Error getting API token: " . $e->getMessage());
        return null;
    }
}

/**
 * Update all cached data
 */
function updateAllCache()
{
    $apiToken = getApiTokenFromConfig();
    
    if (!$apiToken) {
        echo "Error: No API token found in configuration\n";
        return false;
    }
    
    echo "Starting cache update with API token: " . substr($apiToken, 0, 10) . "...\n";
    
    // Clear existing cache
    $cachePattern = __DIR__ . '/../hetznervps/cache/hetzner_*.cache';
    $cacheFiles = glob($cachePattern);
    
    foreach ($cacheFiles as $file) {
        if (unlink($file)) {
            echo "Cleared cache file: " . basename($file) . "\n";
        }
    }
    
    // Update server types
    echo "Updating server types...\n";
    $serverTypes = hetznervps_getServerTypes($apiToken);
    if ($serverTypes) {
        echo "Server types updated: " . count($serverTypes) . " types found\n";
    } else {
        echo "Warning: Could not update server types\n";
    }
    
    // Update locations
    echo "Updating locations...\n";
    $locations = hetznervps_getLocations($apiToken);
    if ($locations) {
        echo "Locations updated: " . count($locations) . " locations found\n";
    } else {
        echo "Warning: Could not update locations\n";
    }
    
    // Update images
    echo "Updating images...\n";
    $images = hetznervps_getImages($apiToken);
    if ($images) {
        echo "Images updated: " . count($images) . " images found\n";
    } else {
        echo "Warning: Could not update images\n";
    }
    
    echo "Cache update completed\n";
    return true;
}

/**
 * Display cache status
 */
function displayCacheStatus()
{
    $cacheDir = __DIR__ . '/../hetznervps/cache/';
    $cacheFiles = ['server_types', 'locations', 'images'];
    
    echo "\n=== Cache Status ===\n";
    
    foreach ($cacheFiles as $cacheKey) {
        $cacheFile = $cacheDir . 'hetzner_' . $cacheKey . '.cache';
        
        if (file_exists($cacheFile)) {
            $cacheData = unserialize(file_get_contents($cacheFile));
            $expires = date('Y-m-d H:i:s', $cacheData['expires']);
            $itemCount = count($cacheData['data']);
            
            echo sprintf("%-15s: %d items, expires %s\n", ucfirst($cacheKey), $itemCount, $expires);
        } else {
            echo sprintf("%-15s: Not cached\n", ucfirst($cacheKey));
        }
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Command line execution
    echo "Hetzner VPS Module Cache Updater\n";
    echo "================================\n\n";
    
    if (isset($argv[1]) && $argv[1] === 'status') {
        displayCacheStatus();
    } else {
        updateAllCache();
        displayCacheStatus();
    }
} else {
    // Web execution (for testing only)
    header('Content-Type: text/plain');
    echo "Hetzner VPS Module Cache Updater\n";
    echo "================================\n\n";
    
    if (isset($_GET['action']) && $_GET['action'] === 'status') {
        displayCacheStatus();
    } else {
        updateAllCache();
        displayCacheStatus();
    }
}
?>