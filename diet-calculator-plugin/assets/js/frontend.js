/**
 * Frontend JavaScript for Diet Calculator Plugin
 */
jQuery(document).ready(function($) {
    let currentStep = 1;
    const totalSteps = 4;

    // Initialize the calculator
    initDietCalculator();

    function initDietCalculator() {
        showStep(1);
        bindEvents();
        updateProgress();
    }

    function bindEvents() {
        // Navigation buttons
        $('#next-step').on('click', handleNextStep);
        $('#prev-step').on('click', handlePrevStep);
        $('#submit-form').on('click', handleFormSubmit);

        // Form validation
        $('#diet-calculator-form input, #diet-calculator-form select').on('change', validateCurrentStep);
        
        // Goal weight visibility
        $('#goal').on('change', toggleGoalWeight);
        
        // Real-time validation
        $('#diet-calculator-form input[type="number"]').on('input', function() {
            const min = $(this).attr('min');
            const max = $(this).attr('max');
            const value = $(this).val();
            
            if (min && value < min) {
                $(this).addClass('error');
            } else if (max && value > max) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
    }

    function showStep(step) {
        $('.form-step').removeClass('active').hide();
        $(`.form-step[data-step="${step}"]`).addClass('active').fadeIn(300);
        
        $('.progress-steps .step').removeClass('active completed');
        
        // Mark previous steps as completed
        for (let i = 1; i < step; i++) {
            $(`.progress-steps .step[data-step="${i}"]`).addClass('completed');
        }
        
        // Mark current step as active
        $(`.progress-steps .step[data-step="${step}"]`).addClass('active');
        
        updateNavigationButtons(step);
        updateProgress(step);
        currentStep = step;
    }

    function updateNavigationButtons(step) {
        $('#prev-step').toggle(step > 1);
        $('#next-step').toggle(step < totalSteps);
        $('#submit-form').toggle(step === totalSteps);
    }

    function updateProgress(step = currentStep) {
        const progress = (step / totalSteps) * 100;
        $('.progress-fill').css('width', progress + '%');
    }

    function handleNextStep(e) {
        e.preventDefault();
        
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        } else {
            showValidationErrors();
        }
    }

    function handlePrevStep(e) {
        e.preventDefault();
        
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }

    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        const requiredFields = currentStepElement.find('[required]');
        let isValid = true;

        requiredFields.each(function() {
            const field = $(this);
            const value = field.val();
            
            if (!value || value === '') {
                field.addClass('error');
                isValid = false;
            } else {
                field.removeClass('error');
            }
        });

        return isValid;
    }

    function showValidationErrors() {
        const errorFields = $(`.form-step[data-step="${currentStep}"] .error`);
        if (errorFields.length > 0) {
            errorFields.first().focus();
            showNotification('Please fill in all required fields', 'error');
        }
    }

    function toggleGoalWeight() {
        const goal = $('#goal').val();
        const goalWeightGroup = $('#goal-weight-group');
        
        if (goal === 'lose_weight' || goal === 'build_muscle') {
            goalWeightGroup.slideDown(300);
            $('#goalWeight').attr('required', true);
        } else {
            goalWeightGroup.slideUp(300);
            $('#goalWeight').removeAttr('required');
        }
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        
        if (!validateCurrentStep()) {
            showValidationErrors();
            return;
        }

        // Show loading state
        showLoading();
        
        // Collect form data
        const formData = collectFormData();
        
        // Submit via AJAX
        $.ajax({
            url: dietCalculatorAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'diet_calculator_generate_plan',
                nonce: dietCalculatorAjax.nonce,
                formData: formData
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showResults(response.data);
                } else {
                    showNotification(response.data.message || 'An error occurred. Please try again.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('Network error. Please check your connection and try again.', 'error');
            }
        });
    }

    function collectFormData() {
        const form = $('#diet-calculator-form');
        const formData = {};
        
        // Basic form fields
        form.find('input, select').each(function() {
            const field = $(this);
            const name = field.attr('name');
            const value = field.val();
            
            if (name && value) {
                formData[name] = value;
            }
        });
        
        // Checkbox arrays
        formData.dietaryPreferences = [];
        form.find('input[name="dietaryPreferences[]"]:checked').each(function() {
            formData.dietaryPreferences.push($(this).val());
        });
        
        formData.foodAllergies = [];
        form.find('input[name="foodAllergies[]"]:checked').each(function() {
            formData.foodAllergies.push($(this).val());
        });
        
        formData.foodIntolerances = [];
        form.find('input[name="foodIntolerances[]"]:checked').each(function() {
            formData.foodIntolerances.push($(this).val());
        });
        
        return formData;
    }

    function showLoading() {
        $('#diet-calculator-form').hide();
        $('#diet-calculator-loading').fadeIn(300);
    }

    function hideLoading() {
        $('#diet-calculator-loading').hide();
    }

    function showResults(data) {
        const resultsHtml = generateResultsHTML(data);
        $('#diet-calculator-results').html(resultsHtml).fadeIn(300);
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#diet-calculator-results').offset().top - 50
        }, 500);
    }

    function generateResultsHTML(data) {
        return `
            <div class="results-container">
                <div class="results-header">
                    <h3>Your Personalized Diet Plan</h3>
                    <div class="plan-id">Plan ID: ${data.plan_id}</div>
                </div>
                
                <div class="nutrition-summary">
                    <h4>Daily Nutrition Targets</h4>
                    <div class="nutrition-grid">
                        <div class="nutrition-item">
                            <span class="value">${Math.round(data.daily_calories)}</span>
                            <span class="label">Calories</span>
                        </div>
                        <div class="nutrition-item">
                            <span class="value">${Math.round(data.protein_grams)}g</span>
                            <span class="label">Protein</span>
                        </div>
                        <div class="nutrition-item">
                            <span class="value">${Math.round(data.carb_grams)}g</span>
                            <span class="label">Carbs</span>
                        </div>
                        <div class="nutrition-item">
                            <span class="value">${Math.round(data.fat_grams)}g</span>
                            <span class="label">Fat</span>
                        </div>
                    </div>
                </div>
                
                <div class="plan-actions">
                    <a href="${dietCalculatorAjax.ajaxurl}?action=diet_calculator_download_pdf&plan_id=${data.plan_id}" 
                       class="btn btn-primary btn-download" target="_blank">
                        Download Complete PDF Report
                    </a>
                    <button class="btn btn-secondary btn-restart" onclick="location.reload()">
                        Create Another Plan
                    </button>
                </div>
                
                <div class="ai-confidence">
                    <small>
                        ${data.ai_generated ? 'Generated using AI analysis' : 'Generated using intelligent fallback'}
                        ${data.ai_confidence ? ` (Confidence: ${data.ai_confidence})` : ''}
                    </small>
                </div>
            </div>
        `;
    }

    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="diet-calculator-notification ${type}">
                <span class="message">${message}</span>
                <button class="close-notification">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        notification.fadeIn(300);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut(300, () => notification.remove());
        }, 5000);
        
        // Manual close
        notification.find('.close-notification').on('click', () => {
            notification.fadeOut(300, () => notification.remove());
        });
    }

    // Initialize tooltips if available
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-tooltip]').tooltip();
    }

    // Form field formatting
    $('#height, #weight, #age').on('input', function() {
        const value = $(this).val();
        if (value && !isNaN(value)) {
            $(this).addClass('has-value');
        } else {
            $(this).removeClass('has-value');
        }
    });

    // Prevent form submission on Enter key (except on submit button)
    $('#diet-calculator-form').on('keypress', function(e) {
        if (e.which === 13 && e.target.type !== 'submit') {
            e.preventDefault();
            $('#next-step').click();
        }
    });
});