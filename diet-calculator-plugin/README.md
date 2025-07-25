# AI Diet Calculator WordPress Plugin

A comprehensive WordPress plugin that provides AI-powered diet planning with personalized meal recommendations, PDF reports, and modern UI/UX.

## Features

### Core Functionality
- **4-Step Wizard Form**: Intuitive user interface for collecting user data
- **BMR/TDEE Calculations**: Uses the Mifflin-St Jeor equation for accurate metabolic calculations
- **AI-Powered Meal Recommendations**: Integrates with Hugging Face API for intelligent meal planning
- **Food Categorization**: "Prioritize", "Neutral", and "Minimize" food lists based on user goals
- **Allergy & Dietary Preference Handling**: Comprehensive filtering for restrictions and preferences
- **PDF Report Generation**: Professional reports with TCPDF integration
- **Responsive Design**: Modern glass morphism UI that works on all devices

### Admin Features
- **Dashboard**: Overview of generated plans and statistics
- **Plan Management**: View, manage, and export all diet plans
- **Settings**: Configure API keys and plugin options
- **Bulk Actions**: Delete multiple plans at once
- **Help Documentation**: Built-in help system

## Installation

1. Download the plugin files
2. Upload the `diet-calculator-plugin` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure your Hugging Face API key in the plugin settings (optional)

## Usage

### Frontend Display

Use these shortcodes to display the diet calculator on your pages:

```php
[diet_calculator]                           // Full 4-step wizard
[diet_calculator_form style="simple"]       // Simplified form version
[diet_calculator_results plan_id="123"]    // Display specific plan results
```

### Admin Configuration

1. Go to **Diet Calculator > Settings** in your WordPress admin
2. Enter your Hugging Face API key (get one from [Hugging Face](https://huggingface.co/settings/tokens))
3. Configure auto-cleanup settings
4. Save your changes

## API Configuration

### Hugging Face Integration

The plugin uses Hugging Face's Inference API for AI-powered meal recommendations. To enable AI features:

1. Create a free account at [Hugging Face](https://huggingface.co/)
2. Generate an API token from your [settings page](https://huggingface.co/settings/tokens)
3. Enter the token in **Diet Calculator > Settings**

**Note**: The plugin works without an API key using intelligent fallback recommendations.

## Technical Details

### Database Tables
- `wp_diet_calculator_users`: User profile data
- `wp_diet_calculator_plans`: Generated meal plans
- `wp_diet_calculator_progress`: User progress tracking

### File Structure
```
diet-calculator-plugin/
├── diet-calculator.php          // Main plugin file
├── includes/
│   ├── class-admin.php         // Admin interface
│   ├── class-ai-client.php     // Hugging Face integration
│   ├── class-calculations.php  // Nutrition calculations
│   ├── class-database.php     // Database operations
│   ├── class-pdf-generator.php // PDF report generation
│   └── class-shortcodes.php   // Frontend shortcodes
├── assets/
│   ├── js/
│   │   ├── frontend.js        // Frontend JavaScript
│   │   └── admin.js          // Admin JavaScript
│   └── css/
│       ├── frontend.css      // Frontend styles
│       └── admin.css        // Admin styles
└── README.md
```

### Supported Dietary Preferences
- Vegetarian
- Vegan
- Keto
- Paleo
- Mediterranean
- Low Carb
- High Protein
- Gluten Free

### Food Allergies & Intolerances
- Nuts, Dairy, Eggs, Shellfish, Soy, Wheat, Fish, Sesame
- Lactose, Gluten, Fructose, Histamine, FODMAP

## Customization

### Styling
The plugin uses CSS custom properties that can be overridden in your theme:

```css
.diet-calculator-container {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --background-color: rgba(255, 255, 255, 0.95);
}
```

### Hooks & Filters
Available WordPress hooks for developers:

```php
// Filter meal plan data before saving
add_filter('diet_calculator_meal_plan_data', 'my_custom_meal_plan_filter');

// Action after plan generation
add_action('diet_calculator_plan_generated', 'my_plan_generated_callback');

// Filter PDF content
add_filter('diet_calculator_pdf_content', 'my_pdf_content_filter');
```

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **TCPDF**: Included with WordPress
- **cURL**: For API requests

## Security Features

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Data Sanitization**: All user input is sanitized and validated
- **SQL Injection Protection**: Uses WordPress database abstraction
- **XSS Prevention**: All output is properly escaped

## Performance

- **Caching**: Transient caching for API responses
- **Database Optimization**: Indexed database tables
- **Asset Minification**: Compressed CSS and JavaScript
- **Lazy Loading**: Progressive form loading

## Troubleshooting

### Common Issues

1. **PDF Generation Fails**
   - Ensure TCPDF is available
   - Check file permissions
   - Verify memory limits

2. **AI Recommendations Not Working**
   - Check API key configuration
   - Verify internet connectivity
   - Review error logs

3. **Form Submission Errors**
   - Check nonce verification
   - Verify required field validation
   - Review browser console

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0.0
- Initial release
- Full WordPress plugin conversion from Next.js application
- AI-powered meal recommendations
- PDF report generation
- Modern responsive UI
- Comprehensive admin interface

## Support

For support and updates, visit:
- **GitHub Repository**: https://github.com/askshameer/diet-calculator
- **Issues**: https://github.com/askshameer/diet-calculator/issues

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

- **Original Application**: Next.js diet calculator
- **Author**: Shameer
- **AI Integration**: Hugging Face Inference API
- **PDF Generation**: TCPDF
- **UI Framework**: Custom CSS with glass morphism design

---

**Note**: This plugin was converted from a modern Next.js application to maintain all functionality while providing native WordPress integration.