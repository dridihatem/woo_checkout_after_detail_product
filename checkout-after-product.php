<?php
/**
 * Plugin Name: Checkout After Product
 * Plugin URI: https://dridihatem.dawebcompany.tn/checkout-after-product
 * Description: Shows a checkout form with payment methods after product detail pages
 * Version: 1.0.0
 * Author: Hatem Dridi
 * Author URI: https://dridihatem.dawebcompany.tn
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: checkout-after-product
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CAP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CAP_PLUGIN_VERSION', '1.0.0');

// Include admin settings
require_once CAP_PLUGIN_PATH . 'includes/admin-settings.php';

// Main plugin class
class CheckoutAfterProduct {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_cap_process_checkout', array($this, 'process_checkout'));
        add_action('wp_ajax_nopriv_cap_process_checkout', array($this, 'process_checkout'));
        add_action('wp_ajax_cap_validate_form', array($this, 'validate_form'));
        add_action('wp_ajax_nopriv_cap_validate_form', array($this, 'validate_form'));
    }
    
    public function init() {
        // Add shortcode for checkout form
        add_shortcode('checkout_after_product', array($this, 'checkout_form_shortcode'));
        
        // Add action to display checkout after short description
        add_action('woocommerce_single_product_summary', array($this, 'display_checkout_form'), 25);
        
        // Pre-fill WooCommerce checkout fields with data from our form
        add_filter('woocommerce_checkout_get_value', array($this, 'prefill_checkout_fields'), 10, 2);
        
        // Hide WooCommerce add to cart and quantity elements
        add_action('woocommerce_single_product_summary', array($this, 'hide_add_to_cart_elements'), 1);
        
        // AJAX handlers
        add_action('wp_ajax_cap_get_cities', array($this, 'get_cities_by_state'));
        add_action('wp_ajax_nopriv_cap_get_cities', array($this, 'get_cities_by_state'));
        
        // Handle add to cart form submission
        add_action('wp_loaded', array($this, 'handle_add_to_cart'));
        

        
        // Load text domain
        load_plugin_textdomain('checkout-after-product', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_style('cap-checkout', CAP_PLUGIN_URL . 'assets/css/checkout.css', array(), CAP_PLUGIN_VERSION);
            
            // Ensure WooCommerce scripts are loaded for add to cart functionality
            if (function_exists('WC')) {
                wp_enqueue_script('wc-add-to-cart');
                wp_enqueue_script('woocommerce');
            }
            
            // Add inline JavaScript
            wp_add_inline_script('jquery', $this->get_inline_javascript());
        }
    }
    
    private function get_inline_javascript() {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('cap_checkout_nonce');
        $messages = array(
            'required_field' => __('Ce champ est requis.', 'checkout-after-product'),
            'invalid_email' => __('Veuillez entrer une adresse email valide.', 'checkout-after-product'),
            'processing' => __('Traitement en cours...', 'checkout-after-product'),
            'success' => __('Commande passée avec succès !', 'checkout-after-product'),
            'error' => __('Erreur. Veuillez réessayer.', 'checkout-after-product'),
            'select_city' => __('Sélectionnez une ville', 'checkout-after-product'),
            'loading_cities' => __('Chargement des villes...', 'checkout-after-product'),
            'no_cities_found' => __('Aucune ville trouvée', 'checkout-after-product'),
            'loading_error' => __('Erreur de chargement', 'checkout-after-product'),
            'added_to_cart' => __('Produit ajouté au panier !', 'checkout-after-product'),
            'quantity_required' => __('Veuillez sélectionner une quantité.', 'checkout-after-product')
        );
        
        return "
        jQuery(document).ready(function($) {
            'use strict';
            
            // Mode switching functionality
            function switchMode(mode) {
                if (mode === 'direct') {
                    $('body').removeClass('cap-add-to-cart-mode').addClass('cap-direct-checkout');
                    $('.cap-checkout-form').show();
                    $('.cap-add-to-cart-section').hide();
                } else if (mode === 'cart') {
                    $('body').removeClass('cap-direct-checkout').addClass('cap-add-to-cart-mode');
                    $('.cap-checkout-form').hide();
                    $('.cap-add-to-cart-section').show();
                }
            }
            
            // Initialize mode
            var initialMode = $('input[name=\"cap_purchase_mode\"]:checked').val();
            switchMode(initialMode);
            
            // Handle mode change
            $('input[name=\"cap_purchase_mode\"]').on('change', function() {
                var mode = $(this).val();
                switchMode(mode);
                
                // Clear any existing messages
                clearMessages();
            });
            
            // Add to cart functionality
            function addToCart(productId, quantity) {
                // Create a temporary form and submit it
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                var productInput = document.createElement('input');
                productInput.type = 'hidden';
                productInput.name = 'add-to-cart';
                productInput.value = productId;
                form.appendChild(productInput);
                
                var quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'quantity';
                quantityInput.value = quantity;
                form.appendChild(quantityInput);
                
                var redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_to_cart';
                redirectInput.value = '1';
                form.appendChild(redirectInput);
                
                document.body.appendChild(form);
                form.submit();
            }
            
            // Handle add to cart button click
            $(document).on('click', '.cap-add-to-cart-btn', function(e) {
                e.preventDefault();
                
                var productId = $(this).data('product-id');
                var quantity = $('.cap-quantity-input').val();
                
                if (!quantity || quantity < 1) {
                    showMessage('" . $messages['quantity_required'] . "', 'error');
                    return;
                }
                
                addToCart(productId, quantity);
            });
            
            // Quantity input handling
            $(document).on('click', '.cap-quantity-btn', function(e) {
                e.preventDefault();
                
                var input = $('.cap-quantity-input');
                var currentQty = parseInt(input.val()) || 1;
                var change = $(this).data('change');
                var newQty = currentQty + change;
                
                if (newQty >= 1) {
                    input.val(newQty);
                }
            });
            
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
                var requiredFields = ['full_name', 'billing_phone', 'billing_address', 'billing_city'];
                
                requiredFields.forEach(function(field) {
                    var fieldElement = $('[name=\"' + field + '\"]');
                    if (!formData[field] || formData[field].trim() === '') {
                        errors.push('" . $messages['required_field'] . "');
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
                
                // Validate phone number (should have at least 8 digits)
                if (formData.billing_phone && formData.billing_phone.replace(/[^0-9]/g, '').length < 8) {
                    errors.push('Veuillez entrer un numéro de téléphone valide (au moins 8 chiffres).');
                    $('#cap_billing_phone').addClass('error');
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
                
                $('#cap-messages').html('<div class=\"' + messageClass + '\">' + icon + ' ' + message + '</div>').show();
                
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
                showMessage('" . $messages['processing'] . "', 'info');
                
                // Disable submit button
                $('#cap-submit-order').prop('disabled', true).text('" . $messages['processing'] . "');
                
                // Get form data
                var formData = $(this).serialize();
                
                // Submit form via AJAX
                $.ajax({
                    url: '" . $ajax_url . "',
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
                            var errorMessage = response.data.message || '" . $messages['error'] . "';
                            if (response.data.errors) {
                                errorMessage = response.data.errors.join('<br>');
                            }
                            showMessage(errorMessage, 'error');
                        }
                    },
                    error: function() {
                        showMessage('" . $messages['error'] . "', 'error');
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
                } else if (fieldName === 'billing_phone' && fieldValue && fieldValue.replace(/[^0-9]/g, '').length < 8) {
                    field.addClass('error');
                    showMessage('Veuillez entrer un numéro de téléphone valide (au moins 8 chiffres).', 'error');
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
                var cityField = citySelect.closest('.cap-form-field');
                
                console.log('CAP Debug: State changed to: ' + selectedState);
                
                // Clear current cities
                citySelect.empty().append('<option value=\"\">" . $messages['select_city'] . "</option>');
                
                if (selectedState) {
                    // Show loading state
                    citySelect.prop('disabled', true);
                    cityField.addClass('loading');
                    
                    // Add loading text to dropdown
                    citySelect.empty().append('<option value=\"\">" . $messages['loading_cities'] . "</option>');
                    
                    console.log('CAP Debug: Making AJAX request for cities...');
                    
                    // Get cities for selected state
                    $.ajax({
                        url: '" . $ajax_url . "',
                        type: 'POST',
                        data: {
                            action: 'cap_get_cities',
                            state: selectedState,
                            nonce: '" . $nonce . "'
                        },
                        success: function(response) {
                            console.log('CAP Debug: AJAX response received:', response);
                            if (response.success && response.data.cities) {
                                console.log('CAP Debug: Adding ' + response.data.cities.length + ' cities to dropdown');
                                // Clear loading text and add cities
                                citySelect.empty().append('<option value=\"\">" . $messages['select_city'] . "</option>');
                                response.data.cities.forEach(function(city) {
                                    citySelect.append('<option value=\"' + city + '\">' + city + '</option>');
                                });
                            } else {
                                console.log('CAP Debug: No cities found or invalid response');
                                citySelect.empty().append('<option value=\"\">" . $messages['no_cities_found'] . "</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('CAP Debug: AJAX error:', status, error);
                            console.log('CAP Debug: Response text:', xhr.responseText);
                            citySelect.empty().append('<option value=\"\">" . $messages['loading_error'] . "</option>');
                            showMessage('Erreur lors du chargement des villes. Veuillez réessayer.', 'error');
                        },
                        complete: function() {
                            // Remove loading state
                            citySelect.prop('disabled', false);
                            cityField.removeClass('loading');
                        }
                    });
                }
            });
            
          
        });
        ";
    }
    
    public function hide_add_to_cart_elements() {
        // Only hide WooCommerce elements if direct checkout is enabled
        // We'll control this via CSS classes and JavaScript
        echo '<style>
            .cap-direct-checkout .woocommerce .single-product .cart,
            .cap-direct-checkout .woocommerce .single-product .quantity,
            .cap-direct-checkout .woocommerce .single-product .single_add_to_cart_button,
            .cap-direct-checkout .woocommerce .single-product .variations_form,
            .cap-direct-checkout .woocommerce .single-product .woocommerce-variation-add-to-cart,
            .cap-direct-checkout .woocommerce .single-product .product_meta {
                display: none !important;
            }
            
            .cap-add-to-cart-mode .cap-checkout-section {
                display: none !important;
            }
        </style>';
    }
    
    public function get_cities_by_state() {
        check_ajax_referer('cap_checkout_nonce', 'nonce');
        
        $state = sanitize_text_field($_POST['state']);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CAP Debug: State requested: ' . $state);
        }
        
        $cities = $this->get_tunisian_cities($state);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CAP Debug: Cities found: ' . count($cities));
        }
        
        wp_send_json_success(array('cities' => $cities));
    }
    
    public function handle_add_to_cart() {
        // Check if this is an add to cart request from our form
        if (isset($_POST['add-to-cart']) && isset($_POST['quantity'])) {
            $product_id = intval($_POST['add-to-cart']);
            $quantity = intval($_POST['quantity']);
            
            // Validate quantity
            if ($quantity < 1) {
                $quantity = 1;
            }
            
            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
            
            if ($cart_item_key) {
                // Set a success message
                wc_add_notice(__('Produit ajouté au panier !', 'checkout-after-product'), 'success');
                
                // Redirect to cart if requested
                if (isset($_POST['redirect_to_cart']) && $_POST['redirect_to_cart']) {
                    wp_safe_redirect(wc_get_cart_url());
                    exit;
                }
            } else {
                wc_add_notice(__('Erreur lors de l\'ajout au panier.', 'checkout-after-product'), 'error');
            }
        }
    }
    
 
    
    private function get_tunisian_cities($state) {
        $cities_by_state = array(
            'Ariana' => array('Ariana', 'Ettadhamen', 'Mnihla', 'Kalâat el-Andalous', 'Raoued', 'Sidi Thabet', 'La Soukra'),
            'Beja' => array('Béja', 'El Maâgoula', 'Nefza', 'Téboursouk', 'Testour', 'Amdoun', 'Goubellat'),
            'Ben Arous' => array('Ben Arous', 'Bou Mhel el-Bassatine', 'El Mourouj', 'Hammam Chott', 'Hammam Lif', 'Mohamedia-Fouchana', 'Mégrine', 'Rades', 'Ezzahra', 'Chihia', 'Mornag', 'Khalidia'),
            'Bizerte' => array('Bizerte', 'Sejnane', 'Mateur', 'Menzel Bourguiba', 'Tinja', 'Ghar El Melh', 'Ras Jebel', 'Menzel Jemil', 'El Alia', 'Utique', 'Raf Raf', 'Menzel Abderrahmane'),
            'Gabes' => array('Gabès', 'Chenini Nahal', 'Ghannouch', 'El Hamma', 'Matmata', 'Nouvelle Matmata', 'Mareth', 'Zarzis', 'Djerba - Ajim', 'Djerba - Houmt Souk', 'Djerba - Midoun', 'El Hamma de Djerba'),
            'Gafsa' => array('Gafsa', 'El Ksar', 'Métlaoui', 'Moularès', 'Redeyef', 'Sidi Aïch', 'El Guettar', 'Sened', 'Belkhir', 'Lela', 'Oum El Araies'),
            'Jendouba' => array('Jendouba', 'Bou Salem', 'Tabarka', 'Aïn Draham', 'Fernana', 'Beni MTir', 'Oued Meliz', 'Ghardimaou', 'Chestar', 'Balta-Bou Aouene'),
            'Kairouan' => array('Kairouan', 'Chebika', 'Sbikha', 'Oueslatia', 'Aïn Djeloula', 'Haffouz', 'Alaâ', 'Hajeb El Ayoun', 'Nasrallah', 'Menzel Mehiri', 'Echrarda'),
            'Kasserine' => array('Kasserine', 'Sbeitla', 'Fériana', 'Sbiba', 'Thala', 'Haïdra', 'Foussana', 'Jedelienne', 'Thelepte', 'Hidra', 'Majel Bel Abbès', 'Ezzouhour'),
            'Kebili' => array('Kébili', 'Douz', 'Faouar', 'Souk Lahad'),
            'Kef' => array('Le Kef', 'Dahmani', 'Sakiet Sidi Youssef', 'Sers', 'Kalâat Khasba', 'Kalâat Senan', 'Nebeur', 'Touiref', 'Tajerouine', 'Jérissa', 'El Ksour'),
            'Mahdia' => array('Mahdia', 'Bou Merdes', 'Ouled Chamekh', 'Chorbane', 'Hebira', 'Essouassi', 'El Djem', 'Kerker', 'Chebba', 'Melloulèche', 'Sidi Alouane', 'Rejiche', 'El Bradâa'),
            'Manouba' => array('La Manouba', 'Douar Hicher', 'Oued Ellil', 'Mornaguia', 'Borj El Amri', 'Djedeida', 'Tebourba', 'El Battan', 'Mateur', 'Jedaida', 'Sidi Thabet'),
            'Medenine' => array('Médenine', 'Ben Gardane', 'Zarzis', 'Djerba - Houmt Souk', 'Djerba - Midoun', 'Djerba - Ajim', 'Sidi Makhlouf', 'Beni Khedache', 'Smar', 'Zraoua'),
            'Monastir' => array('Monastir', 'Kantaoui', 'Moknine', 'Bembla', 'Teboulba', 'Ksar Hellal', 'Ksibet el-Médiouni', 'Bekalta', 'Téboulba', 'Zéramdine', 'Beni Hassen', 'Jemmal', 'Menzel Kamel', 'Sahline Moôtmar', 'Lamta', 'Bouhjar'),
            'Nabeul' => array('Nabeul', 'Dar Chaâbane El Fehri', 'Béni Khiar', 'El Maâmoura', 'Somaâ', 'Korba', 'Tazerka', 'Menzel Temime', 'Menzel Horr', 'El Haouaria', 'Takelsa', 'Kelibia', 'Azmour', 'Hammam Ghezèze', 'Dar Allouch', 'El Mida', 'Bou Argoub', 'Hammamet', 'Korbous', 'Menzel Bouzelfa', 'Béni Khalled', 'Zaouiet Djedidi', 'Grombalia', 'Soliman', 'Menzel Abderrahmane'),
            'Sfax' => array('Sfax', 'Sakiet Ezzit', 'Chihia', 'Sakiet Eddaïer', 'Gremda', 'El Hencha', 'Menzel Chaker', 'Ghraiba', 'Jebiniana', 'Lahouache', 'Sidi Hassen', 'Agareb', 'Skhira', 'Bir Ali Ben Khalifa', 'El Amra', 'Thyna', 'Sakiet Sidi Youssef', 'Ouled Chamekh', 'Essouassi', 'Haffouz', 'Sidi Bouzid', 'Meknassy', 'Regueb', 'Sidi Ali Ben Aoun', 'Cebbala Ouled Asker', 'Menzel Bouzaiane', 'Jilma', 'Bir El Hafey', 'Sidi Bouzid Est', 'Sidi Bouzid Ouest'),
            'Sidi Bouzid' => array('Sidi Bouzid', 'Cebbala Ouled Asker', 'Menzel Bouzaiane', 'Regueb', 'Sidi Ali Ben Aoun', 'Jilma', 'Bir El Hafey', 'Meknassy', 'Ouled Haffouz', 'Sidi Bouzid Est', 'Sidi Bouzid Ouest'),
            'Siliana' => array('Siliana', 'Bou Arada', 'Gaâfour', 'El Krib', 'Sidi Bou Rouis', 'Maktar', 'Rouhia', 'Kesra', 'Bargou', 'El Aroussa', 'Makthar', 'Sidi Bou Rouis'),
            'Sousse' => array('Sousse', 'Kantaoui', 'Hammam Sousse', 'Akouda', 'Kalâa Kebira', 'Kalâa Seghira', 'Kondar', 'Messaadine', 'Sidi El Hani', 'Bouficha', 'Enfidha', 'Hergla', 'Chott Meriem', 'Kantaoui', 'Port El Kantaoui'),
            'Tataouine' => array('Tataouine', 'Bir Lahmar', 'Ghomrassen', 'Dehiba', 'Remada', 'Tataouine Sud', 'Tataouine Nord'),
            'Tozeur' => array('Tozeur', 'Nefta', 'Degache', 'Hazoua', 'Tamaghza', 'El Hamma du Jérid', 'Chebika', 'Tamerza', 'Midès'),
            'Tunis' => array('Tunis', 'Le Bardo', 'Le Kram', 'La Goulette', 'Carthage', 'Sidi Bou Said', 'La Marsa', 'Sidi Hassine', 'Cité El Khadra', 'Bab El Bhar', 'Bab Souika', 'Bab Saadoun', 'Bab Jedid', 'Bab El Fellah', 'Bab El Khadra', 'Bab El Assal', 'Bab Sidi Abdessalem', 'Bab El Gorjani', 'Bab Saadoun', 'Bab El Bhar', 'Bab Souika', 'Bab Jedid', 'Bab El Fellah', 'Bab El Assal', 'Bab Sidi Abdessalem', 'Bab El Gorjani'),
            'Zaghouan' => array('Zaghouan', 'Zriba', 'Bir Mcherga', 'Joumine', 'El Fahs', 'Nadhour', 'Saouaf', 'El Haouanet', 'Djebel Oust', 'Zriba')
        );
        
        return isset($cities_by_state[$state]) ? $cities_by_state[$state] : array();
    }
    
    public function display_checkout_form() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        echo '<div id="cap-checkout-section" class="cap-checkout-section">';
        echo $this->render_mode_selection($product);
        echo $this->render_checkout_form($product);
        echo '</div>';
    }
    
    private function render_mode_selection($product) {
        ob_start();
        ?>
        <div class="cap-mode-selection">
            <h4><?php _e('Choisissez votre option d\'achat', 'checkout-after-product'); ?></h4>
            <div class="cap-mode-options">
                <div class="cap-mode-option">
                    <input type="radio" id="cap-mode-direct" name="cap_purchase_mode" value="direct" checked>
                    <label for="cap-mode-direct">
                        <span class="cap-mode-icon">⚡</span>
                        <span class="cap-mode-title"><?php _e('Commander directement', 'checkout-after-product'); ?></span>
                        <span class="cap-mode-description"><?php _e('Remplissez le formulaire et passez directement au paiement', 'checkout-after-product'); ?></span>
                    </label>
                </div>
                <div class="cap-mode-option">
                    <input type="radio" id="cap-mode-cart" name="cap_purchase_mode" value="cart">
                    <label for="cap-mode-cart">
                        <span class="cap-mode-icon">🛒</span>
                        <span class="cap-mode-title"><?php _e('Ajouter au panier', 'checkout-after-product'); ?></span>
                        <span class="cap-mode-description"><?php _e('Choisissez la quantité et ajoutez au panier pour continuer vos achats', 'checkout-after-product'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="cap-add-to-cart-section" style="display: none;">
            <?php echo $this->render_add_to_cart_section($product); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_add_to_cart_section($product) {
        ob_start();
        ?>
        <div class="cap-add-to-cart-container">
            <h4><?php _e('Ajouter au panier', 'checkout-after-product'); ?></h4>
            
            <div class="cap-product-info">
                <div class="cap-product-name">
                    <strong><?php echo esc_html($product->get_name()); ?></strong>
                </div>
                <div class="cap-product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
            </div>
            
            <div class="cap-quantity-section">
                <label for="cap-quantity"><?php _e('Quantité', 'checkout-after-product'); ?></label>
                <div class="cap-quantity-controls">
                    <button type="button" class="cap-quantity-btn" data-change="-1">-</button>
                    <input type="number" id="cap-quantity" class="cap-quantity-input" value="1" min="1" max="<?php echo $product->get_max_purchase_quantity(); ?>">
                    <button type="button" class="cap-quantity-btn" data-change="1">+</button>
                </div>
            </div>
            
            <div class="cap-add-to-cart-actions">
                <button type="button" class="cap-add-to-cart-btn button alt" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                    <?php _e('Ajouter au panier', 'checkout-after-product'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function checkout_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0
        ), $atts);
        
        if ($atts['product_id']) {
            $product = wc_get_product($atts['product_id']);
        } else {
            global $product;
        }
        
        if (!$product) {
            return '<p>' . __('Product not found.', 'checkout-after-product') . '</p>';
        }
        
        return $this->render_checkout_form($product);
    }
    
    private function render_checkout_form($product) {
        ob_start();
        ?>
        <div class="cap-checkout-form">
            <form id="cap-checkout-form" class="cap-form">
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
                <input type="hidden" name="action" value="cap_process_checkout">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('cap_checkout_nonce'); ?>">
                
                <div class="cap-form-section">
                    <h4><?php _e('Commander', 'checkout-after-product'); ?></h4>
                    
                    <div class="cap-form-field mt-3">
                        <label for="cap_full_name"><?php _e('Nom Complet', 'checkout-after-product'); ?> *</label>
                        <input type="text" id="cap_full_name" name="full_name" required>
                    </div>
                    
                    <div class="cap-form-field mt-3">
                        <label for="cap_billing_phone"><?php _e('Téléphone', 'checkout-after-product'); ?> *</label>
                        <input type="tel" id="cap_billing_phone" name="billing_phone" required>
                    </div>
                    
                    <div class="cap-form-field mt-3">
                        <label for="cap_billing_address"><?php _e('Adresse de Facturation', 'checkout-after-product'); ?> *</label>
                        <input type="text" id="cap_billing_address" name="billing_address" required>
                    </div>
                    
                    <div class="cap-form-row mt-3">
                    <div class="cap-form-field">
                            <label for="cap_billing_state"><?php _e('Gouvernorat', 'checkout-after-product'); ?></label>
                            <select id="cap_billing_state" name="billing_state">
                                <option value=""><?php _e('Sélectionnez un gouvernorat', 'checkout-after-product'); ?></option>
                                <option value="Ariana">Ariana</option>
                                <option value="Beja">Beja</option>
                                <option value="Ben Arous">Ben Arous</option>
                                <option value="Bizerte">Bizerte</option>
                                <option value="Gabes">Gabes</option>
                                <option value="Gafsa">Gafsa</option>
                                <option value="Jendouba">Jendouba</option>
                                <option value="Kairouan">Kairouan</option>
                                <option value="Kasserine">Kasserine</option>
                                <option value="Kebili">Kebili</option>
                                <option value="Kef">Kef</option>
                                <option value="Mahdia">Mahdia</option>
                                <option value="Manouba">Manouba</option>
                                <option value="Medenine">Medenine</option>
                                <option value="Monastir">Monastir</option>
                                <option value="Nabeul">Nabeul</option>
                                <option value="Sfax">Sfax</option>
                                <option value="Sidi Bouzid">Sidi Bouzid</option>
                                <option value="Siliana">Siliana</option>
                                <option value="Sousse">Sousse</option>
                                <option value="Tataouine">Tataouine</option>
                                <option value="Tozeur">Tozeur</option>
                                <option value="Tunis">Tunis</option>
                                <option value="Zaghouan">Zaghouan</option>
                            </select>
                        </div>
                        <div class="cap-form-field mt-3">
                            <label for="cap_billing_city"><?php _e('Ville de Facturation', 'checkout-after-product'); ?> *</label>
                            <select id="cap_billing_city" name="billing_city" required>
                                <option value=""><?php _e('Sélectionnez une ville', 'checkout-after-product'); ?></option>
                            </select>
                        </div>
                       
                    </div>
                </div>
                
                <div class="cap-form-actions">
                    <button type="submit" id="cap-submit-order" class="single_add_to_cart_button button alt" style="width: 100%;">
                        <?php _e('Commander Maintenant', 'checkout-after-product'); ?>
                    </button>
                    
                </div>
            </form>
            
            <div id="cap-messages"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function validate_form() {
        check_ajax_referer('cap_checkout_nonce', 'nonce');
        
        $errors = array();
        $data = $_POST;
        
        // Validate required fields
        $required_fields = array('first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'postcode', 'country');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('%s is required.', 'checkout-after-product'), ucfirst(str_replace('_', ' ', $field)));
            }
        }
        
        // Validate email
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = __('Please enter a valid email address.', 'checkout-after-product');
        }
       
        
        if (!empty($errors)) {
            wp_send_json_error(array('errors' => $errors));
        } else {
            wp_send_json_success();
        }
    }
    
    public function prefill_checkout_fields($value, $field) {
        // Only pre-fill if we have session data from our form
        if (!WC()->session->get('cap_customer_first_name')) {
            return $value;
        }
        
        switch ($field) {
            case 'billing_first_name':
                return WC()->session->get('cap_customer_first_name');
            case 'billing_last_name':
                return WC()->session->get('cap_customer_last_name');
            case 'billing_email':
                return WC()->session->get('cap_customer_email');
            case 'billing_phone':
                return WC()->session->get('cap_customer_phone');
            case 'billing_company':
                return WC()->session->get('cap_customer_company');
            case 'billing_address_1':
                return WC()->session->get('cap_customer_address_1');
            case 'billing_address_2':
                return WC()->session->get('cap_customer_address_2');
            case 'billing_city':
                return WC()->session->get('cap_customer_city');
            case 'billing_state':
                return WC()->session->get('cap_customer_state');
            case 'billing_postcode':
                return WC()->session->get('cap_customer_postcode');
            case 'billing_country':
                return WC()->session->get('cap_customer_country');
            case 'shipping_first_name':
                return WC()->session->get('cap_customer_first_name');
            case 'shipping_last_name':
                return WC()->session->get('cap_customer_last_name');
            case 'shipping_company':
                return WC()->session->get('cap_customer_company');
            case 'shipping_address_1':
                return WC()->session->get('cap_customer_address_1');
            case 'shipping_address_2':
                return WC()->session->get('cap_customer_address_2');
            case 'shipping_city':
                return WC()->session->get('cap_customer_city');
            case 'shipping_state':
                return WC()->session->get('cap_customer_state');
            case 'shipping_postcode':
                return WC()->session->get('cap_customer_postcode');
            case 'shipping_country':
                return WC()->session->get('cap_customer_country');
            default:
                return $value;
        }
    }
    
    public function process_checkout() {
        check_ajax_referer('cap_checkout_nonce', 'nonce');
        
        $data = $_POST;
        $product_id = intval($data['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array('message' => __('Produit introuvable.', 'checkout-after-product')));
        }
        
        // Validate form data
        $errors = array();
        $required_fields = array('full_name', 'billing_phone', 'billing_address', 'billing_city');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $field_names = array(
                    'full_name' => __('Nom Complet', 'checkout-after-product'),
                    'billing_phone' => __('Téléphone', 'checkout-after-product'),
                    'billing_address' => __('Adresse de Facturation', 'checkout-after-product'),
                    'billing_city' => __('Ville de Facturation', 'checkout-after-product')
                );
                $field_name = isset($field_names[$field]) ? $field_names[$field] : ucfirst(str_replace('_', ' ', $field));
                $errors[] = sprintf(__('%s est requis.', 'checkout-after-product'), $field_name);
            }
        }
        
        // Validate phone number
        if (!empty($data['billing_phone']) && strlen(preg_replace('/[^0-9]/', '', $data['billing_phone'])) < 8) {
            $errors[] = __('Veuillez entrer un numéro de téléphone valide (au moins 8 chiffres).', 'checkout-after-product');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('errors' => $errors));
        }
        
        try {
            // Add product to cart
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id, 1);
            
            // Split full name into first and last name
            $name_parts = explode(' ', trim($data['full_name']), 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Store customer data in session for WooCommerce checkout
            WC()->session->set('cap_customer_first_name', sanitize_text_field($first_name));
            WC()->session->set('cap_customer_last_name', sanitize_text_field($last_name));
            WC()->session->set('cap_customer_phone', sanitize_text_field($data['billing_phone']));
            WC()->session->set('cap_customer_address_1', sanitize_text_field($data['billing_address']));
            WC()->session->set('cap_customer_city', sanitize_text_field($data['billing_city']));
            WC()->session->set('cap_customer_state', sanitize_text_field($data['billing_state']));
            
            // Set default values for other required fields
            WC()->session->set('cap_customer_email', ''); // Will be filled by user on checkout
            WC()->session->set('cap_customer_postcode', ''); // Will be filled by user on checkout
            WC()->session->set('cap_customer_country', 'TN'); // Default country (Tunisia)
            WC()->session->set('cap_customer_company', '');
            WC()->session->set('cap_customer_address_2', '');
            
            // Redirect to WooCommerce checkout
            $checkout_url = wc_get_checkout_url();
            
            wp_send_json_success(array(
                'message' => __('Redirection vers le paiement...', 'checkout-after-product'),
                'redirect_url' => $checkout_url
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    private function get_payment_method_title($method) {
        $titles = array(
            'credit_card' => __('Credit Card', 'checkout-after-product'),
            'paypal' => __('PayPal', 'checkout-after-product'),
            'bank_transfer' => __('Bank Transfer', 'checkout-after-product')
        );
        
        return isset($titles[$method]) ? $titles[$method] : $method;
    }
}

// Initialize the plugin
new CheckoutAfterProduct();

// Activation hook
register_activation_hook(__FILE__, 'cap_activate');
function cap_activate() {
    // Create necessary database tables or options if needed
    add_option('cap_version', CAP_PLUGIN_VERSION);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'cap_deactivate');
function cap_deactivate() {
    // Clean up if necessary
} 