<?php
/**
 * Shortcodes for Diet Calculator Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DietCalculator_Shortcodes {

    public function __construct() {
        add_shortcode('diet_calculator', array($this, 'diet_calculator_shortcode'));
        add_shortcode('diet_calculator_form', array($this, 'diet_calculator_form_shortcode'));
        add_shortcode('diet_calculator_results', array($this, 'diet_calculator_results_shortcode'));
    }

    /**
     * Main diet calculator shortcode
     */
    public function diet_calculator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'steps' => '4',
            'show_progress' => 'true'
        ), $atts);

        ob_start();
        ?>
        <div id="diet-calculator-app" class="diet-calculator-container" data-style="<?php echo esc_attr($atts['style']); ?>">
            <?php echo $this->render_calculator_form($atts); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the main calculator form
     */
    private function render_calculator_form($atts) {
        ob_start();
        ?>
        <div class="diet-calculator-form-wrapper">
            <div class="diet-calculator-header">
                <h2><?php _e('AI-Powered Diet Calculator', 'diet-calculator'); ?></h2>
                <p><?php _e('Get personalized meal recommendations based on your goals and preferences', 'diet-calculator'); ?></p>
            </div>

            <?php if ($atts['show_progress'] === 'true'): ?>
            <div class="diet-calculator-progress">
                <div class="progress-steps">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label"><?php _e('Basic Info', 'diet-calculator'); ?></span>
                    </div>
                    <div class="step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label"><?php _e('Goals', 'diet-calculator'); ?></span>
                    </div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label"><?php _e('Activity', 'diet-calculator'); ?></span>
                    </div>
                    <div class="step" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-label"><?php _e('Preferences', 'diet-calculator'); ?></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 25%;"></div>
                </div>
            </div>
            <?php endif; ?>

            <form id="diet-calculator-form" class="diet-calculator-form">
                <?php wp_nonce_field('diet_calculator_nonce', 'diet_calculator_nonce'); ?>
                
                <!-- Step 1: Basic Information -->
                <div class="form-step active" data-step="1">
                    <h3><?php _e('Basic Information', 'diet-calculator'); ?></h3>
                    <p class="step-description"><?php _e('Tell us about yourself to get started', 'diet-calculator'); ?></p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="height"><?php _e('Height (cm)', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <input type="number" id="height" name="height" min="100" max="250" required>
                        </div>
                        <div class="form-group">
                            <label for="weight"><?php _e('Weight (kg)', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <input type="number" id="weight" name="weight" min="30" max="300" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="age"><?php _e('Age', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <input type="number" id="age" name="age" min="13" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="sex"><?php _e('Sex', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <select id="sex" name="sex" required>
                                <option value=""><?php _e('Select...', 'diet-calculator'); ?></option>
                                <option value="male"><?php _e('Male', 'diet-calculator'); ?></option>
                                <option value="female"><?php _e('Female', 'diet-calculator'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Goals & Timeline -->
                <div class="form-step" data-step="2">
                    <h3><?php _e('Goals & Timeline', 'diet-calculator'); ?></h3>
                    <p class="step-description"><?php _e('What do you want to achieve?', 'diet-calculator'); ?></p>
                    
                    <div class="form-group">
                        <label for="goal"><?php _e('Primary Goal', 'diet-calculator'); ?> <span class="required">*</span></label>
                        <select id="goal" name="goal" required>
                            <option value=""><?php _e('Select your goal...', 'diet-calculator'); ?></option>
                            <option value="lose_weight"><?php _e('Lose Weight', 'diet-calculator'); ?></option>
                            <option value="build_muscle"><?php _e('Build Muscle', 'diet-calculator'); ?></option>
                            <option value="athletic_performance"><?php _e('Athletic Performance', 'diet-calculator'); ?></option>
                            <option value="body_recomposition"><?php _e('Body Recomposition', 'diet-calculator'); ?></option>
                            <option value="improve_health"><?php _e('Improve Health', 'diet-calculator'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="timeline"><?php _e('Timeline (weeks)', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <input type="number" id="timeline" name="timeline" min="1" max="104" required>
                        </div>
                        <div class="form-group" id="goal-weight-group" style="display: none;">
                            <label for="goalWeight"><?php _e('Goal Weight (kg)', 'diet-calculator'); ?></label>
                            <input type="number" id="goalWeight" name="goalWeight" min="30" max="300">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Activity Level -->
                <div class="form-step" data-step="3">
                    <h3><?php _e('Activity Level', 'diet-calculator'); ?></h3>
                    <p class="step-description"><?php _e('How active are you?', 'diet-calculator'); ?></p>
                    
                    <div class="form-group">
                        <label for="dailyActivityLevel"><?php _e('Daily Activity Level', 'diet-calculator'); ?> <span class="required">*</span></label>
                        <select id="dailyActivityLevel" name="dailyActivityLevel" required>
                            <option value=""><?php _e('Select activity level...', 'diet-calculator'); ?></option>
                            <option value="sedentary"><?php _e('Sedentary (desk job, no exercise)', 'diet-calculator'); ?></option>
                            <option value="lightly_active"><?php _e('Lightly Active (light exercise 1-3 days/week)', 'diet-calculator'); ?></option>
                            <option value="moderately_active"><?php _e('Moderately Active (moderate exercise 3-5 days/week)', 'diet-calculator'); ?></option>
                            <option value="very_active"><?php _e('Very Active (hard exercise 6-7 days/week)', 'diet-calculator'); ?></option>
                            <option value="extremely_active"><?php _e('Extremely Active (very hard exercise, physical job)', 'diet-calculator'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exerciseIntensity"><?php _e('Exercise Intensity', 'diet-calculator'); ?> <span class="required">*</span></label>
                        <select id="exerciseIntensity" name="exerciseIntensity" required>
                            <option value=""><?php _e('Select exercise intensity...', 'diet-calculator'); ?></option>
                            <option value="low"><?php _e('Low Intensity', 'diet-calculator'); ?></option>
                            <option value="moderate"><?php _e('Moderate Intensity', 'diet-calculator'); ?></option>
                            <option value="high"><?php _e('High Intensity', 'diet-calculator'); ?></option>
                            <option value="very_high"><?php _e('Very High Intensity', 'diet-calculator'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Step 4: Dietary Preferences -->
                <div class="form-step" data-step="4">
                    <h3><?php _e('Dietary Preferences', 'diet-calculator'); ?></h3>
                    <p class="step-description"><?php _e('Tell us about your dietary needs and preferences', 'diet-calculator'); ?></p>
                    
                    <div class="form-group">
                        <label><?php _e('Dietary Preferences', 'diet-calculator'); ?></label>
                        <div class="checkbox-group">
                            <?php
                            $preferences = array(
                                'vegetarian' => __('Vegetarian', 'diet-calculator'),
                                'vegan' => __('Vegan', 'diet-calculator'),
                                'keto' => __('Keto', 'diet-calculator'),
                                'paleo' => __('Paleo', 'diet-calculator'),
                                'mediterranean' => __('Mediterranean', 'diet-calculator'),
                                'low_carb' => __('Low Carb', 'diet-calculator'),
                                'high_protein' => __('High Protein', 'diet-calculator'),
                                'gluten_free' => __('Gluten Free', 'diet-calculator')
                            );
                            foreach ($preferences as $value => $label):
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="dietaryPreferences[]" value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?php _e('Food Allergies', 'diet-calculator'); ?></label>
                        <div class="checkbox-group">
                            <?php
                            $allergies = array(
                                'nuts' => __('Nuts', 'diet-calculator'),
                                'dairy' => __('Dairy', 'diet-calculator'),
                                'eggs' => __('Eggs', 'diet-calculator'),
                                'shellfish' => __('Shellfish', 'diet-calculator'),
                                'soy' => __('Soy', 'diet-calculator'),
                                'wheat' => __('Wheat', 'diet-calculator'),
                                'fish' => __('Fish', 'diet-calculator'),
                                'sesame' => __('Sesame', 'diet-calculator')
                            );
                            foreach ($allergies as $value => $label):
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="foodAllergies[]" value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?php _e('Food Intolerances', 'diet-calculator'); ?></label>
                        <div class="checkbox-group">
                            <?php
                            $intolerances = array(
                                'lactose' => __('Lactose', 'diet-calculator'),
                                'gluten' => __('Gluten', 'diet-calculator'),
                                'fructose' => __('Fructose', 'diet-calculator'),
                                'histamine' => __('Histamine', 'diet-calculator'),
                                'fodmap' => __('FODMAP', 'diet-calculator')
                            );
                            foreach ($intolerances as $value => $label):
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="foodIntolerances[]" value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="macronutrientRatio"><?php _e('Macronutrient Ratio', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <select id="macronutrientRatio" name="macronutrientRatio" required>
                                <option value=""><?php _e('Select macro ratio...', 'diet-calculator'); ?></option>
                                <option value="balanced"><?php _e('Balanced (30/40/30)', 'diet-calculator'); ?></option>
                                <option value="high_protein"><?php _e('High Protein (40/30/30)', 'diet-calculator'); ?></option>
                                <option value="low_carb"><?php _e('Low Carb (35/15/50)', 'diet-calculator'); ?></option>
                                <option value="high_carb"><?php _e('High Carb (20/60/20)', 'diet-calculator'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mealsPerDay"><?php _e('Meals Per Day', 'diet-calculator'); ?> <span class="required">*</span></label>
                            <input type="number" id="mealsPerDay" name="mealsPerDay" min="1" max="8" value="3" required>
                        </div>
                    </div>
                </div>

                <div class="form-navigation">
                    <button type="button" id="prev-step" class="btn btn-secondary" style="display: none;"><?php _e('Previous', 'diet-calculator'); ?></button>
                    <button type="button" id="next-step" class="btn btn-primary"><?php _e('Next', 'diet-calculator'); ?></button>
                    <button type="submit" id="submit-form" class="btn btn-success" style="display: none;"><?php _e('Generate Diet Plan', 'diet-calculator'); ?></button>
                </div>
            </form>

            <div id="diet-calculator-results" class="diet-calculator-results" style="display: none;">
                <!-- Results will be loaded here via AJAX -->
            </div>

            <div id="diet-calculator-loading" class="diet-calculator-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <p><?php _e('Generating your personalized meal plan...', 'diet-calculator'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Diet calculator form shortcode (simplified)
     */
    public function diet_calculator_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'simple'
        ), $atts);

        return $this->render_calculator_form($atts);
    }

    /**
     * Diet calculator results shortcode
     */
    public function diet_calculator_results_shortcode($atts) {
        $atts = shortcode_atts(array(
            'plan_id' => 0
        ), $atts);

        if (!$atts['plan_id']) {
            return '<p>' . __('No plan ID provided.', 'diet-calculator') . '</p>';
        }

        $database = new DietCalculator_Database();
        $plan_data = $database->get_meal_plan($atts['plan_id']);

        if (!$plan_data) {
            return '<p>' . __('Plan not found.', 'diet-calculator') . '</p>';
        }

        ob_start();
        ?>
        <div class="diet-calculator-results-display">
            <h3><?php _e('Your Diet Plan Results', 'diet-calculator'); ?></h3>
            
            <div class="nutrition-summary">
                <h4><?php _e('Daily Nutrition Targets', 'diet-calculator'); ?></h4>
                <ul>
                    <li><?php _e('Calories:', 'diet-calculator'); ?> <?php echo round($plan_data['daily_calories']); ?> kcal</li>
                    <li><?php _e('Protein:', 'diet-calculator'); ?> <?php echo round($plan_data['protein_grams']); ?>g</li>
                    <li><?php _e('Carbs:', 'diet-calculator'); ?> <?php echo round($plan_data['carb_grams']); ?>g</li>
                    <li><?php _e('Fat:', 'diet-calculator'); ?> <?php echo round($plan_data['fat_grams']); ?>g</li>
                    <li><?php _e('Water:', 'diet-calculator'); ?> <?php echo round($plan_data['water_intake']); ?>ml</li>
                </ul>
            </div>

            <div class="plan-actions">
                <a href="<?php echo admin_url('admin-ajax.php?action=diet_calculator_download_pdf&plan_id=' . $plan_data['id']); ?>" class="btn btn-primary" target="_blank">
                    <?php _e('Download PDF Report', 'diet-calculator'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}