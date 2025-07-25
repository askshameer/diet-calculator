<?php
/**
 * PDF Generator for Diet Calculator Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DietCalculator_PDF_Generator {

    /**
     * Generate PDF report
     */
    public function generate_pdf($data, $meal_plan) {
        // Check if TCPDF is available, if not use alternative approach
        if (!$this->load_tcpdf()) {
            $this->generate_simple_pdf($data, $meal_plan);
            return;
        }

        // Parse meal plan if it's JSON string
        if (is_string($meal_plan)) {
            $meal_plan = json_decode($meal_plan, true);
        }

        // Create new PDF document
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information with fallback constants  
        $pdf->SetCreator(defined('PDF_CREATOR') ? PDF_CREATOR : 'Diet Calculator Plugin');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle(__('Personalized Diet Plan', 'diet-calculator'));
        $pdf->SetSubject(__('AI-Generated Nutrition Plan', 'diet-calculator'));

        // Set default header data
        $pdf->SetHeaderData('', 0, __('AI Diet Calculator', 'diet-calculator'), get_bloginfo('name'));

        // Set header and footer fonts with fallbacks
        $main_font = defined('PDF_FONT_NAME_MAIN') ? PDF_FONT_NAME_MAIN : 'helvetica';
        $main_size = defined('PDF_FONT_SIZE_MAIN') ? PDF_FONT_SIZE_MAIN : 12;
        $data_font = defined('PDF_FONT_NAME_DATA') ? PDF_FONT_NAME_DATA : 'helvetica';
        $data_size = defined('PDF_FONT_SIZE_DATA') ? PDF_FONT_SIZE_DATA : 10;
        
        $pdf->setHeaderFont(array($main_font, '', $main_size));
        $pdf->setFooterFont(array($data_font, '', $data_size));

        // Set default monospaced font
        $mono_font = defined('PDF_FONT_MONOSPACED') ? PDF_FONT_MONOSPACED : 'courier';
        $pdf->SetDefaultMonospacedFont($mono_font);

        // Set margins with fallbacks
        $margin_left = defined('PDF_MARGIN_LEFT') ? PDF_MARGIN_LEFT : 15;
        $margin_top = defined('PDF_MARGIN_TOP') ? PDF_MARGIN_TOP : 27;
        $margin_right = defined('PDF_MARGIN_RIGHT') ? PDF_MARGIN_RIGHT : 15;
        $margin_header = defined('PDF_MARGIN_HEADER') ? PDF_MARGIN_HEADER : 5;
        $margin_footer = defined('PDF_MARGIN_FOOTER') ? PDF_MARGIN_FOOTER : 10;
        $margin_bottom = defined('PDF_MARGIN_BOTTOM') ? PDF_MARGIN_BOTTOM : 25;
        
        $pdf->SetMargins($margin_left, $margin_top, $margin_right);
        $pdf->SetHeaderMargin($margin_header);
        $pdf->SetFooterMargin($margin_footer);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, $margin_bottom);

        // Set image scale factor
        $image_scale = defined('PDF_IMAGE_SCALE_RATIO') ? PDF_IMAGE_SCALE_RATIO : 1.25;
        $pdf->setImageScale($image_scale);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Generate PDF content
        $html = $this->generate_pdf_content($data, $meal_plan);
        
        // Print content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Generate filename
        $filename = 'diet-plan-' . date('Y-m-d') . '.pdf';

        // Output PDF and exit
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Generate PDF HTML content
     */
    private function generate_pdf_content($data, $meal_plan) {
        $bmi = round($data['weight'] / pow($data['height'] / 100, 2), 1);
        
        $html = '<style>
            .header { background-color: #4f46e5; color: white; padding: 10px; text-align: center; font-size: 20px; font-weight: bold; }
            .section { margin: 20px 0; }
            .section-title { background-color: #f3f4f6; padding: 8px; font-size: 16px; font-weight: bold; color: #374151; }
            .info-grid { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .info-grid td { border: 1px solid #d1d5db; padding: 8px; }
            .info-grid .label { background-color: #f9fafb; font-weight: bold; width: 40%; }
            .food-category { margin: 15px 0; }
            .prioritize { color: #059669; }
            .neutral { color: #0369a1; }
            .minimize { color: #dc2626; }
            .meal-item { background-color: #f8fafc; padding: 10px; margin: 5px 0; border-left: 4px solid #4f46e5; }
        </style>';

        $html .= '<div class="header">' . __('Your Personalized Diet Plan', 'diet-calculator') . '</div>';

        // Personal Information Section
        $html .= '<div class="section">';
        $html .= '<div class="section-title">' . __('Personal Information', 'diet-calculator') . '</div>';
        $html .= '<table class="info-grid">';
        $html .= '<tr><td class="label">' . __('Height', 'diet-calculator') . '</td><td>' . $data['height'] . 'cm</td></tr>';
        $html .= '<tr><td class="label">' . __('Weight', 'diet-calculator') . '</td><td>' . $data['weight'] . 'kg</td></tr>';
        $html .= '<tr><td class="label">' . __('Age', 'diet-calculator') . '</td><td>' . $data['age'] . ' ' . __('years', 'diet-calculator') . '</td></tr>';
        $html .= '<tr><td class="label">' . __('Sex', 'diet-calculator') . '</td><td>' . ucfirst($data['sex']) . '</td></tr>';
        $html .= '<tr><td class="label">' . __('BMI', 'diet-calculator') . '</td><td>' . $bmi . '</td></tr>';
        $html .= '<tr><td class="label">' . __('Goal', 'diet-calculator') . '</td><td>' . ucwords(str_replace('_', ' ', $data['goal'])) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Nutrition Targets Section
        $html .= '<div class="section">';
        $html .= '<div class="section-title">' . __('Daily Nutrition Targets', 'diet-calculator') . '</div>';
        $html .= '<table class="info-grid">';
        $html .= '<tr><td class="label">' . __('Calories', 'diet-calculator') . '</td><td>' . round($data['dailyCalories']) . ' kcal</td></tr>';
        $html .= '<tr><td class="label">' . __('Protein', 'diet-calculator') . '</td><td>' . round($data['proteinGrams']) . 'g</td></tr>';
        $html .= '<tr><td class="label">' . __('Carbohydrates', 'diet-calculator') . '</td><td>' . round($data['carbGrams']) . 'g</td></tr>';
        $html .= '<tr><td class="label">' . __('Fat', 'diet-calculator') . '</td><td>' . round($data['fatGrams']) . 'g</td></tr>';
        $html .= '<tr><td class="label">' . __('Water', 'diet-calculator') . '</td><td>' . round($data['waterIntake']) . 'ml</td></tr>';
        $html .= '<tr><td class="label">' . __('BMR', 'diet-calculator') . '</td><td>' . round($data['bmr']) . ' kcal</td></tr>';
        $html .= '<tr><td class="label">' . __('TDEE', 'diet-calculator') . '</td><td>' . round($data['tdee']) . ' kcal</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Food Categorization Section
        if (isset($meal_plan['foodCategorization'])) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">' . __('Food Categorization Guide', 'diet-calculator') . '</div>';
            
            foreach (['prioritize', 'neutral', 'minimize'] as $category) {
                if (isset($meal_plan['foodCategorization'][$category])) {
                    $cat_data = $meal_plan['foodCategorization'][$category];
                    $class = $category;
                    
                    $html .= '<div class="food-category">';
                    $html .= '<h3 class="' . $class . '">' . $cat_data['title'] . '</h3>';
                    $html .= '<p>' . $cat_data['description'] . '</p>';
                    
                    if (!empty($cat_data['foods'])) {
                        $html .= '<ul>';
                        foreach ($cat_data['foods'] as $food) {
                            $html .= '<li>' . $this->clean_text($food) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    
                    if (isset($cat_data['reasoning'])) {
                        $html .= '<p><em>' . $cat_data['reasoning'] . '</em></p>';
                    }
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
        }

        // Sample Meals Section
        if (isset($meal_plan['weeklyPlan'][0]['meals'])) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">' . __('Sample Daily Meals', 'diet-calculator') . '</div>';
            
            foreach ($meal_plan['weeklyPlan'][0]['meals'] as $meal) {
                $html .= '<div class="meal-item">';
                $html .= '<h4>' . $meal['name'] . '</h4>';
                $html .= '<p><strong>' . $this->clean_text($meal['food']) . '</strong></p>';
                $html .= '<p>' . __('Calories:', 'diet-calculator') . ' ' . $meal['calories'] . ' | ';
                $html .= __('Protein:', 'diet-calculator') . ' ' . $meal['protein'] . 'g | ';
                $html .= __('Carbs:', 'diet-calculator') . ' ' . $meal['carbs'] . 'g | ';
                $html .= __('Fat:', 'diet-calculator') . ' ' . $meal['fat'] . 'g</p>';
                
                if (!empty($meal['ingredients'])) {
                    $html .= '<p><strong>' . __('Ingredients:', 'diet-calculator') . '</strong> ' . implode(', ', array_map(array($this, 'clean_text'), $meal['ingredients'])) . '</p>';
                }
                
                if (!empty($meal['portions'])) {
                    $html .= '<p><strong>' . __('Portions:', 'diet-calculator') . '</strong> ' . $this->clean_text($meal['portions']) . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Shopping List Section
        if (isset($meal_plan['shoppingList']) && is_array($meal_plan['shoppingList'])) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">' . __('Smart Shopping List', 'diet-calculator') . '</div>';
            
            foreach ($meal_plan['shoppingList'] as $category => $items) {
                if (is_array($items) && isset($items['title']) && isset($items['items'])) {
                    $html .= '<h4>' . $items['title'] . '</h4>';
                    if (!empty($items['items'])) {
                        $html .= '<ul>';
                        foreach ($items['items'] as $item) {
                            $html .= '<li>' . $this->clean_text($item) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                } elseif (is_array($items)) {
                    $html .= '<h4>' . ucwords(str_replace('_', ' ', $category)) . '</h4>';
                    $html .= '<ul>';
                    foreach ($items as $item) {
                        $html .= '<li>' . $this->clean_text($item) . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
            $html .= '</div>';
        }

        // Disclaimers
        $html .= '<div class="section">';
        $html .= '<div class="section-title">' . __('Important Disclaimers', 'diet-calculator') . '</div>';
        $html .= '<p>' . __('This report is generated for informational purposes only. Please consult with a healthcare professional before making significant dietary changes.', 'diet-calculator') . '</p>';
        $html .= '<p>' . __('Individual results may vary. This plan is based on general nutritional guidelines and your provided information.', 'diet-calculator') . '</p>';
        $html .= '</div>';

        // Footer
        $html .= '<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #6b7280;">';
        $html .= __('Generated by AI Diet Calculator Plugin', 'diet-calculator') . ' | ' . date('Y-m-d H:i:s');
        $html .= '</div>';

        return $html;
    }

    /**
     * Load TCPDF library
     */
    private function load_tcpdf() {
        // First check if TCPDF is already loaded
        if (class_exists('TCPDF')) {
            return true;
        }

        // Try to load TCPDF from various locations
        $tcpdf_paths = array(
            ABSPATH . 'wp-includes/tcpdf/tcpdf.php',
            ABSPATH . 'wp-content/plugins/tcpdf/tcpdf.php',
            dirname(__FILE__) . '/tcpdf/tcpdf.php',
            DIET_CALCULATOR_PLUGIN_PATH . 'includes/tcpdf/tcpdf.php'
        );

        foreach ($tcpdf_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once($path);
                    if (class_exists('TCPDF')) {
                        return true;
                    }
                } catch (Exception $e) {
                    // Continue to next path
                    continue;
                }
            }
        }

        // Try to use WordPress's built-in mPDF if available (some themes/plugins include it)
        if (class_exists('mPDF') || class_exists('Mpdf\\Mpdf')) {
            return $this->use_mpdf_alternative();
        }

        return false;
    }

    /**
     * Use mPDF as alternative to TCPDF
     */
    private function use_mpdf_alternative() {
        // This would be implemented if mPDF is available
        // For now, return false to use fallback
        return false;
    }

    /**
     * Generate simple HTML PDF as fallback
     */
    private function generate_simple_pdf($data, $meal_plan) {
        // Parse meal plan if it's JSON string
        if (is_string($meal_plan)) {
            $meal_plan = json_decode($meal_plan, true);
        }

        // Log which fallback method we're using
        error_log('Diet Calculator: Using fallback PDF generation methods');

        // Generate HTML content
        $html = $this->generate_pdf_content($data, $meal_plan);
        
        // Use wkhtmltopdf if available, otherwise use simple PDF
        if ($this->use_wkhtmltopdf($html)) {
            return;
        }

        // Fallback to simple PDF
        $this->generate_fpdf($data, $meal_plan);
    }

    /**
     * Try to use wkhtmltopdf for PDF generation
     */
    private function use_wkhtmltopdf($html) {
        // Check if wkhtmltopdf is available
        $wkhtmltopdf = exec('which wkhtmltopdf');
        if (empty($wkhtmltopdf)) {
            return false;
        }

        // Create temporary HTML file
        $temp_html = tempnam(sys_get_temp_dir(), 'diet_calc_') . '.html';
        $temp_pdf = tempnam(sys_get_temp_dir(), 'diet_calc_') . '.pdf';

        // Add CSS styling for print
        $styled_html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { background-color: #4f46e5; color: white; padding: 10px; text-align: center; }
                .section { margin: 15px 0; }
                .section-title { background-color: #f3f4f6; padding: 8px; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                td { border: 1px solid #ddd; padding: 6px; }
                .label { background-color: #f9fafb; font-weight: bold; }
                ul { margin: 10px 0; padding-left: 20px; }
            </style>
        </head>
        <body>' . $html . '</body>
        </html>';

        file_put_contents($temp_html, $styled_html);

        // Generate PDF
        $command = escapeshellcmd($wkhtmltopdf) . ' --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in ' . escapeshellarg($temp_html) . ' ' . escapeshellarg($temp_pdf);
        exec($command, $output, $return_var);

        if ($return_var === 0 && file_exists($temp_pdf)) {
            // Set proper headers for PDF download
            if (!headers_sent()) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="diet-plan-' . date('Y-m-d') . '.pdf"');
                header('Content-Length: ' . filesize($temp_pdf));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
            }
            
            // Output PDF
            readfile($temp_pdf);
            
            // Clean up
            unlink($temp_html);
            unlink($temp_pdf);
            
            exit; // Important: exit after successful PDF generation
        }

        // Clean up on failure
        if (file_exists($temp_html)) unlink($temp_html);
        if (file_exists($temp_pdf)) unlink($temp_pdf);
        
        return false;
    }

    /**
     * Generate PDF using simple PDF library as final fallback
     */
    private function generate_fpdf($data, $meal_plan) {
        // Load our simple PDF class
        require_once(dirname(__FILE__) . '/lib/simple-pdf.php');
        
        $pdf = new SimplePDF();
        $pdf->SetTitle(__('Personalized Diet Plan', 'diet-calculator'));
        $pdf->SetAuthor(get_bloginfo('name'));
        
        // Generate HTML content
        $html = $this->generate_pdf_content($data, $meal_plan);
        
        // Add to PDF
        $pdf->writeHTML($html);
        
        // Output PDF
        $filename = 'diet-plan-' . date('Y-m-d') . '.txt';
        $pdf->Output($filename, 'D');
        
        // Exit is handled in SimplePDF class
        exit;
    }
    

    /**
     * Clean text for PDF output
     */
    private function clean_text($text) {
        // Remove problematic characters and emojis
        $cleaned = preg_replace('/[^\x20-\x7E]/', '', $text);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }

    /**
     * Generate and return PDF as base64 for AJAX
     */
    public function generate_pdf_base64($data, $meal_plan) {
        ob_start();
        $this->generate_pdf($data, $meal_plan);
        $pdf_content = ob_get_clean();
        
        return base64_encode($pdf_content);
    }
}