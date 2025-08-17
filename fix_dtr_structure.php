<?php

$file = 'app/Http/Controllers/PayrollController.php';
$content = file_get_contents($file);

// Pattern 1: Replace the problematic map() approach with proper loop approach
$oldPattern1 = '        $dtrData = [$employee->id => $timeLogs->map(function($logs) {
            return $logs->first();
        })];';

$newPattern1 = '        // Build DTR data structure matching working version
        $employeeDtr = [];
        foreach ($periodDates as $date) {
            $timeLog = $timeLogs->get($date, collect())->first();
            $employeeDtr[$date] = $timeLog;
        }
        $dtrData = [$employee->id => $employeeDtr];';

$newContent = str_replace($oldPattern1, $newPattern1, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "Successfully updated DTR data structure to match working version in PayrollController.php\n";
    echo "Replacements made: " . substr_count($content, $oldPattern1) . "\n";
} else {
    echo "No replacements made - pattern not found\n";
    echo "Looking for:\n" . $oldPattern1 . "\n";
}

// Clean up temp file
if (file_exists('temp_working.php')) {
    unlink('temp_working.php');
    echo "Cleaned up temporary file\n";
}
