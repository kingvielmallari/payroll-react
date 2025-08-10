<?php
// Simple log monitoring script
echo "Monitoring Laravel logs for recalculate attempts...\n";
echo "Please click the recalculate button in your browser now.\n";
echo "Press Ctrl+C to stop monitoring.\n\n";

$logFile = 'storage/logs/laravel.log';
$lastSize = filesize($logFile);

while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastSize) {
        // New content added
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastSize);
        $newContent = fread($handle, $currentSize - $lastSize);
        fclose($handle);
        
        // Only show recalculate-related logs
        $lines = explode("\n", $newContent);
        foreach ($lines as $line) {
            if (stripos($line, 'recalculate') !== false || 
                stripos($line, 'payroll') !== false ||
                stripos($line, 'error') !== false ||
                stripos($line, 'exception') !== false) {
                echo date('Y-m-d H:i:s') . " - " . trim($line) . "\n";
            }
        }
        
        $lastSize = $currentSize;
    }
    
    sleep(1);
}
