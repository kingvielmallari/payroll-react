<?php

$file = 'app/Http/Controllers/PayrollController.php';
$content = file_get_contents($file);

// Find and replace the DTR data transformation to remove toArray()
$oldPattern = '$dtrData = [$employee->id => $timeLogs->map(function($logs) {
            return $logs->first();
        })->toArray()];';

$newPattern = '$dtrData = [$employee->id => $timeLogs->map(function($logs) {
            return $logs->first();
        })];';

$newContent = str_replace($oldPattern, $newPattern, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "Successfully removed toArray() from DTR data transformation in PayrollController.php\n";
    echo "Replacements made: " . substr_count($content, $oldPattern) . "\n";
} else {
    echo "No replacements made - pattern not found\n";
    echo "Looking for:\n" . $oldPattern . "\n";
}

// Clean up temp file
if (file_exists('temp_working_controller.php')) {
    unlink('temp_working_controller.php');
    echo "Cleaned up temporary file\n";
}
