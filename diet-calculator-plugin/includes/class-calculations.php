<?php
/**
 * Nutrition calculations for Diet Calculator Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DietCalculator_Calculations {

    /**
     * Calculate complete nutrition plan
     */
    public function calculate_nutrition_plan($data) {
        $bmr = $this->calculate_bmr($data['height'], $data['weight'], $data['age'], $data['sex']);
        $tdee = $this->calculate_tdee($bmr, $data['dailyActivityLevel'], $data['exerciseIntensity']);
        $daily_calories = $this->calculate_daily_calories($tdee, $data['goal'], $data['weight'], $data['goalWeight'], $data['timeline']);
        $macros = $this->calculate_macros($daily_calories, $data['macronutrientRatio'], $data['goal']);
        $water_intake = $this->calculate_water_intake($data['weight'], $data['exerciseIntensity']);

        return array_merge($data, array(
            'bmr' => $bmr,
            'tdee' => $tdee,
            'dailyCalories' => $daily_calories,
            'proteinGrams' => $macros['protein'],
            'carbGrams' => $macros['carbs'],
            'fatGrams' => $macros['fat'],
            'waterIntake' => $water_intake
        ));
    }

    /**
     * Calculate BMR using Mifflin-St Jeor Equation
     */
    public function calculate_bmr($height, $weight, $age, $sex) {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age;
        
        if ($sex === 'male') {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }
        
        return round($bmr, 2);
    }

    /**
     * Calculate TDEE (Total Daily Energy Expenditure)
     */
    public function calculate_tdee($bmr, $activity_level, $exercise_intensity) {
        // Activity level multipliers
        $activity_multipliers = array(
            'sedentary' => 1.2,
            'lightly_active' => 1.375,
            'moderately_active' => 1.55,
            'very_active' => 1.725,
            'extremely_active' => 1.9
        );

        // Exercise intensity adjustments
        $exercise_adjustments = array(
            'low' => 0,
            'moderate' => 100,
            'high' => 200,
            'very_high' => 300
        );

        $activity_multiplier = isset($activity_multipliers[$activity_level]) 
            ? $activity_multipliers[$activity_level] 
            : 1.55;

        $exercise_adjustment = isset($exercise_adjustments[$exercise_intensity]) 
            ? $exercise_adjustments[$exercise_intensity] 
            : 100;

        $tdee = ($bmr * $activity_multiplier) + $exercise_adjustment;
        
        return round($tdee, 2);
    }

    /**
     * Calculate daily calorie target based on goal
     */
    public function calculate_daily_calories($tdee, $goal, $current_weight, $goal_weight = null, $timeline = 12) {
        switch ($goal) {
            case 'lose_weight':
                if ($goal_weight && $goal_weight < $current_weight) {
                    // Calculate calorie deficit needed
                    $weight_to_lose = $current_weight - $goal_weight;
                    $weeks = max($timeline, 1);
                    $calories_per_kg = 7700; // Approximately 7700 calories per kg of fat
                    
                    $total_deficit_needed = $weight_to_lose * $calories_per_kg;
                    $daily_deficit = $total_deficit_needed / ($weeks * 7);
                    
                    // Limit deficit to safe range (0.5kg-1kg per week = 550-1100 cal deficit)
                    $daily_deficit = min(max($daily_deficit, 300), 1000);
                    
                    return round($tdee - $daily_deficit, 0);
                } else {
                    // Standard weight loss deficit
                    return round($tdee * 0.8, 0); // 20% deficit
                }

            case 'build_muscle':
                return round($tdee * 1.1, 0); // 10% surplus

            case 'body_recomposition':
                return round($tdee, 0); // Maintenance calories

            case 'athletic_performance':
                return round($tdee * 1.05, 0); // 5% surplus

            case 'improve_health':
                return round($tdee, 0); // Maintenance calories

            default:
                return round($tdee, 0);
        }
    }

    /**
     * Calculate macronutrient distribution
     */
    public function calculate_macros($daily_calories, $macro_ratio, $goal) {
        $ratios = array(
            'balanced' => array('protein' => 0.25, 'carbs' => 0.45, 'fat' => 0.30),
            'high_protein' => array('protein' => 0.35, 'carbs' => 0.35, 'fat' => 0.30),
            'low_carb' => array('protein' => 0.30, 'carbs' => 0.20, 'fat' => 0.50),
            'high_carb' => array('protein' => 0.20, 'carbs' => 0.60, 'fat' => 0.20)
        );

        // Adjust ratios based on goal
        if ($goal === 'build_muscle') {
            $ratios['balanced']['protein'] = 0.30;
            $ratios['balanced']['carbs'] = 0.40;
            $ratios['balanced']['fat'] = 0.30;
        }

        $ratio = isset($ratios[$macro_ratio]) ? $ratios[$macro_ratio] : $ratios['balanced'];

        return array(
            'protein' => round(($daily_calories * $ratio['protein']) / 4, 1), // 4 cal/g
            'carbs' => round(($daily_calories * $ratio['carbs']) / 4, 1),     // 4 cal/g
            'fat' => round(($daily_calories * $ratio['fat']) / 9, 1)         // 9 cal/g
        );
    }

    /**
     * Calculate daily water intake
     */
    public function calculate_water_intake($weight, $exercise_intensity) {
        // Base water intake: 35ml per kg of body weight
        $base_water = $weight * 35;

        // Exercise adjustments
        $exercise_adjustments = array(
            'low' => 250,
            'moderate' => 500,
            'high' => 750,
            'very_high' => 1000
        );

        $exercise_bonus = isset($exercise_adjustments[$exercise_intensity]) 
            ? $exercise_adjustments[$exercise_intensity] 
            : 500;

        $total_water = $base_water + $exercise_bonus;

        // Minimum 2000ml, maximum 4000ml
        return round(min(max($total_water, 2000), 4000), 0);
    }

    /**
     * Calculate BMI (Body Mass Index)
     */
    public function calculate_bmi($height, $weight) {
        $height_meters = $height / 100;
        $bmi = $weight / ($height_meters * $height_meters);
        return round($bmi, 1);
    }

    /**
     * Get BMI category
     */
    public function get_bmi_category($bmi) {
        if ($bmi < 18.5) {
            return 'underweight';
        } elseif ($bmi < 25) {
            return 'normal';
        } elseif ($bmi < 30) {
            return 'overweight';
        } else {
            return 'obese';
        }
    }

    /**
     * Calculate ideal weight range
     */
    public function calculate_ideal_weight_range($height, $sex) {
        $height_meters = $height / 100;
        
        // Using BMI of 20-25 for ideal range
        $min_weight = round(20 * ($height_meters * $height_meters), 1);
        $max_weight = round(25 * ($height_meters * $height_meters), 1);

        return array(
            'min' => $min_weight,
            'max' => $max_weight
        );
    }

    /**
     * Calculate estimated time to reach goal
     */
    public function calculate_time_to_goal($current_weight, $goal_weight, $daily_calories, $tdee) {
        if (!$goal_weight || $goal_weight == $current_weight) {
            return null;
        }

        $weight_difference = abs($current_weight - $goal_weight);
        $daily_deficit_surplus = abs($tdee - $daily_calories);
        
        if ($daily_deficit_surplus == 0) {
            return null;
        }

        // Approximate 7700 calories per kg
        $calories_per_kg = 7700;
        $total_calories_needed = $weight_difference * $calories_per_kg;
        $days_needed = $total_calories_needed / $daily_deficit_surplus;
        $weeks_needed = ceil($days_needed / 7);

        return array(
            'weeks' => $weeks_needed,
            'months' => round($weeks_needed / 4.33, 1)
        );
    }

    /**
     * Get personalized recommendations based on profile
     */
    public function get_personalized_recommendations($data) {
        $recommendations = array();
        $bmi = $this->calculate_bmi($data['height'], $data['weight']);
        $bmi_category = $this->get_bmi_category($bmi);

        // BMI-based recommendations
        switch ($bmi_category) {
            case 'underweight':
                $recommendations[] = __('Focus on healthy weight gain with nutrient-dense, calorie-rich foods.', 'diet-calculator');
                $recommendations[] = __('Include healthy fats like nuts, avocados, and olive oil in your meals.', 'diet-calculator');
                break;
            
            case 'overweight':
            case 'obese':
                $recommendations[] = __('Prioritize whole foods and increase physical activity gradually.', 'diet-calculator');
                $recommendations[] = __('Focus on creating a moderate calorie deficit for sustainable weight loss.', 'diet-calculator');
                break;
            
            case 'normal':
                $recommendations[] = __('Maintain your healthy weight with balanced nutrition and regular exercise.', 'diet-calculator');
                break;
        }

        // Age-based recommendations
        if ($data['age'] > 50) {
            $recommendations[] = __('Ensure adequate calcium and vitamin D intake for bone health.', 'diet-calculator');
            $recommendations[] = __('Consider adding resistance training to maintain muscle mass.', 'diet-calculator');
        }

        // Sex-specific recommendations
        if ($data['sex'] === 'female' && $data['age'] < 50) {
            $recommendations[] = __('Ensure adequate iron intake, especially if you experience heavy menstrual periods.', 'diet-calculator');
        }

        // Goal-specific recommendations
        switch ($data['goal']) {
            case 'build_muscle':
                $recommendations[] = __('Consume protein within 2 hours after strength training for optimal muscle synthesis.', 'diet-calculator');
                $recommendations[] = __('Aim for 1.6-2.2g protein per kg of body weight daily.', 'diet-calculator');
                break;
            
            case 'lose_weight':
                $recommendations[] = __('Eat slowly and mindfully to help recognize satiety cues.', 'diet-calculator');
                $recommendations[] = __('Include fiber-rich foods to help you feel full with fewer calories.', 'diet-calculator');
                break;
            
            case 'athletic_performance':
                $recommendations[] = __('Time your carbohydrate intake around training sessions for optimal performance.', 'diet-calculator');
                $recommendations[] = __('Stay well-hydrated before, during, and after exercise.', 'diet-calculator');
                break;
        }

        return $recommendations;
    }
}