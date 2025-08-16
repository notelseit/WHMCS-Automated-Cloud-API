<?php
/**
 * WHMCS Hetzner VPS Module with Automated API Options
 * 
 * Automatically populates configuration options from Hetzner Cloud API
 * Reduces manual maintenance and ensures up-to-date server configurations
 * 
 * @author Your Name
 * @version 2.0.0
 * @link https://github.com/notelseit/WHMCS-HetznerVPS
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module meta data
 */
function hetznervps_MetaData()
{
    return array(
        'DisplayName' => 'Hetzner Cloud VPS (Auto-Configured)',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    );
}

/**
 * Configuration Options - Automatically populated from Hetzner API
 */
function hetznervps_ConfigOptions()
{
    // Base configuration options
    $configOptions = array(
        "api_token" => array(
            "FriendlyName" => "API Token",
            "Type" => "password",
            "Size" => "50",
            "Description" => "Your Hetzner Cloud API Token"
        ),
        "default_location" => array(
            "FriendlyName" => "Default Location",
            "Type" => "text",
            "Size" => "20",
            "Default" => "fsn1",
            "Description" => "Default location if not specified (fsn1, nbg1, hel1, ash, hil)"
        ),
        "enable_backups" => array(
            "FriendlyName" => "Enable Backups by Default",
            "Type" => "yesno",
            "Description" => "Enable automatic backups for new servers"
        ),
        "enable_monitoring" => array(
            "FriendlyName" => "Enable Monitoring by Default", 
            "Type" => "yesno",
            "Description" => "Enable monitoring for new servers"
        )
    );

    // Try to get dynamic options from API
    $dynamicOptions = hetznervps_getDynamicConfigOptions();
    
    // Merge with base options
    return array_merge($configOptions, $dynamicOptions);
}

/**
 * Get dynamic configuration options from Hetzner API
 */
function hetznervps_getDynamicConfigOptions()
{
    $dynamicOptions = array();
    
    // Get cached or fresh data from API
    $serverTypes = hetznervps_getServerTypes();
    $locations = hetznervps_getLocations();
    $images = hetznervps_getImages();
    
    // Server Type Options
    if (!empty($serverTypes)) {
        $serverTypeOptions = array();
        foreach ($serverTypes as $serverType) {
            $price = !empty($serverType['prices']) ? ' - €' . $serverType['prices'][0]['price_monthly']['gross'] . '/month' : '';
            $displayName = strtoupper($serverType['name']) . ' - ' . 
                          $serverType['cores'] . ' vCPU, ' . 
                          $serverType['memory'] . 'GB RAM, ' . 
                          $serverType['disk'] . 'GB SSD' . $price;
            $serverTypeOptions[] = $serverType['name'] . '|' . $displayName;
        }
        
        $dynamicOptions["server_type"] = array(
            "FriendlyName" => "Server Configuration",
            "Type" => "dropdown", 
            "Options" => implode(",", $serverTypeOptions),
            "Description" => "Server configuration (auto-updated from Hetzner API)"
        );
    }
    
    // Location Options
    if (!empty($locations)) {
        $locationOptions = array();
        foreach ($locations as $location) {
            $displayName = $location['city'] . ', ' . $location['country'] . ' (' . $location['name'] . ')';
            $locationOptions[] = $location['name'] . '|' . $displayName;
        }
        
        $dynamicOptions["location"] = array(
            "FriendlyName" => "Server Location",
            "Type" => "dropdown",
            "Options" => implode(",", $locationOptions),
            "Description" => "Server location (auto-updated from Hetzner API)"
        );
    }
    
    // OS Image Options  
    if (!empty($images)) {
        $imageOptions = array();
        foreach ($images as $image) {
            if ($image['status'] === 'available' && $image['type'] === 'system') {
                $displayName = $image['description'] ?: $image['name'];
                $imageOptions[] = $image['name'] . '|' . $displayName;
            }
        }
        
        $dynamicOptions["image"] = array(
            "FriendlyName" => "Operating System",
            "Type" => "dropdown",
            "Options" => implode(",", array_slice($imageOptions, 0, 20)), // Limit to 20 most common
            "Description" => "Operating system image (auto-updated from Hetzner API)"
        );
    }
    
    return $dynamicOptions;
}

