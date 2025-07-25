<?php
/**
 * Debug script for PDF generation
 * Place this file in your WordPress root directory and access via browser
 */

// WordPress configuration
define('WP_USE_THEMES', false);
require_once('./wp-config.php');
require_once('./wp-load.php');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diet Calculator PDF Debug</h1>";

// Step 1: Check if plugin is active
echo "<h2>Step 1: Plugin Status</h2>";
if (is_plugin_active('diet-calculator-plugin/diet-calculator.php')) {
    echo "✅ Plugin is ACTIVE<br>";
} else {
    echo "❌ Plugin is NOT ACTIVE<br>";
}

// Step 2: Check if classes exist
echo "<h2>Step 2: Class Availability</h2>";
$classes = [
    'DietCalculator_Database',
    'DietCalculator_PDF_Generator',
    'DietCalculator_Admin',
    'FPDF'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✅ Class '$class' exists<br>";
    } else {
        echo "❌ Class '$class' does NOT exist<br>";
    }
}

// Step 3: Check database tables
echo "<h2>Step 3: Database Tables</h2>";
global $wpdb;
$tables = [
    $wpdb->prefix . 'diet_calculator_users',
    $wpdb->prefix . 'diet_calculator_plans',
    $wpdb->prefix . 'diet_calculator_progress'
];

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✅ Table '$table' exists<br>";
        
        // Check for data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "&nbsp;&nbsp;&nbsp;→ Contains $count records<br>";
    } else {
        echo "❌ Table '$table' does NOT exist<br>";
    }
}

// Step 4: Test FPDF directly
echo "<h2>Step 4: FPDF Test</h2>";
try {
    require_once(WP_PLUGIN_DIR . '/diet-calculator-plugin/includes/lib/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Test PDF');
    echo "✅ FPDF works correctly<br>";
} catch (Exception $e) {
    echo "❌ FPDF Error: " . $e->getMessage() . "<br>";
}

// Step 5: Test database retrieval
echo "<h2>Step 5: Database Test</h2>";
try {
    $database = new DietCalculator_Database();
    echo "✅ Database class instantiated<br>";
    
    // Get the latest plan
    $plans_table = $wpdb->prefix . 'diet_calculator_plans';
    $latest_plan = $wpdb->get_row("SELECT * FROM $plans_table ORDER BY id DESC LIMIT 1");
    
    if ($latest_plan) {
        echo "✅ Found latest plan ID: " . $latest_plan->id . "<br>";
        
        // Test retrieval
        $plan_data = $database->get_meal_plan($latest_plan->id);
        if ($plan_data) {
            echo "✅ Plan data retrieved successfully<br>";
            echo "&nbsp;&nbsp;&nbsp;→ Keys: " . implode(', ', array_keys($plan_data)) . "<br>";
            
            if (isset($plan_data['meal_plan'])) {
                if (is_string($plan_data['meal_plan'])) {
                    echo "&nbsp;&nbsp;&nbsp;→ Meal plan is JSON string (needs parsing)<br>";
                } elseif (is_array($plan_data['meal_plan'])) {
                    echo "&nbsp;&nbsp;&nbsp;→ Meal plan is array (ready to use)<br>";
                } else {
                    echo "&nbsp;&nbsp;&nbsp;→ Meal plan type: " . gettype($plan_data['meal_plan']) . "<br>";
                }
            }
        } else {
            echo "❌ Failed to retrieve plan data<br>";
        }
    } else {
        echo "❌ No plans found in database<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

// Step 6: Test PDF generation
echo "<h2>Step 6: PDF Generation Test</h2>";
if (isset($plan_data) && $plan_data) {
    try {
        echo "🔄 Testing PDF generation...<br>";
        
        // Create test data if needed
        $test_data = [
            'height' => 175,
            'weight' => 70,
            'age' => 30,
            'sex' => 'male',
            'goal' => 'lose_weight',
            'daily_calories' => 2000,
            'protein_grams' => 150,
            'carb_grams' => 200,
            'fat_grams' => 67,
            'water_intake' => 2500,
            'bmr' => 1700,
            'tdee' => 2200
        ];
        
        $test_meal_plan = [
            'foodCategorization' => [
                'prioritize' => [
                    'title' => 'Prioritize Foods',
                    'description' => 'Foods to eat more of',
                    'foods' => ['vegetables', 'lean protein', 'fruits']
                ]
            ]
        ];
        
        $pdf_generator = new DietCalculator_PDF_Generator();
        
        // Start output buffering to catch any output
        ob_start();
        
        // This would normally trigger download, so we'll just test class methods
        echo "✅ PDF Generator instantiated successfully<br>";
        
        // Clean any output
        ob_end_clean();
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ PDF Generation Error: " . $e->getMessage() . "<br>";
        echo "❌ Stack trace: " . $e->getTraceAsString() . "<br>";
    }
}

// Step 7: Test AJAX endpoint
echo "<h2>Step 7: AJAX Endpoint Test</h2>";
$ajax_url = admin_url('admin-ajax.php');
echo "AJAX URL: $ajax_url<br>";

if (isset($latest_plan)) {
    $test_url = $ajax_url . '?action=diet_calculator_download_pdf&plan_id=' . $latest_plan->id;
    echo "Test PDF URL: <a href='$test_url' target='_blank'>$test_url</a><br>";
    echo "👆 Click this link to test PDF download directly<br>";
}

// Step 8: Check WordPress error log
echo "<h2>Step 8: Recent Error Log</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = shell_exec("tail -20 $error_log");
    if ($recent_errors) {
        echo "<pre>$recent_errors</pre>";
    } else {
        echo "No recent errors in log<br>";
    }
} else {
    echo "Error log not found or not configured<br>";
}

echo "<h2>Manual Test Instructions</h2>";
echo "<ol>";
echo "<li>First, try creating a diet plan through the frontend form</li>";
echo "<li>Then go to WordPress Admin → Diet Calculator → All Plans</li>";
echo "<li>Click 'Download PDF' on any plan</li>";
echo "<li>Check browser Network tab (F12) to see what response you get</li>";
echo "<li>Check WordPress error logs for any PHP errors</li>";
echo "</ol>";

echo "<h2>Debug Complete</h2>";
echo "If you see any ❌ errors above, those need to be fixed first.<br>";
echo "If everything shows ✅ but PDF still doesn't work, check the browser Network tab when clicking download.";
?>