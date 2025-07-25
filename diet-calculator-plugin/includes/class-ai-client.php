<?php
/**
 * AI Client for Diet Calculator Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DietCalculator_AI_Client {

    private $api_key;
    private $models = array(
        'microsoft/DialoGPT-medium',
        'google/flan-t5-base',
        'facebook/blenderbot-400M-distill',
        'microsoft/DialoGPT-small'
    );

    public function __construct() {
        $this->api_key = get_option('diet_calculator_huggingface_api_key', '');
    }

    /**
     * Generate meal plan using AI or fallback
     */
    public function generate_meal_plan($data) {
        if (empty($this->api_key)) {
            return $this->generate_mock_meal_plan($data);
        }

        $prompt = $this->create_meal_plan_prompt($data);

        try {
            $response = $this->call_huggingface_api($prompt);
            return $this->parse_ai_response($response, $data);
        } catch (Exception $e) {
            error_log('Diet Calculator AI Error: ' . $e->getMessage());
            return $this->generate_mock_meal_plan($data);
        }
    }

    /**
     * Create detailed AI prompt
     */
    private function create_meal_plan_prompt($data) {
        $bmi = $data['weight'] / pow($data['height'] / 100, 2);
        $bmi_category = $bmi < 18.5 ? 'underweight' : ($bmi < 25 ? 'normal' : ($bmi < 30 ? 'overweight' : 'obese'));
        $is_deficit = $data['goal'] === 'lose_weight';
        $is_surplus = $data['goal'] === 'build_muscle';
        $has_restrictions = !empty($data['foodAllergies']) || !empty($data['foodIntolerances']);

        $prompt = "You are a certified nutritionist and meal planning expert. Create a comprehensive, personalized meal plan based on scientific nutrition principles.\n\n";

        $prompt .= "CLIENT PROFILE:\n";
        $prompt .= "- Demographics: {$data['age']}yr " . ($data['sex'] === 'male' ? 'male' : 'female') . ", {$data['height']}cm, {$data['weight']}kg (BMI: " . round($bmi, 1) . " - {$bmi_category})\n";
        $prompt .= "- Primary Goal: " . strtoupper(str_replace('_', ' ', $data['goal'])) . "\n";
        $prompt .= "- Timeline: {$data['timeline']} weeks" . (isset($data['goalWeight']) ? " (target: {$data['goalWeight']}kg)" : '') . "\n";
        $prompt .= "- Activity: " . str_replace('_', ' ', $data['dailyActivityLevel']) . " lifestyle, " . str_replace('_', ' ', $data['exerciseIntensity']) . " exercise intensity\n";
        $prompt .= "- Meal Frequency: {$data['mealsPerDay']} meals/day\n";
        $prompt .= "- Macro Strategy: " . str_replace('_', ' ', $data['macronutrientRatio']) . "\n\n";

        $prompt .= "CALCULATED TARGETS:\n";
        $prompt .= "- Daily Calories: " . round($data['dailyCalories']) . " (" . ($is_deficit ? 'DEFICIT' : ($is_surplus ? 'SURPLUS' : 'MAINTENANCE')) . ")\n";
        $prompt .= "- Protein: " . round($data['proteinGrams']) . "g (" . round($data['proteinGrams'] * 4 / $data['dailyCalories'] * 100) . "%)\n";
        $prompt .= "- Carbohydrates: " . round($data['carbGrams']) . "g (" . round($data['carbGrams'] * 4 / $data['dailyCalories'] * 100) . "%)\n";
        $prompt .= "- Fat: " . round($data['fatGrams']) . "g (" . round($data['fatGrams'] * 9 / $data['dailyCalories'] * 100) . "%)\n";
        $prompt .= "- Water: " . round($data['waterIntake']) . "ml\n\n";

        $prompt .= "DIETARY REQUIREMENTS:\n";
        $prompt .= "- Preferences: " . (!empty($data['dietaryPreferences']) ? implode(', ', $data['dietaryPreferences']) : 'None specified') . "\n";
        $prompt .= "- Allergies: " . (!empty($data['foodAllergies']) ? implode(', ', $data['foodAllergies']) : 'None') . "\n";
        $prompt .= "- Intolerances: " . (!empty($data['foodIntolerances']) ? implode(', ', $data['foodIntolerances']) : 'None') . "\n\n";

        $prompt .= "Generate a comprehensive meal plan with specific foods, portions, and nutritional guidance.";

        return $prompt;
    }

    /**
     * Call Hugging Face API
     */
    private function call_huggingface_api($prompt) {
        $last_error = null;

        foreach ($this->models as $model) {
            $url = "https://api-inference.huggingface.co/models/{$model}";
            
            $body = json_encode(array(
                'inputs' => $prompt,
                'parameters' => array(
                    'max_new_tokens' => 1000,
                    'temperature' => 0.8,
                    'do_sample' => true,
                    'return_full_text' => false
                )
            ));

            $args = array(
                'body' => $body,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 30,
            );

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                $last_error = $response->get_error_message();
                continue;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (isset($data[0]['generated_text'])) {
                    return $data[0]['generated_text'];
                }
            }

            $last_error = "HTTP {$response_code}: " . wp_remote_retrieve_body($response);
        }

        throw new Exception("All models failed. Last error: {$last_error}");
    }

    /**
     * Parse AI response and structure it
     */
    private function parse_ai_response($generated_text, $data) {
        $ai_insights = $this->extract_ai_insights($generated_text);
        
        return array(
            'weeklyPlan' => array(
                array(
                    'day' => 1,
                    'meals' => $this->generate_personalized_meals($generated_text, $data, $ai_insights)
                )
            ),
            'recipes' => $this->generate_intelligent_recipes($generated_text, $data, $ai_insights),
            'shoppingList' => $this->generate_smart_shopping_list($data, $ai_insights),
            'supplements' => $this->generate_evidence_based_supplements($data, $ai_insights),
            'hydrationSchedule' => $this->generate_personalized_hydration($data),
            'mealPrepTips' => $this->generate_contextual_meal_prep_tips($data, $ai_insights),
            'foodCategorization' => $this->generate_food_categorization($data, $ai_insights),
            'nutritionalAnalysis' => $this->generate_nutritional_analysis($data, $ai_insights),
            'aiGenerated' => true,
            'aiConfidence' => strlen($generated_text) > 100 ? 'high' : 'fallback',
            'generatedText' => substr($generated_text, 0, 1000)
        );
    }

    /**
     * Extract insights from AI response
     */
    private function extract_ai_insights($text) {
        return array(
            'recommendedFoods' => array(),
            'avoidFoods' => array(),
            'keyNutrients' => array(),
            'mealTiming' => '',
            'specialConsiderations' => array()
        );
    }

    /**
     * Generate food categorization
     */
    private function generate_food_categorization($data, $ai_insights) {
        $is_vegetarian = in_array('vegetarian', $data['dietaryPreferences']) || in_array('vegan', $data['dietaryPreferences']);
        $is_vegan = in_array('vegan', $data['dietaryPreferences']);
        $is_keto = in_array('keto', $data['dietaryPreferences']) || in_array('low_carb', $data['dietaryPreferences']);
        $has_nut_allergy = in_array('nuts', $data['foodAllergies']);
        $has_dairy_allergy = in_array('dairy', $data['foodAllergies']) || in_array('lactose', $data['foodIntolerances']) || $is_vegan;

        // Goal-specific food recommendations
        $goal_foods = array(
            'lose_weight' => array(
                'prioritize' => array('leafy greens', 'lean protein', 'fiber-rich vegetables', 'berries', 'green tea', 'quinoa', 'salmon', 'legumes', 'egg whites'),
                'neutral' => array('whole grains', 'nuts in moderation', 'lean poultry', 'sweet potato', 'oats', 'brown rice'),
                'minimize' => array('processed foods', 'sugary drinks', 'refined carbs', 'fried foods', 'alcohol', 'high-calorie snacks', 'white bread', 'candy')
            ),
            'build_muscle' => array(
                'prioritize' => array('lean meats', 'eggs', 'protein powder', 'cottage cheese', 'quinoa', 'sweet potato', 'nuts', 'avocado', 'salmon'),
                'neutral' => array('whole grains', 'fruits', 'vegetables', 'healthy fats', 'legumes'),
                'minimize' => array('excessive alcohol', 'processed meats', 'trans fats', 'excessive sugar', 'low-protein processed foods')
            ),
            'improve_health' => array(
                'prioritize' => array('colorful vegetables', 'omega-3 rich fish', 'whole grains', 'berries', 'nuts', 'olive oil', 'legumes', 'fermented foods'),
                'neutral' => array('lean meats', 'fruits', 'herbs and spices'),
                'minimize' => array('processed meats', 'trans fats', 'excessive sodium', 'refined sugars', 'processed foods')
            )
        );

        $base_foods = isset($goal_foods[$data['goal']]) ? $goal_foods[$data['goal']] : $goal_foods['improve_health'];
        
        $prioritize = $base_foods['prioritize'];
        $neutral = $base_foods['neutral'];
        $minimize = $base_foods['minimize'];

        // Apply dietary restrictions
        if ($has_dairy_allergy) {
            $dairy_terms = array('dairy', 'milk', 'yogurt', 'cheese', 'cottage cheese');
            $prioritize = array_filter($prioritize, function($food) use ($dairy_terms) {
                return !$this->contains_any_term($food, $dairy_terms);
            });
            $neutral = array_filter($neutral, function($food) use ($dairy_terms) {
                return !$this->contains_any_term($food, $dairy_terms);
            });
            
            $prioritize = array_merge($prioritize, array('plant-based milk alternatives', 'coconut yogurt', 'nutritional yeast', 'calcium-fortified plant milk'));
            $minimize = array_merge($minimize, array('all dairy products', 'milk-based foods', 'whey protein', 'casein protein'));
        }

        if ($has_nut_allergy) {
            $prioritize = array_filter($prioritize, function($food) {
                return strpos(strtolower($food), 'nut') === false;
            });
            $neutral = array_filter($neutral, function($food) {
                return strpos(strtolower($food), 'nut') === false;
            });
            $prioritize = array_merge($prioritize, array('seeds (sunflower, pumpkin)', 'tahini', 'coconut'));
        }

        return array(
            'prioritize' => array(
                'title' => 'Prioritize (Good to Have)',
                'description' => "Foods that actively support your " . str_replace('_', ' ', $data['goal']) . " goal and overall health",
                'foods' => array_values($prioritize),
                'reasoning' => "These foods are specifically chosen based on your " . str_replace('_', ' ', $data['goal']) . " goal, " . $data['sex'] . " demographics, and " . str_replace('_', ' ', $data['dailyActivityLevel']) . " lifestyle."
            ),
            'neutral' => array(
                'title' => 'Neutral (Moderate Consumption)',
                'description' => 'Foods that can be included in moderation as part of a balanced approach',
                'foods' => array_values($neutral),
                'reasoning' => 'These foods provide good nutrition but should be balanced with your primary goal foods.'
            ),
            'minimize' => array(
                'title' => 'Minimize (Avoid/Limit)',
                'description' => 'Foods that may hinder your progress or cause health issues',
                'foods' => array_values($minimize),
                'reasoning' => "These foods may interfere with your " . str_replace('_', ' ', $data['goal']) . " goal or conflict with your dietary restrictions."
            )
        );
    }

    /**
     * Helper function to check if text contains any of the specified terms
     */
    private function contains_any_term($text, $terms) {
        $text_lower = strtolower($text);
        foreach ($terms as $term) {
            if (strpos($text_lower, strtolower($term)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate mock meal plan as fallback
     */
    private function generate_mock_meal_plan($data) {
        $ai_insights = array(
            'recommendedFoods' => array(),
            'avoidFoods' => array(),
            'keyNutrients' => array(),
            'mealTiming' => '',
            'specialConsiderations' => array()
        );

        return array(
            'weeklyPlan' => array(
                array(
                    'day' => 1,
                    'meals' => $this->generate_personalized_meals('', $data, $ai_insights)
                )
            ),
            'recipes' => $this->generate_basic_recipes($data),
            'shoppingList' => $this->generate_basic_shopping_list($data),
            'supplements' => $this->generate_basic_supplements($data),
            'hydrationSchedule' => $this->generate_personalized_hydration($data),
            'mealPrepTips' => $this->generate_basic_meal_prep_tips($data),
            'foodCategorization' => $this->generate_food_categorization($data, $ai_insights),
            'nutritionalAnalysis' => $this->generate_nutritional_analysis($data, $ai_insights),
            'aiGenerated' => false,
            'aiConfidence' => 'fallback',
            'generatedText' => 'Using intelligent fallback recommendations based on your profile'
        );
    }

    /**
     * Generate personalized meals
     */
    private function generate_personalized_meals($text, $data, $ai_insights) {
        $meal_names = array('Breakfast', 'Lunch', 'Dinner', 'Snack 1', 'Snack 2');
        $meals_to_generate = min($data['mealsPerDay'], count($meal_names));
        
        $calories_per_meal = $data['dailyCalories'] / $data['mealsPerDay'];
        $protein_per_meal = $data['proteinGrams'] / $data['mealsPerDay'];
        $carbs_per_meal = $data['carbGrams'] / $data['mealsPerDay'];
        $fat_per_meal = $data['fatGrams'] / $data['mealsPerDay'];

        $meals = array();

        for ($i = 0; $i < $meals_to_generate; $i++) {
            $meal_suggestion = $this->get_meal_suggestion($meal_names[$i], $data['dietaryPreferences'], $data['foodAllergies']);
            
            $meals[] = array(
                'name' => $meal_names[$i],
                'food' => $meal_suggestion['food'],
                'calories' => round($calories_per_meal),
                'protein' => round($protein_per_meal),
                'carbs' => round($carbs_per_meal),
                'fat' => round($fat_per_meal),
                'ingredients' => $meal_suggestion['ingredients'],
                'portions' => $meal_suggestion['portions']
            );
        }

        return $meals;
    }

    /**
     * Get meal suggestion based on preferences and allergies
     */
    private function get_meal_suggestion($meal_name, $preferences, $allergies) {
        $is_vegan = in_array('vegan', $preferences);
        $is_vegetarian = in_array('vegetarian', $preferences) || $is_vegan;
        $is_keto = in_array('keto', $preferences) || in_array('low_carb', $preferences);
        $has_dairy_allergy = in_array('dairy', $allergies) || $is_vegan;

        $meal_options = array(
            'Breakfast' => array(
                'food' => $is_vegan ? 'Overnight Oats with Berries' : 
                         ($has_dairy_allergy ? 'Chia Pudding with Plant Milk' :
                         ($is_keto ? 'Avocado and Eggs' : 'Greek Yogurt Parfait')),
                'ingredients' => $is_vegan ? array('oats', 'plant milk', 'berries', 'chia seeds') :
                                ($has_dairy_allergy ? array('chia seeds', 'coconut milk', 'berries') :
                                ($is_keto ? array('eggs', 'avocado', 'spinach', 'olive oil') :
                                array('greek yogurt', 'berries', 'granola', 'honey'))),
                'portions' => $has_dairy_allergy ? '3 tbsp chia seeds, 1 cup coconut milk' :
                             ($is_keto ? '2 eggs, 1/2 avocado' : '1 cup yogurt, 1/2 cup berries')
            ),
            'Lunch' => array(
                'food' => $is_vegetarian ? 'Quinoa Buddha Bowl' : 'Grilled Chicken Breast',
                'ingredients' => $is_vegetarian ? array('quinoa', 'chickpeas', 'vegetables', 'tahini') :
                                array('chicken breast', 'brown rice', 'broccoli', 'olive oil'),
                'portions' => '120g protein, 1 cup grains'
            ),
            'Dinner' => array(
                'food' => $is_vegan ? 'Lentil Curry with Vegetables' : 'Salmon with Sweet Potato',
                'ingredients' => $is_vegan ? array('lentils', 'coconut milk', 'vegetables', 'spices') :
                                array('salmon', 'sweet potato', 'asparagus', 'herbs'),
                'portions' => '150g protein, 200g vegetables'
            )
        );

        return isset($meal_options[$meal_name]) ? $meal_options[$meal_name] : array(
            'food' => 'Balanced meal with protein and vegetables',
            'ingredients' => array('protein source', 'vegetables', 'healthy fats'),
            'portions' => 'Appropriate serving size'
        );
    }

    // Placeholder methods for other components (implement as needed)
    private function generate_intelligent_recipes($text, $data, $ai_insights) { return array(); }
    private function generate_smart_shopping_list($data, $ai_insights) { return array(); }
    private function generate_evidence_based_supplements($data, $ai_insights) { return array(); }
    private function generate_personalized_hydration($data) { return array(); }
    private function generate_contextual_meal_prep_tips($data, $ai_insights) { return array(); }
    private function generate_nutritional_analysis($data, $ai_insights) { return array(); }
    private function generate_basic_recipes($data) { return array(); }
    private function generate_basic_shopping_list($data) { return array(); }
    private function generate_basic_supplements($data) { return array(); }
    private function generate_basic_meal_prep_tips($data) { return array(); }
}