/**
 * Get server types from Hetzner API (with caching)
 */
function hetznervps_getServerTypes($apiToken = null)
{
    // Try to get from cache first
    $cachedData = hetznervps_getFromCache('server_types');
    if ($cachedData) {
        return $cachedData;
    }
    
    if (!$apiToken) {
        return hetznervps_getFallbackServerTypes();
    }
    
    $response = hetznervps_makeAPICall('/server_types', $apiToken);
    
    if ($response && isset($response['server_types'])) {
        // Cache the response
        hetznervps_saveToCache('server_types', $response['server_types']);
        return $response['server_types'];
    }
    
    return hetznervps_getFallbackServerTypes();
}

/**
 * Get locations from Hetzner API (with caching)
 */
function hetznervps_getLocations($apiToken = null)
{
    $cachedData = hetznervps_getFromCache('locations');
    if ($cachedData) {
        return $cachedData;
    }
    
    if (!$apiToken) {
        return hetznervps_getFallbackLocations();
    }
    
    $response = hetznervps_makeAPICall('/locations', $apiToken);
    
    if ($response && isset($response['locations'])) {
        hetznervps_saveToCache('locations', $response['locations']);
        return $response['locations'];
    }
    
    return hetznervps_getFallbackLocations();
}

/**
 * Get images from Hetzner API (with caching)
 */
function hetznervps_getImages($apiToken = null)
{
    $cachedData = hetznervps_getFromCache('images');
    if ($cachedData) {
        return $cachedData;
    }
    
    if (!$apiToken) {
        return hetznervps_getFallbackImages();
    }
    
    $response = hetznervps_makeAPICall('/images?type=system&status=available', $apiToken);
    
    if ($response && isset($response['images'])) {
        hetznervps_saveToCache('images', $response['images']);
        return $response['images'];
    }
    
    return hetznervps_getFallbackImages();
}

/**
 * Make API call to Hetzner Cloud API
 */
function hetznervps_makeAPICall($endpoint, $apiToken, $method = 'GET', $data = null)
{
    $url = 'https://api.hetzner.cloud/v1' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'User-Agent: WHMCS-HetznerVPS/2.0'
    ));
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log API calls for debugging
    hetznervps_logAPICall($endpoint, $method, $httpCode, $error);
    
    if ($httpCode >= 200 && $httpCode < 300 && $response) {
        return json_decode($response, true);
    }
    
    return false;
}

/**
 * Cache management functions
 */
function hetznervps_saveToCache($key, $data, $expiration = 86400)
{
    $cacheData = array(
        'data' => $data,
        'expires' => time() + $expiration
    );
    
    $cacheFile = __DIR__ . '/cache/hetzner_' . $key . '.cache';
    $cacheDir = dirname($cacheFile);
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    file_put_contents($cacheFile, serialize($cacheData), LOCK_EX);
}

function hetznervps_getFromCache($key)
{
    $cacheFile = __DIR__ . '/cache/hetzner_' . $key . '.cache';
    
    if (!file_exists($cacheFile)) {
        return false;
    }
    
    $cacheData = unserialize(file_get_contents($cacheFile));
    
    if (!$cacheData || $cacheData['expires'] < time()) {
        unlink($cacheFile);
        return false;
    }
    
    return $cacheData['data'];
}

/**
 * Logging function
 */
