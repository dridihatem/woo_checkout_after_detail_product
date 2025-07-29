<?php
/**
 * Admin Settings for Checkout After Product Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CAP_Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('Checkout After Product Settings', 'checkout-after-product'),
            __('Checkout After Product', 'checkout-after-product'),
            'manage_options',
            'checkout-after-product',
            array($this, 'settings_page')
        );
    }
    
    public function init_settings() {
        register_setting('cap_settings', 'cap_options');
        
        add_settings_section(
            'cap_general_section',
            __('General Settings', 'checkout-after-product'),
            array($this, 'general_section_callback'),
            'checkout-after-product'
        );
        
        add_settings_field(
            'cap_enable_auto_display',
            __('Enable Auto Display', 'checkout-after-product'),
            array($this, 'checkbox_callback'),
            'checkout-after-product',
            'cap_general_section',
            array('field' => 'enable_auto_display')
        );
        

        
        add_settings_field(
            'cap_form_title',
            __('Form Title', 'checkout-after-product'),
            array($this, 'text_callback'),
            'checkout-after-product',
            'cap_general_section',
            array('field' => 'form_title')
        );
        
        add_settings_field(
            'cap_button_text',
            __('Submit Button Text', 'checkout-after-product'),
            array($this, 'text_callback'),
            'checkout-after-product',
            'cap_general_section',
            array('field' => 'button_text')
        );
        
        add_settings_field(
            'cap_success_message',
            __('Success Message', 'checkout-after-product'),
            array($this, 'textarea_callback'),
            'checkout-after-product',
            'cap_general_section',
            array('field' => 'success_message')
        );
        
        add_settings_section(
            'cap_styling_section',
            __('Styling Options', 'checkout-after-product'),
            array($this, 'styling_section_callback'),
            'checkout-after-product'
        );
        
        add_settings_field(
            'cap_primary_color',
            __('Primary Color', 'checkout-after-product'),
            array($this, 'color_callback'),
            'checkout-after-product',
            'cap_styling_section',
            array('field' => 'primary_color')
        );
        
        add_settings_field(
            'cap_button_color',
            __('Button Color', 'checkout-after-product'),
            array($this, 'color_callback'),
            'checkout-after-product',
            'cap_styling_section',
            array('field' => 'button_color')
        );
        
        add_settings_field(
            'cap_custom_css',
            __('Custom CSS', 'checkout-after-product'),
            array($this, 'textarea_callback'),
            'checkout-after-product',
            'cap_styling_section',
            array('field' => 'custom_css')
        );
    }
    
    public function settings_page() {
        $options = get_option('cap_options', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Checkout After Product Settings', 'checkout-after-product'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cap_settings');
                do_settings_sections('checkout-after-product');
                submit_button();
                ?>
            </form>
            
            <div class="cap-admin-info">
                <h3><?php _e('Shortcode Usage', 'checkout-after-product'); ?></h3>
                <p><?php _e('Use this shortcode to display the checkout form anywhere:', 'checkout-after-product'); ?></p>
                <code>[checkout_after_product]</code>
                
                <h3><?php _e('Documentation', 'checkout-after-product'); ?></h3>
                <p><?php _e('For detailed documentation and customization options, please refer to the README file.', 'checkout-after-product'); ?></p>
            </div>
        </div>
        
        <style>
            .cap-admin-info {
                margin-top: 30px;
                padding: 20px;
                background: #f9f9f9;
                border-left: 4px solid #007cba;
            }
            .cap-admin-info h3 {
                margin-top: 0;
            }
            .cap-admin-info code {
                background: #fff;
                padding: 5px 10px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
        </style>
        <?php
    }
    
    public function general_section_callback() {
        echo '<p>' . __('Configure the general settings for the checkout form.', 'checkout-after-product') . '</p>';
    }
    
    public function styling_section_callback() {
        echo '<p>' . __('Customize the appearance of the checkout form.', 'checkout-after-product') . '</p>';
    }
    
    public function checkbox_callback($args) {
        $options = get_option('cap_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : 1;
        ?>
        <input type="checkbox" name="cap_options[<?php echo $field; ?>]" value="1" <?php checked(1, $value); ?> />
        <span class="description"><?php _e('Automatically display checkout form after product content', 'checkout-after-product'); ?></span>
        <?php
    }
    

    
    public function text_callback($args) {
        $options = get_option('cap_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        
        switch ($field) {
            case 'form_title':
                $default = __('Quick Checkout', 'checkout-after-product');
                break;
            case 'button_text':
                $default = __('Order Now', 'checkout-after-product');
                break;
            default:
                $default = '';
        }
        
        if (empty($value)) {
            $value = $default;
        }
        ?>
        <input type="text" name="cap_options[<?php echo $field; ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <?php
    }
    
    public function textarea_callback($args) {
        $options = get_option('cap_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        
        switch ($field) {
            case 'success_message':
                $default = __('Redirecting to checkout...', 'checkout-after-product');
                break;
            default:
                $default = '';
        }
        
        if (empty($value)) {
            $value = $default;
        }
        ?>
        <textarea name="cap_options[<?php echo $field; ?>]" rows="4" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <?php
    }
    
    public function color_callback($args) {
        $options = get_option('cap_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        
        switch ($field) {
            case 'primary_color':
                $default = '#007cba';
                break;
            case 'button_color':
                $default = '#007cba';
                break;
            default:
                $default = '#007cba';
        }
        
        if (empty($value)) {
            $value = $default;
        }
        ?>
        <input type="color" name="cap_options[<?php echo $field; ?>]" value="<?php echo esc_attr($value); ?>" />
        <span class="description"><?php _e('Choose a color for this element', 'checkout-after-product'); ?></span>
        <?php
    }
}

// Initialize admin settings
new CAP_Admin_Settings(); 