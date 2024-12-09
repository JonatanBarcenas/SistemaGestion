// debug_log.php
<?php
function logError($message, $data = null) {
    $logFile = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    if ($data !== null) {
        $logMessage .= "Data: " . print_r($data, true) . "\n";
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>