function hetznervps_logAPICall($endpoint, $method, $httpCode, $error = '')
{
    $logEntry = date('Y-m-d H:i:s') . ' - ' . $method . ' ' . $endpoint . ' - HTTP ' . $httpCode;
    if ($error) {
        $logEntry .= ' - Error: ' . $error;
    }
    $logEntry .= PHP_EOL;
    
    $logFile = __DIR__ . '/logs/hetzner_api.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Fallback data when API is not available
 */
function hetznervps_getFallbackServerTypes()
{
    return array(
        array(
            'name' => 'cpx11',
            'description' => 'CPX11',
            'cores' => 2,
            'memory' => 2,
            'disk' => 40,
            'prices' => array(array('price_monthly' => array('gross' => '3.92')))
        ),
        array(
            'name' => 'cpx21',
            'description' => 'CPX21',
            'cores' => 3,
            'memory' => 4,
            'disk' => 80,
            'prices' => array(array('price_monthly' => array('gross' => '8.21')))
        ),
        array(
            'name' => 'cpx31',
            'description' => 'CPX31',
            'cores' => 4,
            'memory' => 8,
            'disk' => 160,
            'prices' => array(array('price_monthly' => array('gross' => '16.54')))
        )
    );
}

function hetznervps_getFallbackLocations()
{
    return array(
        array('name' => 'fsn1', 'city' => 'Falkenstein', 'country' => 'DE'),
        array('name' => 'nbg1', 'city' => 'Nuremberg', 'country' => 'DE'),
        array('name' => 'hel1', 'city' => 'Helsinki', 'country' => 'FI'),
        array('name' => 'ash', 'city' => 'Ashburn', 'country' => 'US'),
        array('name' => 'hil', 'city' => 'Hillsboro', 'country' => 'US')
    );
}

function hetznervps_getFallbackImages()
{
    return array(
        array('name' => 'ubuntu-20.04', 'description' => 'Ubuntu 20.04', 'status' => 'available', 'type' => 'system'),
        array('name' => 'ubuntu-22.04', 'description' => 'Ubuntu 22.04', 'status' => 'available', 'type' => 'system'),
        array('name' => 'debian-11', 'description' => 'Debian 11', 'status' => 'available', 'type' => 'system'),
        array('name' => 'centos-7', 'description' => 'CentOS 7', 'status' => 'available', 'type' => 'system'),
        array('name' => 'fedora-36', 'description' => 'Fedora 36', 'status' => 'available', 'type' => 'system')
    );
}

/**
 * Test connection to Hetzner API
 */
function hetznervps_TestConnection(array $params)
{
    try {
        $apiToken = $params['api_token'];
        
        if (empty($apiToken)) {
            return array('error' => 'API Token is required');
        }
        
        $response = hetznervps_makeAPICall('/server_types?per_page=1', $apiToken);
        
        if ($response && isset($response['server_types'])) {
            return array('success' => true);
        }
        
        return array('error' => 'Unable to connect to Hetzner Cloud API');
        
    } catch (Exception $e) {
        return array('error' => $e->getMessage());
    }
}

/**
 * Create a new VPS
 */
function hetznervps_CreateAccount(array $params)
{
    try {
        $apiToken = $params['api_token'];
        $serverName = $params['domain']; // Use domain field as server name
        $serverType = $params['configoption1'] ?: 'cpx11'; // server_type
        $location = $params['configoption2'] ?: $params['default_location'] ?: 'fsn1'; // location
        $image = $params['configoption3'] ?: 'ubuntu-20.04'; // image
        
        // Prepare server creation data
        $serverData = array(
            'name' => $serverName,
            'server_type' => $serverType,
            'location' => $location,
            'image' => $image,
            'start_after_create' => true,
            'public_net' => array('enable_ipv4' => true, 'enable_ipv6' => true)
        );
        
        // Add optional features
        if ($params['enable_backups'] === 'on') {
            $serverData['automount'] = true;
        }
        
        if ($params['enable_monitoring'] === 'on') {
            $serverData['labels'] = array('monitoring' => 'enabled');
        }
        
        // Create the server
        $response = hetznervps_makeAPICall('/servers', $apiToken, 'POST', $serverData);
        
        if ($response && isset($response['server'])) {
            $server = $response['server'];
            
            // Save server ID for future operations
            hetznervps_saveServerData($params['serviceid'], array(
                'server_id' => $server['id'],
                'server_name' => $server['name'],
                'ip_address' => $server['public_net']['ipv4']['ip'] ?? '',
                'root_password' => $response['root_password'] ?? ''
            ));
            
            return 'success';
        }
        
        return 'Failed to create server: ' . (isset($response['error']['message']) ? $response['error']['message'] : 'Unknown error');
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Suspend VPS
 */
function hetznervps_SuspendAccount(array $params)
{
    try {
        $apiToken = $params['api_token'];
        $serverData = hetznervps_getServerData($params['serviceid']);
        
        if (!$serverData || !$serverData['server_id']) {
            return 'Server not found';
        }
        
        // Power off the server
        $response = hetznervps_makeAPICall('/servers/' . $serverData['server_id'] . '/actions/poweroff', $apiToken, 'POST');
        
        if ($response) {
            return 'success';
        }
        
        return 'Failed to suspend server';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Unsuspend VPS
 */
function hetznervps_UnsuspendAccount(array $params)
{
    try {
        $apiToken = $params['api_token'];
        $serverData = hetznervps_getServerData($params['serviceid']);
        
        if (!$serverData || !$serverData['server_id']) {
            return 'Server not found';
        }
        
        // Power on the server
        $response = hetznervps_makeAPICall('/servers/' . $serverData['server_id'] . '/actions/poweron', $apiToken, 'POST');
        
        if ($response) {
            return 'success';
        }
        
        return 'Failed to unsuspend server';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Terminate VPS
 */
function hetznervps_TerminateAccount(array $params)
{
    try {
        $apiToken = $params['api_token'];
        $serverData = hetznervps_getServerData($params['serviceid']);
        
        if (!$serverData || !$serverData['server_id']) {
            return 'success'; // Already deleted
        }
        
        // Delete the server
        $response = hetznervps_makeAPICall('/servers/' . $serverData['server_id'], $apiToken, 'DELETE');
        
        // Remove server data
        hetznervps_removeServerData($params['serviceid']);
        
        return 'success';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Server data management
 */
function hetznervps_saveServerData($serviceId, $data)
{
    $dataFile = __DIR__ . '/data/server_' . $serviceId . '.json';
    $dataDir = dirname($dataFile);
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    file_put_contents($dataFile, json_encode($data), LOCK_EX);
}

function hetznervps_getServerData($serviceId)
{
    $dataFile = __DIR__ . '/data/server_' . $serviceId . '.json';
    
    if (!file_exists($dataFile)) {
        return false;
    }
    
    return json_decode(file_get_contents($dataFile), true);
}

function hetznervps_removeServerData($serviceId)
{
    $dataFile = __DIR__ . '/data/server_' . $serviceId . '.json';
    if (file_exists($dataFile)) {
        unlink($dataFile);
    }
}

/**
 * Client area custom buttons
 */
function hetznervps_ClientAreaCustomButtonArray()
{
    return array(
        "Reboot Server" => "reboot",
        "Reset Password" => "resetpassword",
        "Server Console" => "console",
    );
}

/**
 * Admin area custom buttons  
 */
function hetznervps_AdminCustomButtonArray()
{
    return array(
        "View Server Details" => "serverdetails",
        "Reboot Server" => "reboot", 
        "Reset Password" => "resetpassword",
        "Update Cache" => "updatecache",
    );
}

/**
 * Custom button: Update Cache
 */
function hetznervps_updatecache(array $params)
{
    try {
        $apiToken = $params['api_token'];
        
        // Clear existing cache
        $cacheFiles = glob(__DIR__ . '/cache/hetzner_*.cache');
        foreach ($cacheFiles as $file) {
            unlink($file);
        }
        
        // Refresh data from API
        hetznervps_getServerTypes($apiToken);
        hetznervps_getLocations($apiToken);
        hetznervps_getImages($apiToken);
        
        return 'success';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Custom button: Reboot Server
 */
function hetznervps_reboot(array $params)
{
    try {
        $apiToken = $params['api_token'];
        $serverData = hetznervps_getServerData($params['serviceid']);
        
        if (!$serverData || !$serverData['server_id']) {
            return 'Server not found';
        }
        
        $response = hetznervps_makeAPICall('/servers/' . $serverData['server_id'] . '/actions/reboot', $apiToken, 'POST');
        
        if ($response) {
            return 'success';
        }
        
        return 'Failed to reboot server';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Custom button: Reset Password
 */
function hetznervps_resetpassword(array $params)
{
    try {
        $apiToken = $params['api_token'];
        $serverData = hetznervps_getServerData($params['serviceid']);
        
        if (!$serverData || !$serverData['server_id']) {
            return 'Server not found';
        }
        
        $response = hetznervps_makeAPICall('/servers/' . $serverData['server_id'] . '/actions/reset_password', $apiToken, 'POST');
        
        if ($response && isset($response['root_password'])) {
            // Update stored password
            $serverData['root_password'] = $response['root_password'];
            hetznervps_saveServerData($params['serviceid'], $serverData);
            
            return 'success';
        }
        
        return 'Failed to reset password';
        
    } catch (Exception $e) {
        return $e->getMessage();
    }
}