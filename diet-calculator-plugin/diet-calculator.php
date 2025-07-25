<?php
/**
 * Plugin Name: AI Diet Calculator
 * Plugin URI: https://github.com/askshameer/diet-calculator
 * Description: AI-powered diet calculator with personalized meal recommendations, PDF reports, and comprehensive nutrition planning.
 * Version: 1.0.0
 * Author: Shameer
 * Author URI: https://github.com/askshameer
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: diet-calculator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * This plugin provides a comprehensive AI-powered diet calculator with:
 * - 4-step wizard form for user input
 * - BMR/TDEE calculations using Mifflin-St Jeor equation
 * - AI-powered meal recommendations with food categorization
 * - PDF report generation with allergy-aware suggestions
 * - Shopping lists and meal prep strategies
 * - Evidence-based supplement recommendations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DIET_CALCULATOR_VERSION', '1.0.0');
define('DIET_CALCULATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DIET_CALCULATOR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DIET_CALCULATOR_PLUGIN_FILE', __FILE__);

/**
 * Main Diet Calculator Plugin Class
 */
class DietCalculatorPlugin {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_diet_calculator_generate_plan', array($this, 'ajax_generate_meal_plan'));
        add_action('wp_ajax_nopriv_diet_calculator_generate_plan', array($this, 'ajax_generate_meal_plan'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once DIET_CALCULATOR_PLUGIN_PATH . 'includes/class-database.php';
        require_once DIET_CALCULATOR_PLUGIN_PATH . 'includes/class-calculations.php';
        require_once DIET_CALCULATOR_PLUGIN_PATH . 'includes/class-ai-client.php';
        require_once DIET_CALCULATOR_PLUGIN_PATH . 'includes/class-pdf-generator.php';
        require_once DIET_CALCULATOR_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once DIET_CALCULATOR_PLUGIN_PATH . 'includes/class-admin.php';
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database = new DietCalculator_Database();
        $database->create_tables();
        
        // Set default options
        update_option('diet_calculator_version', DIET_CALCULATOR_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary data
        delete_transient('diet_calculator_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('diet-calculator', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize shortcodes
        new DietCalculator_Shortcodes();
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'diet-calculator-frontend',
            DIET_CALCULATOR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            DIET_CALCULATOR_VERSION,
            true
        );

        wp_enqueue_style(
            'diet-calculator-frontend',
            DIET_CALCULATOR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            DIET_CALCULATOR_VERSION
        );

        // Localize script for AJAX
        wp_localize_script('diet-calculator-frontend', 'dietCalculatorAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('diet_calculator_nonce'),
            'strings' => array(
                'loading' => __('Generating your personalized meal plan...', 'diet-calculator'),
                'error' => __('Error generating meal plan. Please try again.', 'diet-calculator'),
            )
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'diet-calculator') === false) {
            return;
        }

        wp_enqueue_script(
            'diet-calculator-admin',
            DIET_CALCULATOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DIET_CALCULATOR_VERSION,
            true
        );

        wp_enqueue_style(
            'diet-calculator-admin',
            DIET_CALCULATOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DIET_CALCULATOR_VERSION
        );

        // Localize script for admin AJAX
        wp_localize_script('diet-calculator-admin', 'dietCalculatorAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('diet_calculator_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this plan?', 'diet-calculator'),
                'loading' => __('Loading...', 'diet-calculator'),
                'saved' => __('Settings saved successfully.', 'diet-calculator'),
            ),
            'chartData' => array(
                'labels' => array(), // Will be populated with actual data
                'data' => array()
            )
        ));
    }

    /**
     * AJAX handler for meal plan generation
     */
    public function ajax_generate_meal_plan() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'diet_calculator_nonce')) {
            wp_die(__('Security check failed', 'diet-calculator'));
        }

        // Sanitize input data
        $input_data = $this->sanitize_form_data($_POST['formData']);

        try {
            // Calculate nutrition metrics
            $calculations = new DietCalculator_Calculations();
            $nutrition_data = $calculations->calculate_nutrition_plan($input_data);

            // Generate AI meal recommendations
            $ai_client = new DietCalculator_AI_Client();
            $meal_plan = $ai_client->generate_meal_plan($nutrition_data);

            // Save to database
            $database = new DietCalculator_Database();
            $plan_id = $database->save_meal_plan($nutrition_data, $meal_plan);

            // Return success response with formatted data for frontend
            wp_send_json_success(array(
                'plan_id' => $plan_id,
                'daily_calories' => $nutrition_data['dailyCalories'],
                'protein_grams' => $nutrition_data['proteinGrams'],
                'carb_grams' => $nutrition_data['carbGrams'],
                'fat_grams' => $nutrition_data['fatGrams'],
                'water_intake' => $nutrition_data['waterIntake'],
                'ai_generated' => $meal_plan['aiGenerated'],
                'ai_confidence' => $meal_plan['aiConfidence']
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Sanitize form data
     */
    private function sanitize_form_data($data) {
        return array(
            'height' => intval($data['height']),
            'weight' => intval($data['weight']),
            'age' => intval($data['age']),
            'sex' => sanitize_text_field($data['sex']),
            'goal' => sanitize_text_field($data['goal']),
            'timeline' => intval($data['timeline']),
            'goalWeight' => isset($data['goalWeight']) ? intval($data['goalWeight']) : null,
            'exerciseIntensity' => sanitize_text_field($data['exerciseIntensity']),
            'dailyActivityLevel' => sanitize_text_field($data['dailyActivityLevel']),
            'dietaryPreferences' => array_map('sanitize_text_field', (array)$data['dietaryPreferences']),
            'foodAllergies' => array_map('sanitize_text_field', (array)$data['foodAllergies']),
            'foodIntolerances' => array_map('sanitize_text_field', (array)$data['foodIntolerances']),
            'macronutrientRatio' => sanitize_text_field($data['macronutrientRatio']),
            'mealsPerDay' => intval($data['mealsPerDay']),
        );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $admin = new DietCalculator_Admin();
        $admin->add_menu_pages();
    }
}

// Initialize the plugin
DietCalculatorPlugin::get_instance();