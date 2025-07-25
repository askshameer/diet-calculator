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
        // Include TCPDF library
        if (!class_exists('TCPDF')) {
            require_once(ABSPATH . 'wp-includes/class-tcpdf.php');
        }

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle(__('Personalized Diet Plan', 'diet-calculator'));
        $pdf->SetSubject(__('AI-Generated Nutrition Plan', 'diet-calculator'));

        // Set default header data
        $pdf->SetHeaderData('', 0, __('AI Diet Calculator', 'diet-calculator'), get_bloginfo('name'));

        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

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

        // Output PDF
        return $pdf->Output($filename, 'D');
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