<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$keys_file = 'keys.json';

// Create keys.json if it doesn't exist
if (!file_exists($keys_file)) {
    file_put_contents($keys_file, json_encode([]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new key
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['key'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }
    
    $keys = json_decode(file_get_contents($keys_file), true);
    $keys[$input['key']] = $input;
    
    file_put_contents($keys_file, json_encode($keys, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'message' => 'Key added']);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validate key - NO BOT TOKEN NEEDED
    $key = $_GET['key'] ?? '';
    
    if (empty($key)) {
        echo json_encode(['valid' => false, 'message' => 'Missing key']);
        exit;
    }
    
    $keys = json_decode(file_get_contents($keys_file), true);
    
    if (isset($keys[$key])) {
        $key_data = $keys[$key];
        
        // Check if expired
        $expiry_date = new DateTime($key_data['expiry']);
        $now = new DateTime();
        
        if ($now > $expiry_date) {
            echo json_encode(['valid' => false, 'message' => 'Key expired']);
        } elseif ($key_data['used']) {
            echo json_encode(['valid' => false, 'message' => 'Key already used']);
        } else {
            // Mark as used
            $keys[$key]['used'] = true;
            $keys[$key]['used_at'] = $now->format('Y-m-d H:i:s');
            file_put_contents($keys_file, json_encode($keys, JSON_PRETTY_PRINT));
            
            echo json_encode([
                'valid' => true, 
                'message' => 'Valid key', 
                'duration' => $key_data['duration'],
                'expiry' => $key_data['expiry']
            ]);
        }
    } else {
        echo json_encode(['valid' => false, 'message' => 'Key not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
}
?>
