<?php
/**
 * Direct PDF test - bypasses WordPress AJAX
 * Place this in your WordPress root directory
 */

// WordPress setup
define('WP_USE_THEMES', false);
require_once('./wp-config.php');
require_once('./wp-load.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing PDF generation directly...<br><br>";

try {
    // Load the PDF generator
    require_once(WP_PLUGIN_DIR . '/diet-calculator-plugin/includes/lib/fpdf.php');
    require_once(WP_PLUGIN_DIR . '/diet-calculator-plugin/includes/class-pdf-generator.php');
    
    echo "✅ Classes loaded successfully<br>";
    
    // Create test data
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
                'title' => 'Prioritize (Good to Have)',
                'description' => 'Foods that actively support your weight loss goal',
                'foods' => ['leafy greens', 'lean protein', 'berries', 'quinoa'],
                'reasoning' => 'These foods support weight loss and overall health'
            ],
            'neutral' => [
                'title' => 'Neutral (Moderate Consumption)', 
                'description' => 'Foods that can be included in moderation',
                'foods' => ['whole grains', 'nuts in moderation', 'fruits'],
                'reasoning' => 'Good nutrition but should be balanced'
            ],
            'minimize' => [
                'title' => 'Minimize (Avoid/Limit)',
                'description' => 'Foods that may hinder your progress',
                'foods' => ['processed foods', 'sugary drinks', 'fried foods'],
                'reasoning' => 'These foods may interfere with weight loss goals'
            ]
        ],
        'weeklyPlan' => [
            [
                'day' => 1,
                'meals' => [
                    [
                        'name' => 'Breakfast',
                        'food' => 'Greek Yogurt Parfait with Berries',
                        'calories' => 400,
                        'protein' => 25,
                        'carbs' => 35,
                        'fat' => 12,
                        'ingredients' => ['greek yogurt', 'mixed berries', 'granola', 'honey'],
                        'portions' => '1 cup yogurt, 1/2 cup berries, 2 tbsp granola'
                    ],
                    [
                        'name' => 'Lunch', 
                        'food' => 'Grilled Chicken Salad',
                        'calories' => 500,
                        'protein' => 40,
                        'carbs' => 20,
                        'fat' => 15,
                        'ingredients' => ['chicken breast', 'mixed greens', 'cherry tomatoes', 'olive oil'],
                        'portions' => '150g chicken, 2 cups greens, 1 tbsp dressing'
                    ],
                    [
                        'name' => 'Dinner',
                        'food' => 'Salmon with Quinoa and Vegetables',
                        'calories' => 600,
                        'protein' => 35,
                        'carbs' => 45,
                        'fat' => 18,
                        'ingredients' => ['salmon fillet', 'quinoa', 'broccoli', 'carrots'],
                        'portions' => '150g salmon, 1/2 cup quinoa, 1 cup vegetables'
                    ]
                ]
            ]
        ],
        'shoppingList' => [
            'proteins' => [
                'title' => 'Proteins',
                'items' => ['chicken breast', 'salmon fillet', 'greek yogurt', 'eggs']
            ],
            'vegetables' => [
                'title' => 'Vegetables',
                'items' => ['mixed greens', 'broccoli', 'carrots', 'cherry tomatoes']
            ],
            'grains' => [
                'title' => 'Grains & Carbs',
                'items' => ['quinoa', 'brown rice', 'oats']
            ]
        ]
    ];
    
    echo "✅ Test data created<br>";
    
    // Test PDF generation
    echo "🔄 Generating PDF...<br>";
    
    $pdf_generator = new DietCalculator_PDF_Generator();
    
    // This will either download the PDF or show an error
    $pdf_generator->generate_pdf($test_data, $test_meal_plan);
    
    // If we reach here, there was an error (PDF should have exited)
    echo "❌ PDF generation completed but no download triggered<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "❌ File: " . $e->getFile() . "<br>";
    echo "❌ Line: " . $e->getLine() . "<br>";
    echo "❌ Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>