<?php
/**
 * Admin interface for Diet Calculator Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DietCalculator_Admin {

    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_diet_calculator_download_pdf', array($this, 'download_pdf'));
        add_action('wp_ajax_nopriv_diet_calculator_download_pdf', array($this, 'download_pdf'));
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('diet_calculator_settings', 'diet_calculator_huggingface_api_key');
        register_setting('diet_calculator_settings', 'diet_calculator_enable_ai');
        register_setting('diet_calculator_settings', 'diet_calculator_auto_cleanup_days');
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        add_menu_page(
            __('Diet Calculator', 'diet-calculator'),
            __('Diet Calculator', 'diet-calculator'),
            'manage_options',
            'diet-calculator',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'diet-calculator',
            __('All Plans', 'diet-calculator'),
            __('All Plans', 'diet-calculator'),
            'manage_options',
            'diet-calculator-plans',
            array($this, 'plans_page')
        );

        add_submenu_page(
            'diet-calculator',
            __('Settings', 'diet-calculator'),
            __('Settings', 'diet-calculator'),
            'manage_options',
            'diet-calculator-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'diet-calculator',
            __('Help', 'diet-calculator'),
            __('Help', 'diet-calculator'),
            'manage_options',
            'diet-calculator-help',
            array($this, 'help_page')
        );
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        $database = new DietCalculator_Database();
        
        // Get recent plans
        global $wpdb;
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';
        $users_table = $wpdb->prefix . 'diet_calculator_users';
        
        $recent_plans = $wpdb->get_results("
            SELECT p.*, u.goal, u.sex, u.age 
            FROM $plans_table p 
            JOIN $users_table u ON p.user_entry_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");

        $total_plans = $wpdb->get_var("SELECT COUNT(*) FROM $plans_table");
        $plans_today = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $plans_table 
            WHERE DATE(created_at) = %s
        ", date('Y-m-d')));

        ?>
        <div class="wrap">
            <h1><?php _e('Diet Calculator Dashboard', 'diet-calculator'); ?></h1>
            
            <div class="diet-calculator-stats">
                <div class="stat-box">
                    <h3><?php echo $total_plans; ?></h3>
                    <p><?php _e('Total Plans Generated', 'diet-calculator'); ?></p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $plans_today; ?></h3>
                    <p><?php _e('Plans Today', 'diet-calculator'); ?></p>
                </div>
            </div>

            <h2><?php _e('Recent Diet Plans', 'diet-calculator'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'diet-calculator'); ?></th>
                        <th><?php _e('Goal', 'diet-calculator'); ?></th>
                        <th><?php _e('Demographics', 'diet-calculator'); ?></th>
                        <th><?php _e('Calories', 'diet-calculator'); ?></th>
                        <th><?php _e('Created', 'diet-calculator'); ?></th>
                        <th><?php _e('Actions', 'diet-calculator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_plans): ?>
                        <?php foreach ($recent_plans as $plan): ?>
                        <tr>
                            <td><?php echo $plan->id; ?></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $plan->goal)); ?></td>
                            <td><?php echo ucfirst($plan->sex) . ', ' . $plan->age . ' years'; ?></td>
                            <td><?php echo round($plan->daily_calories); ?> kcal</td>
                            <td><?php echo date('Y-m-d H:i', strtotime($plan->created_at)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin-ajax.php?action=diet_calculator_download_pdf&plan_id=' . $plan->id); ?>" class="button button-small" target="_blank">
                                    <?php _e('Download PDF', 'diet-calculator'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php _e('No plans generated yet.', 'diet-calculator'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="diet-calculator-shortcodes">
                <h2><?php _e('How to Use', 'diet-calculator'); ?></h2>
                <p><?php _e('Use these shortcodes to display the diet calculator on your pages:', 'diet-calculator'); ?></p>
                <ul>
                    <li><code>[diet_calculator]</code> - <?php _e('Full diet calculator with 4-step wizard', 'diet-calculator'); ?></li>
                    <li><code>[diet_calculator_form]</code> - <?php _e('Simple form version', 'diet-calculator'); ?></li>
                    <li><code>[diet_calculator_results plan_id="123"]</code> - <?php _e('Display specific plan results', 'diet-calculator'); ?></li>
                </ul>
            </div>
        </div>

        <style>
        .diet-calculator-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            min-width: 150px;
        }
        .stat-box h3 {
            font-size: 2em;
            margin: 0;
            color: #0073aa;
        }
        .stat-box p {
            margin: 5px 0 0;
            color: #666;
        }
        .diet-calculator-shortcodes {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        .diet-calculator-shortcodes code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
        }
        </style>
        <?php
    }

    /**
     * Plans management page
     */
    public function plans_page() {
        global $wpdb;
        
        $plans_table = $wpdb->prefix . 'diet_calculator_plans';
        $users_table = $wpdb->prefix . 'diet_calculator_users';
        
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['plan_ids'])) {
            $plan_ids = array_map('intval', $_POST['plan_ids']);
            $placeholders = implode(',', array_fill(0, count($plan_ids), '%d'));
            
            $wpdb->query($wpdb->prepare("
                DELETE p, u FROM $plans_table p 
                JOIN $users_table u ON p.user_entry_id = u.id 
                WHERE p.id IN ($placeholders)
            ", ...$plan_ids));
            
            echo '<div class="notice notice-success"><p>' . sprintf(__('%d plans deleted successfully.', 'diet-calculator'), count($plan_ids)) . '</p></div>';
        }
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total_plans = $wpdb->get_var("SELECT COUNT(*) FROM $plans_table");
        $total_pages = ceil($total_plans / $per_page);
        
        $plans = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, u.goal, u.sex, u.age, u.dietary_preferences, u.food_allergies 
            FROM $plans_table p 
            JOIN $users_table u ON p.user_entry_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT %d OFFSET %d
        ", $per_page, $offset));

        ?>
        <div class="wrap">
            <h1><?php _e('All Diet Plans', 'diet-calculator'); ?></h1>
            
            <form method="post" action="">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value="-1"><?php _e('Bulk Actions', 'diet-calculator'); ?></option>
                            <option value="delete"><?php _e('Delete', 'diet-calculator'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Apply', 'diet-calculator'); ?>">
                    </div>
                    
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(__('%s items', 'diet-calculator'), $total_plans); ?></span>
                        <?php if ($total_pages > 1): ?>
                            <?php echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $current_page
                            )); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" />
                            </td>
                            <th><?php _e('ID', 'diet-calculator'); ?></th>
                            <th><?php _e('Goal', 'diet-calculator'); ?></th>
                            <th><?php _e('Demographics', 'diet-calculator'); ?></th>
                            <th><?php _e('Restrictions', 'diet-calculator'); ?></th>
                            <th><?php _e('Calories', 'diet-calculator'); ?></th>
                            <th><?php _e('Created', 'diet-calculator'); ?></th>
                            <th><?php _e('Actions', 'diet-calculator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($plans): ?>
                            <?php foreach ($plans as $plan): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="plan_ids[]" value="<?php echo $plan->id; ?>" />
                                </th>
                                <td><?php echo $plan->id; ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $plan->goal)); ?></td>
                                <td><?php echo ucfirst($plan->sex) . ', ' . $plan->age . ' years'; ?></td>
                                <td>
                                    <?php
                                    $restrictions = array();
                                    $dietary_prefs = json_decode($plan->dietary_preferences, true);
                                    $allergies = json_decode($plan->food_allergies, true);
                                    
                                    if ($dietary_prefs) {
                                        $restrictions = array_merge($restrictions, $dietary_prefs);
                                    }
                                    if ($allergies) {
                                        $restrictions = array_merge($restrictions, $allergies);
                                    }
                                    
                                    echo $restrictions ? implode(', ', array_slice($restrictions, 0, 3)) : __('None', 'diet-calculator');
                                    if (count($restrictions) > 3) {
                                        echo '...';
                                    }
                                    ?>
                                </td>
                                <td><?php echo round($plan->daily_calories); ?> kcal</td>
                                <td><?php echo date('Y-m-d H:i', strtotime($plan->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin-ajax.php?action=diet_calculator_download_pdf&plan_id=' . $plan->id); ?>" class="button button-small" target="_blank">
                                        <?php _e('PDF', 'diet-calculator'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8"><?php _e('No plans found.', 'diet-calculator'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('diet_calculator_huggingface_api_key', sanitize_text_field($_POST['huggingface_api_key']));
            update_option('diet_calculator_enable_ai', isset($_POST['enable_ai']) ? 1 : 0);
            update_option('diet_calculator_auto_cleanup_days', intval($_POST['auto_cleanup_days']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'diet-calculator') . '</p></div>';
        }

        $api_key = get_option('diet_calculator_huggingface_api_key', '');
        $enable_ai = get_option('diet_calculator_enable_ai', 1);
        $cleanup_days = get_option('diet_calculator_auto_cleanup_days', 90);
        ?>
        <div class="wrap">
            <h1><?php _e('Diet Calculator Settings', 'diet-calculator'); ?></h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable AI Features', 'diet-calculator'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_ai" value="1" <?php checked($enable_ai, 1); ?>>
                                <?php _e('Enable AI-powered meal recommendations', 'diet-calculator'); ?>
                            </label>
                            <p class="description"><?php _e('Uncheck to use only fallback recommendations without API calls.', 'diet-calculator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Hugging Face API Key', 'diet-calculator'); ?></th>
                        <td>
                            <input type="password" name="huggingface_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('Get your API key from', 'diet-calculator'); ?> 
                                <a href="https://huggingface.co/settings/tokens" target="_blank">Hugging Face</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Auto Cleanup', 'diet-calculator'); ?></th>
                        <td>
                            <input type="number" name="auto_cleanup_days" value="<?php echo esc_attr($cleanup_days); ?>" min="30" max="365">
                            <p class="description"><?php _e('Automatically delete anonymous user data older than this many days.', 'diet-calculator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Help page
     */
    public function help_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Diet Calculator Help', 'diet-calculator'); ?></h1>
            
            <div class="help-section">
                <h2><?php _e('Getting Started', 'diet-calculator'); ?></h2>
                <p><?php _e('The AI Diet Calculator plugin provides a comprehensive nutrition planning tool for your website visitors.', 'diet-calculator'); ?></p>
                
                <h3><?php _e('Basic Setup', 'diet-calculator'); ?></h3>
                <ol>
                    <li><?php _e('Add the shortcode [diet_calculator] to any page or post', 'diet-calculator'); ?></li>
                    <li><?php _e('Configure your Hugging Face API key in Settings (optional)', 'diet-calculator'); ?></li>
                    <li><?php _e('Customize the styling to match your theme', 'diet-calculator'); ?></li>
                </ol>
                
                <h3><?php _e('Available Shortcodes', 'diet-calculator'); ?></h3>
                <ul>
                    <li><code>[diet_calculator]</code> - <?php _e('Complete 4-step diet calculator wizard', 'diet-calculator'); ?></li>
                    <li><code>[diet_calculator_form style="simple"]</code> - <?php _e('Simplified form version', 'diet-calculator'); ?></li>
                    <li><code>[diet_calculator_results plan_id="123"]</code> - <?php _e('Display specific plan results', 'diet-calculator'); ?></li>
                </ul>
                
                <h3><?php _e('Features', 'diet-calculator'); ?></h3>
                <ul>
                    <li><?php _e('BMR/TDEE calculations using Mifflin-St Jeor equation', 'diet-calculator'); ?></li>
                    <li><?php _e('AI-powered meal recommendations with food categorization', 'diet-calculator'); ?></li>
                    <li><?php _e('Comprehensive allergy and dietary preference handling', 'diet-calculator'); ?></li>
                    <li><?php _e('PDF report generation with shopping lists', 'diet-calculator'); ?></li>
                    <li><?php _e('Evidence-based supplement recommendations', 'diet-calculator'); ?></li>
                    <li><?php _e('Meal prep tips and hydration schedules', 'diet-calculator'); ?></li>
                </ul>
                
                <h3><?php _e('Support', 'diet-calculator'); ?></h3>
                <p><?php _e('For support and updates, visit:', 'diet-calculator'); ?> 
                   <a href="https://github.com/askshameer/diet-calculator" target="_blank">GitHub Repository</a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Handle PDF download
     */
    public function download_pdf() {
        if (!isset($_GET['plan_id'])) {
            wp_die(__('Plan ID is required.', 'diet-calculator'));
        }

        $plan_id = intval($_GET['plan_id']);
        $database = new DietCalculator_Database();
        $plan_data = $database->get_meal_plan($plan_id);

        if (!$plan_data) {
            wp_die(__('Plan not found.', 'diet-calculator'));
        }

        // Generate PDF
        $pdf_generator = new DietCalculator_PDF_Generator();
        $pdf_generator->generate_pdf($plan_data, $plan_data['meal_plan']);
        
        exit;
    }
}