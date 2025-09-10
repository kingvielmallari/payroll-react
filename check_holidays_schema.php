<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

try {
    // Get the ENUM values for the type column
    $result = DB::select("SHOW COLUMNS FROM holidays LIKE 'type'");

    if (!empty($result)) {
        echo "Type column definition:\n";
        echo "Field: " . $result[0]->Field . "\n";
        echo "Type: " . $result[0]->Type . "\n";
        echo "Null: " . $result[0]->Null . "\n";
        echo "Default: " . $result[0]->Default . "\n";

        // Extract enum values
        preg_match_all("/'([^']+)'/", $result[0]->Type, $matches);
        if (isset($matches[1])) {
            echo "\nAvailable ENUM values:\n";
            foreach ($matches[1] as $value) {
                echo "- " . $value . "\n";
            }
        }
    } else {
        echo "Type column not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
