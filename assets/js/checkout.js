jQuery(document).ready(function($) {
    'use strict';
    
    // Email validation function
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Form validation
    function validateForm() {
        var errors = [];
        var formData = {};
        
        // Get form data
        $('#cap-checkout-form').serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });
        
        // Validate required fields
        var requiredFields = ['full_name', 'billing_address', 'billing_city'];
        
        requiredFields.forEach(function(field) {
            var fieldElement = $('[name="' + field + '"]');
            if (!formData[field] || formData[field].trim() === '') {
                errors.push(cap_ajax.messages.required_field);
                fieldElement.addClass('error');
            } else {
                fieldElement.removeClass('error');
            }
        });
        
        // Validate full name (should have at least 2 characters)
        if (formData.full_name && formData.full_name.trim().length < 2) {
            errors.push('Le nom complet doit contenir au moins 2 caractères.');
            $('#cap_full_name').addClass('error');
        }
        
        // Validate billing address (should have at least 5 characters)
        if (formData.billing_address && formData.billing_address.trim().length < 5) {
            errors.push('Veuillez entrer une adresse de facturation complète.');
            $('#cap_billing_address').addClass('error');
        }
        
        // Validate billing city (should have at least 2 characters)
        if (formData.billing_city && formData.billing_city.trim().length < 2) {
            errors.push('Veuillez entrer un nom de ville valide.');
            $('#cap_billing_city').addClass('error');
        }
        
        // Validate billing state (optional field)
        if (formData.billing_state && formData.billing_state.trim().length < 2) {
            errors.push('Veuillez entrer un nom de gouvernorat valide.');
            $('#cap_billing_state').addClass('error');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
    
    // Show message
    function showMessage(message, type) {
        var messageClass = 'cap-message cap-' + type;
        var icon = '';
        
        switch (type) {
            case 'success':
                icon = '✓';
                break;
            case 'error':
                icon = '✗';
                break;
            case 'info':
                icon = 'ℹ';
                break;
        }
        
        $('#cap-messages').html('<div class="' + messageClass + '">' + icon + ' ' + message + '</div>').show();
        
        // Auto-hide success and info messages after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $('#cap-messages').fadeOut();
            }, 5000);
        }
    }
    
    // Clear messages
    function clearMessages() {
        $('#cap-messages').empty();
    }
    
    // Form submission
    $('#cap-checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        clearMessages();
        
        // Validate form
        var validation = validateForm();
        if (!validation.isValid) {
            showMessage(validation.errors.join('<br>'), 'error');
            return false;
        }
        
        // Show processing message
        showMessage(cap_ajax.messages.processing, 'info');
        
        // Disable submit button
        $('#cap-submit-order').prop('disabled', true).text(cap_ajax.messages.processing);
        
        // Get form data
        var formData = $(this).serialize();
        
        // Submit form via AJAX
        $.ajax({
            url: cap_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Redirect to WooCommerce checkout
                    if (response.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    }
                } else {
                    var errorMessage = response.data.message || cap_ajax.messages.error;
                    if (response.data.errors) {
                        errorMessage = response.data.errors.join('<br>');
                    }
                    showMessage(errorMessage, 'error');
                }
            },
            error: function() {
                showMessage(cap_ajax.messages.error, 'error');
            },
            complete: function() {
                // Re-enable submit button
                $('#cap-submit-order').prop('disabled', false).text('Commander Maintenant');
            }
        });
    });
    
    // Real-time validation
    $('.cap-form input, .cap-form textarea, .cap-form select').on('blur', function() {
        var field = $(this);
        var fieldName = field.attr('name');
        var fieldValue = field.val();
        
        // Remove existing error class
        field.removeClass('error');
        
        // Validate based on field type
        if (fieldName === 'full_name' && fieldValue && fieldValue.trim().length < 2) {
            field.addClass('error');
            showMessage('Le nom complet doit contenir au moins 2 caractères.', 'error');
        } else if (fieldName === 'billing_address' && fieldValue && fieldValue.trim().length < 5) {
            field.addClass('error');
            showMessage('Veuillez entrer une adresse de facturation complète.', 'error');
        }
    });
    
    // Clear error messages when user starts typing
    $('.cap-form input, .cap-form textarea, .cap-form select').on('input', function() {
        clearMessages();
        $(this).removeClass('error');
    });
    
    // Dynamic city loading based on state selection
    $('#cap_billing_state').on('change', function() {
        var selectedState = $(this).val();
        var citySelect = $('#cap_billing_city');
        
        // Clear current cities
        citySelect.html('<option value="">' + cap_ajax.messages.select_city + '</option>');
        
        if (selectedState) {
            // Show loading
            citySelect.prop('disabled', true);
            
            // Get cities for selected state
            $.ajax({
                url: cap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cap_get_cities',
                    state: selectedState,
                    nonce: cap_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.cities) {
                        response.data.cities.forEach(function(city) {
                            citySelect.append('<option value="' + city + '">' + city + '</option>');
                        });
                    }
                },
                error: function() {
                    showMessage('Erreur lors du chargement des villes. Veuillez réessayer.', 'error');
                },
                complete: function() {
                    citySelect.prop('disabled', false);
                }
            });
        }
    });
}); 