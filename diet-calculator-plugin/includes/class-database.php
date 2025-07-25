<?php
/**
 * Database handler for Diet Calculator Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DietCalculator_Database {

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Users table for diet calculations
        $users_table = $wpdb->prefix . 'diet_calculator_users';
        $users_sql = "CREATE TABLE $users_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            session_id varchar(255) NOT NULL,
            height int(11) NOT NULL,
            weight int(11) NOT NULL,
            age int(11) NOT NULL,
            sex varchar(10) NOT NULL,
            goal varchar(50) NOT NULL,
            timeline int(11) NOT NULL,
            goal_weight int(11) DEFAULT NULL,
            exercise_intensity varchar(20) NOT NULL,
            daily_activity_level varchar(30) NOT NULL,
            dietary_preferences text DEFAULT NULL,
            food_allergies text DEFAULT NULL,
            food_intolerances text DEFAULT NULL,
            macronutrient_ratio varchar(20) NOT NULL,
            meals_per_day int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";

        // Nutrition plans table
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';
        $plans_sql = "CREATE TABLE $plans_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_entry_id mediumint(9) NOT NULL,
            bmr decimal(8,2) NOT NULL,
            tdee decimal(8,2) NOT NULL,
            daily_calories decimal(8,2) NOT NULL,
            protein_grams decimal(8,2) NOT NULL,
            carb_grams decimal(8,2) NOT NULL,
            fat_grams decimal(8,2) NOT NULL,
            water_intake decimal(8,2) NOT NULL,
            meal_plan longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_entry_id (user_entry_id)
        ) $charset_collate;";

        // Progress tracking table
        $progress_table = $wpdb->prefix . 'diet_calculator_progress';
        $progress_sql = "CREATE TABLE $progress_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_entry_id mediumint(9) NOT NULL,
            current_weight decimal(5,2) NOT NULL,
            notes text DEFAULT NULL,
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_entry_id (user_entry_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($users_sql);
        dbDelta($plans_sql);
        dbDelta($progress_sql);
    }

    /**
     * Save user data and meal plan
     */
    public function save_meal_plan($nutrition_data, $meal_plan) {
        global $wpdb;

        $users_table = $wpdb->prefix . 'diet_calculator_users';
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Insert user data
            $user_data = array(
                'user_id' => get_current_user_id() ?: null,
                'session_id' => $this->get_session_id(),
                'height' => $nutrition_data['height'],
                'weight' => $nutrition_data['weight'],
                'age' => $nutrition_data['age'],
                'sex' => $nutrition_data['sex'],
                'goal' => $nutrition_data['goal'],
                'timeline' => $nutrition_data['timeline'],
                'goal_weight' => $nutrition_data['goalWeight'],
                'exercise_intensity' => $nutrition_data['exerciseIntensity'],
                'daily_activity_level' => $nutrition_data['dailyActivityLevel'],
                'dietary_preferences' => json_encode($nutrition_data['dietaryPreferences']),
                'food_allergies' => json_encode($nutrition_data['foodAllergies']),
                'food_intolerances' => json_encode($nutrition_data['foodIntolerances']),
                'macronutrient_ratio' => $nutrition_data['macronutrientRatio'],
                'meals_per_day' => $nutrition_data['mealsPerDay']
            );

            $user_result = $wpdb->insert($users_table, $user_data);
            
            if ($user_result === false) {
                throw new Exception('Failed to save user data');
            }

            $user_entry_id = $wpdb->insert_id;

            // Insert nutrition plan
            $plan_data = array(
                'user_entry_id' => $user_entry_id,
                'bmr' => $nutrition_data['bmr'],
                'tdee' => $nutrition_data['tdee'],
                'daily_calories' => $nutrition_data['dailyCalories'],
                'protein_grams' => $nutrition_data['proteinGrams'],
                'carb_grams' => $nutrition_data['carbGrams'],
                'fat_grams' => $nutrition_data['fatGrams'],
                'water_intake' => $nutrition_data['waterIntake'],
                'meal_plan' => json_encode($meal_plan)
            );

            $plan_result = $wpdb->insert($plans_table, $plan_data);
            
            if ($plan_result === false) {
                throw new Exception('Failed to save meal plan');
            }

            $plan_id = $wpdb->insert_id;

            // Commit transaction
            $wpdb->query('COMMIT');

            return $plan_id;

        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Get meal plan by ID
     */
    public function get_meal_plan($plan_id) {
        global $wpdb;

        $users_table = $wpdb->prefix . 'diet_calculator_users';
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';

        $sql = "SELECT u.*, p.* FROM $plans_table p 
                JOIN $users_table u ON p.user_entry_id = u.id 
                WHERE p.id = %d";

        $result = $wpdb->get_row($wpdb->prepare($sql, $plan_id), ARRAY_A);

        if (!$result) {
            return null;
        }

        // Decode JSON fields
        $result['dietary_preferences'] = json_decode($result['dietary_preferences'], true) ?: array();
        $result['food_allergies'] = json_decode($result['food_allergies'], true) ?: array();
        $result['food_intolerances'] = json_decode($result['food_intolerances'], true) ?: array();
        $result['meal_plan'] = json_decode($result['meal_plan'], true) ?: array();

        return $result;
    }

    /**
     * Get user meal plans
     */
    public function get_user_meal_plans($user_id = null, $session_id = null) {
        global $wpdb;

        $users_table = $wpdb->prefix . 'diet_calculator_users';
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';

        if ($user_id) {
            $sql = "SELECT u.*, p.* FROM $plans_table p 
                    JOIN $users_table u ON p.user_entry_id = u.id 
                    WHERE u.user_id = %d 
                    ORDER BY p.created_at DESC";
            $results = $wpdb->get_results($wpdb->prepare($sql, $user_id), ARRAY_A);
        } elseif ($session_id) {
            $sql = "SELECT u.*, p.* FROM $plans_table p 
                    JOIN $users_table u ON p.user_entry_id = u.id 
                    WHERE u.session_id = %s 
                    ORDER BY p.created_at DESC";
            $results = $wpdb->get_results($wpdb->prepare($sql, $session_id), ARRAY_A);
        } else {
            return array();
        }

        // Decode JSON fields for each result
        foreach ($results as &$result) {
            $result['dietary_preferences'] = json_decode($result['dietary_preferences'], true) ?: array();
            $result['food_allergies'] = json_decode($result['food_allergies'], true) ?: array();
            $result['food_intolerances'] = json_decode($result['food_intolerances'], true) ?: array();
            $result['meal_plan'] = json_decode($result['meal_plan'], true) ?: array();
        }

        return $results;
    }

    /**
     * Save progress tracking
     */
    public function save_progress($user_entry_id, $current_weight, $notes = '') {
        global $wpdb;

        $progress_table = $wpdb->prefix . 'diet_calculator_progress';

        $data = array(
            'user_entry_id' => $user_entry_id,
            'current_weight' => $current_weight,
            'notes' => $notes
        );

        $result = $wpdb->insert($progress_table, $data);

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get progress tracking
     */
    public function get_progress($user_entry_id) {
        global $wpdb;

        $progress_table = $wpdb->prefix . 'diet_calculator_progress';

        $sql = "SELECT * FROM $progress_table WHERE user_entry_id = %d ORDER BY recorded_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $user_entry_id), ARRAY_A);
    }

    /**
     * Get or create session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['diet_calculator_session'])) {
            $_SESSION['diet_calculator_session'] = wp_generate_password(32, false);
        }
        
        return $_SESSION['diet_calculator_session'];
    }

    /**
     * Clean up old data (for cron job)
     */
    public function cleanup_old_data($days = 90) {
        global $wpdb;

        $users_table = $wpdb->prefix . 'diet_calculator_users';
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';
        $progress_table = $wpdb->prefix . 'diet_calculator_progress';

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Delete old anonymous user data (users without user_id)
        $old_users = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $users_table WHERE user_id IS NULL AND created_at < %s",
            $date_threshold
        ));

        if (!empty($old_users)) {
            $ids_placeholder = implode(',', array_fill(0, count($old_users), '%d'));
            
            // Delete progress records
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $progress_table WHERE user_entry_id IN ($ids_placeholder)",
                ...$old_users
            ));
            
            // Delete meal plans
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $plans_table WHERE user_entry_id IN ($ids_placeholder)",
                ...$old_users
            ));
            
            // Delete user entries
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $users_table WHERE id IN ($ids_placeholder)",
                ...$old_users
            ));
        }

        return count($old_users);
    }
}