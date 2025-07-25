<?php
/**
 * PDF Generator for Diet Calculator Plugin
 * Rewritten to use FPDF for reliable WordPress compatibility
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
        try {
            // Parse meal plan if it's JSON string
            if (is_string($meal_plan)) {
                $meal_plan = json_decode($meal_plan, true);
            }

            // Load FPDF library
            require_once(dirname(__FILE__) . '/lib/fpdf.php');

            // Create PDF instance
            $pdf = new FPDF();
            $pdf->SetTitle(__('Personalized Diet Plan', 'diet-calculator'));
            $pdf->SetAuthor(get_bloginfo('name'));
            $pdf->SetSubject(__('AI-Generated Nutrition Plan', 'diet-calculator'));

            // Add first page
            $pdf->AddPage();

            // Generate PDF content
            $this->generate_pdf_content($pdf, $data, $meal_plan);

            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Generate filename
            $filename = 'diet-plan-' . date('Y-m-d') . '.pdf';

            // Output PDF for download
            $pdf->Output($filename, 'D');
            exit;

        } catch (Exception $e) {
            error_log('Diet Calculator PDF Error: ' . $e->getMessage());
            
            // Fallback to text file
            $this->generate_text_fallback($data, $meal_plan);
            exit;
        }
    }

    /**
     * Generate PDF content using FPDF
     */
    private function generate_pdf_content($pdf, $data, $meal_plan) {
        // Header
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(79, 70, 229); // Purple color
        $pdf->Cell(0, 15, __('Your Personalized Diet Plan', 'diet-calculator'), 0, 1, 'C');
        $pdf->Ln(10);

        // Personal Information Section
        $this->add_section_header($pdf, __('Personal Information', 'diet-calculator'));
        
        $pdf->SetFont('Arial', '', 11);
        $bmi = round($data['weight'] / pow($data['height'] / 100, 2), 1);
        
        $this->add_info_row($pdf, __('Height', 'diet-calculator'), $data['height'] . 'cm');
        $this->add_info_row($pdf, __('Weight', 'diet-calculator'), $data['weight'] . 'kg');
        $this->add_info_row($pdf, __('Age', 'diet-calculator'), $data['age'] . ' ' . __('years', 'diet-calculator'));
        $this->add_info_row($pdf, __('Sex', 'diet-calculator'), ucfirst($data['sex']));
        $this->add_info_row($pdf, __('BMI', 'diet-calculator'), $bmi);
        $this->add_info_row($pdf, __('Goal', 'diet-calculator'), ucwords(str_replace('_', ' ', $data['goal'])));
        
        $pdf->Ln(10);

        // Nutrition Targets Section
        $this->add_section_header($pdf, __('Daily Nutrition Targets', 'diet-calculator'));
        
        $this->add_info_row($pdf, __('Calories', 'diet-calculator'), round($data['daily_calories']) . ' kcal');
        $this->add_info_row($pdf, __('Protein', 'diet-calculator'), round($data['protein_grams']) . 'g');
        $this->add_info_row($pdf, __('Carbohydrates', 'diet-calculator'), round($data['carb_grams']) . 'g');
        $this->add_info_row($pdf, __('Fat', 'diet-calculator'), round($data['fat_grams']) . 'g');
        $this->add_info_row($pdf, __('Water', 'diet-calculator'), round($data['water_intake']) . 'ml');
        $this->add_info_row($pdf, __('BMR', 'diet-calculator'), round($data['bmr']) . ' kcal');
        $this->add_info_row($pdf, __('TDEE', 'diet-calculator'), round($data['tdee']) . ' kcal');
        
        $pdf->Ln(10);

        // Food Categorization Section
        if (isset($meal_plan['foodCategorization'])) {
            $this->add_section_header($pdf, __('Food Categorization Guide', 'diet-calculator'));
            
            foreach (['prioritize', 'neutral', 'minimize'] as $category) {
                if (isset($meal_plan['foodCategorization'][$category])) {
                    $cat_data = $meal_plan['foodCategorization'][$category];
                    
                    // Category title with color coding
                    $pdf->SetFont('Arial', 'B', 12);
                    switch($category) {
                        case 'prioritize':
                            $pdf->SetTextColor(5, 150, 105); // Green
                            break;
                        case 'neutral':
                            $pdf->SetTextColor(3, 105, 161); // Blue
                            break;
                        case 'minimize':
                            $pdf->SetTextColor(220, 38, 38); // Red
                            break;
                    }
                    
                    $pdf->Cell(0, 8, $cat_data['title'], 0, 1);
                    
                    // Reset color
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetFont('Arial', '', 10);
                    
                    // Description
                    $pdf->MultiCell(0, 5, $cat_data['description'], 0, 'L');
                    $pdf->Ln(2);
                    
                    // Foods list
                    if (!empty($cat_data['foods'])) {
                        foreach ($cat_data['foods'] as $food) {
                            $pdf->Cell(10, 5, '•', 0, 0);
                            $pdf->Cell(0, 5, $this->clean_text($food), 0, 1);
                        }
                    }
                    
                    $pdf->Ln(5);
                }
            }
        }

        // Sample Meals Section
        if (isset($meal_plan['weeklyPlan'][0]['meals'])) {
            // Add new page if needed
            if ($pdf->GetY() > 220) {
                $pdf->AddPage();
            }
            
            $this->add_section_header($pdf, __('Sample Daily Meals', 'diet-calculator'));
            
            foreach ($meal_plan['weeklyPlan'][0]['meals'] as $meal) {
                // Check if we need a new page
                if ($pdf->GetY() > 250) {
                    $pdf->AddPage();
                }
                
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 8, $meal['name'], 0, 1);
                
                $pdf->SetFont('Arial', '', 10);
                $pdf->MultiCell(0, 5, $this->clean_text($meal['food']), 0, 'L');
                
                // Nutrition info
                $nutrition = sprintf(
                    __('Calories: %s | Protein: %sg | Carbs: %sg | Fat: %sg', 'diet-calculator'),
                    $meal['calories'], $meal['protein'], $meal['carbs'], $meal['fat']
                );
                $pdf->Cell(0, 5, $nutrition, 0, 1);
                
                // Ingredients
                if (!empty($meal['ingredients'])) {
                    $ingredients_text = __('Ingredients: ', 'diet-calculator') . implode(', ', array_map(array($this, 'clean_text'), $meal['ingredients']));
                    $pdf->MultiCell(0, 5, $ingredients_text, 0, 'L');
                }
                
                // Portions
                if (!empty($meal['portions'])) {
                    $pdf->Cell(0, 5, __('Portions: ', 'diet-calculator') . $this->clean_text($meal['portions']), 0, 1);
                }
                
                $pdf->Ln(5);
            }
        }

        // Shopping List Section
        if (isset($meal_plan['shoppingList']) && is_array($meal_plan['shoppingList'])) {
            // Add new page if needed
            if ($pdf->GetY() > 200) {
                $pdf->AddPage();
            }
            
            $this->add_section_header($pdf, __('Smart Shopping List', 'diet-calculator'));
            
            foreach ($meal_plan['shoppingList'] as $category => $items) {
                if (is_array($items) && isset($items['title']) && isset($items['items'])) {
                    $pdf->SetFont('Arial', 'B', 11);
                    $pdf->Cell(0, 8, $items['title'], 0, 1);
                    
                    $pdf->SetFont('Arial', '', 10);
                    if (!empty($items['items'])) {
                        foreach ($items['items'] as $item) {
                            $pdf->Cell(10, 5, '•', 0, 0);
                            $pdf->Cell(0, 5, $this->clean_text($item), 0, 1);
                        }
                    }
                } elseif (is_array($items)) {
                    $pdf->SetFont('Arial', 'B', 11);
                    $pdf->Cell(0, 8, ucwords(str_replace('_', ' ', $category)), 0, 1);
                    
                    $pdf->SetFont('Arial', '', 10);
                    foreach ($items as $item) {
                        $pdf->Cell(10, 5, '•', 0, 0);
                        $pdf->Cell(0, 5, $this->clean_text($item), 0, 1);
                    }
                }
                $pdf->Ln(3);
            }
        }

        // Disclaimers
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        
        $this->add_section_header($pdf, __('Important Disclaimers', 'diet-calculator'));
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, __('This report is generated for informational purposes only. Please consult with a healthcare professional before making significant dietary changes.', 'diet-calculator'), 0, 'L');
        $pdf->Ln(3);
        $pdf->MultiCell(0, 5, __('Individual results may vary. This plan is based on general nutritional guidelines and your provided information.', 'diet-calculator'), 0, 'L');
        
        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->Cell(0, 5, __('Generated by AI Diet Calculator Plugin', 'diet-calculator') . ' | ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    }

    /**
     * Add section header
     */
    private function add_section_header($pdf, $title) {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(79, 70, 229);
        $pdf->SetFillColor(243, 244, 246);
        $pdf->Cell(0, 10, $title, 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
    }

    /**
     * Add information row
     */
    private function add_info_row($pdf, $label, $value) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 6, $label . ':', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, $value, 0, 1);
    }

    /**
     * Generate text fallback when PDF fails
     */
    private function generate_text_fallback($data, $meal_plan) {
        $content = "============================================\n";
        $content .= "    PERSONALIZED DIET PLAN\n";
        $content .= "    Generated by: " . get_bloginfo('name') . "\n";
        $content .= "    Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "============================================\n\n";
        
        $content .= "PERSONAL INFORMATION\n";
        $content .= "Height: " . $data['height'] . "cm\n";
        $content .= "Weight: " . $data['weight'] . "kg\n";
        $content .= "Age: " . $data['age'] . " years\n";
        $content .= "Sex: " . ucfirst($data['sex']) . "\n";
        $content .= "Goal: " . ucwords(str_replace('_', ' ', $data['goal'])) . "\n\n";
        
        $content .= "NUTRITION TARGETS\n";
        $content .= "Daily Calories: " . round($data['daily_calories']) . " kcal\n";
        $content .= "Protein: " . round($data['protein_grams']) . "g\n";
        $content .= "Carbohydrates: " . round($data['carb_grams']) . "g\n";
        $content .= "Fat: " . round($data['fat_grams']) . "g\n";
        $content .= "Water: " . round($data['water_intake']) . "ml\n\n";

        if (isset($meal_plan['foodCategorization'])) {
            $content .= "FOOD RECOMMENDATIONS\n\n";
            
            foreach (['prioritize', 'neutral', 'minimize'] as $category) {
                if (isset($meal_plan['foodCategorization'][$category])) {
                    $cat_data = $meal_plan['foodCategorization'][$category];
                    $content .= strtoupper($cat_data['title']) . "\n";
                    $content .= $cat_data['description'] . "\n";
                    
                    if (!empty($cat_data['foods'])) {
                        foreach ($cat_data['foods'] as $food) {
                            $content .= "- " . $this->clean_text($food) . "\n";
                        }
                    }
                    $content .= "\n";
                }
            }
        }

        $content .= "\nDISCLAIMER\n";
        $content .= "This report is for informational purposes only. Consult with a healthcare professional before making significant dietary changes.\n";

        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Output as text file
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="diet-plan-' . date('Y-m-d') . '.txt"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }
        
        echo $content;
    }

    /**
     * Clean text for output
     */
    private function clean_text($text) {
        // Remove problematic characters and emojis
        $cleaned = preg_replace('/[^\x20-\x7E]/', '', $text);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }
}