<?php

// Simple debug without Laravel bootstrap
echo "Debug Holiday Auto-Detection Issue:\n";
echo "===================================\n\n";

// Connect to database directly  
try {
    $host = 'localhost';
    $dbname = 'payroll-react';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "1. Checking holidays for Sept 11, 2025:\n";
    $stmt = $pdo->prepare("SELECT name, date, type, is_active FROM holidays WHERE date = '2025-09-11'");
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($holidays)) {
        echo "   No holidays found for Sept 11, 2025\n";
    } else {
        foreach ($holidays as $holiday) {
            echo "   - {$holiday['name']} ({$holiday['date']}) - Type: {$holiday['type']} - Active: " .
                ($holiday['is_active'] ? 'Yes' : 'No') . "\n";
        }
    }

    echo "\n2. Checking existing time logs for Employee 12 on Sept 11, 2025:\n";
    $stmt = $pdo->prepare("SELECT id, log_date, log_type, time_in, time_out FROM time_logs WHERE employee_id = 12 AND log_date = '2025-09-11'");
    $stmt->execute();
    $timeLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($timeLogs)) {
        echo "   No existing time logs found for Employee 12 on Sept 11, 2025\n";
    } else {
        foreach ($timeLogs as $log) {
            echo "   - ID: {$log['id']}, Type: {$log['log_type']}, Time In: {$log['time_in']}, Time Out: {$log['time_out']}\n";
        }
    }

    echo "\n3. Checking active holidays between Sept 1-15, 2025:\n";
    $stmt = $pdo->prepare("SELECT name, date, type, is_active FROM holidays WHERE date BETWEEN '2025-09-01' AND '2025-09-15' AND is_active = 1 ORDER BY date");
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($holidays)) {
        echo "   No active holidays found in period\n";
    } else {
        foreach ($holidays as $holiday) {
            echo "   - {$holiday['name']} ({$holiday['date']}) - Type: {$holiday['type']}\n";
        }
    }
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

echo "\nDebug complete.\n";
