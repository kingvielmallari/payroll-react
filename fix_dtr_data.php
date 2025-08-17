<?php

$file = 'app/Http/Controllers/PayrollController.php';
$content = file_get_contents($file);

// Find and replace the DTR data transformation
$oldPattern = '$dtrData = [$employee->id => $timeLogs];';
$newPattern = '$dtrData = [$employee->id => $timeLogs->map(function($logs) {
            return $logs->first();
        })];';

$newContent = str_replace($oldPattern, $newPattern, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "Successfully updated DTR data transformation in PayrollController.php\n";
    echo "Replacements made: " . substr_count($content, $oldPattern) . "\n";
} else {
    echo "No replacements made - pattern not found\n";
}
