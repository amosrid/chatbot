<?php
/**
 * WP DeepSeek Chatbot Settings
 *
 * @package WP_DeepSeek_Chatbot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class WP_DeepSeek_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('DeepSeek Chatbot', 'wp-deepseek-chatbot'),
            __('DeepSeek Chatbot', 'wp-deepseek-chatbot'),
            'manage_options',
            'wp-deepseek-chatbot-settings',
            array($this, 'display_settings_page'),
            'dashicons-format-chat',
            100
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_settings',
            array($this, 'sanitize_settings')
        );

        // General Settings Section
        add_settings_section(
            'wp_deepseek_chatbot_general',
            __('General Settings', 'wp-deepseek-chatbot'),
            array($this, 'general_section_callback'),
            'wp_deepseek_chatbot_settings'
        );

        // API Key Field
        add_settings_field(
            'api_key',
            __('OpenRouter API Key', 'wp-deepseek-chatbot'),
            array($this, 'api_key_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_general'
        );

        // Website URL Field
        add_settings_field(
            'website_url',
            __('Website URL', 'wp-deepseek-chatbot'),
            array($this, 'website_url_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_general'
        );

        // Website Name Field
        add_settings_field(
            'website_name',
            __('Website Name', 'wp-deepseek-chatbot'),
            array($this, 'website_name_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_general'
        );

        // Agent Name Field
        add_settings_field(
            'agent_name',
            __('Agent Name', 'wp-deepseek-chatbot'),
            array($this, 'agent_name_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_general'
        );
        
        // Language Field
        add_settings_field(
            'language',
            __('Language', 'wp-deepseek-chatbot'),
            array($this, 'language_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_general'
        );
        

        // Appearance Settings Section
        add_settings_section(
            'wp_deepseek_chatbot_appearance',
            __('Appearance Settings', 'wp-deepseek-chatbot'),
            array($this, 'appearance_section_callback'),
            'wp_deepseek_chatbot_settings'
        );

        // Primary Color Field
        add_settings_field(
            'primary_color',
            __('Primary Color', 'wp-deepseek-chatbot'),
            array($this, 'primary_color_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_appearance'
        );

        // Secondary Color Field
        add_settings_field(
            'secondary_color',
            __('Secondary Color', 'wp-deepseek-chatbot'),
            array($this, 'secondary_color_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_appearance'
        );

        // Font Family Field
        add_settings_field(
            'font_family',
            __('Font Family', 'wp-deepseek-chatbot'),
            array($this, 'font_family_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_appearance'
        );

        // Custom CSS Field
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'wp-deepseek-chatbot'),
            array($this, 'custom_css_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_appearance'
        );

        // Chat Training Settings Section
        add_settings_section(
            'wp_deepseek_chatbot_training',
            __('Chat Training', 'wp-deepseek-chatbot'),
            array($this, 'training_section_callback'),
            'wp_deepseek_chatbot_settings'
        );

        // Official Contact Information Field
        add_settings_field(
            'official_contacts',
            __('Official Contacts', 'wp-deepseek-chatbot'),
            array($this, 'official_contacts_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_training'
        );

        // URL Links Field
        add_settings_field(
            'url_links',
            __('URL Links', 'wp-deepseek-chatbot'),
            array($this, 'url_links_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_training'
        );

        add_settings_field(
            'custom_facts',
            __('Custom Facts', 'wp-deepseek-chatbot'),
            array($this, 'custom_facts_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_training'
        );

        // Knowledge Base Field
        add_settings_field(
            'knowledge_base',
            __('Knowledge Base', 'wp-deepseek-chatbot'),
            array($this, 'knowledge_base_callback'),
            'wp_deepseek_chatbot_settings',
            'wp_deepseek_chatbot_training'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $output = array();

        // API Key
        $output['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
        
        // Website URL
        $output['website_url'] = isset($input['website_url']) ? esc_url_raw($input['website_url']) : '';
        
        // Website Name
        $output['website_name'] = isset($input['website_name']) ? sanitize_text_field($input['website_name']) : '';
        
        // Agent Name
        $output['agent_name'] = isset($input['agent_name']) ? sanitize_text_field($input['agent_name']) : '';
        
        // Language
        $output['language'] = isset($input['language']) ? sanitize_text_field($input['language']) : 'id_ID';
        
        // Primary Color
        $output['primary_color'] = isset($input['primary_color']) ? sanitize_hex_color($input['primary_color']) : '#4a90e2';
        
        // Secondary Color
        $output['secondary_color'] = isset($input['secondary_color']) ? sanitize_hex_color($input['secondary_color']) : '#f5f5f5';
        
        // Font Family
        $output['font_family'] = isset($input['font_family']) ? sanitize_text_field($input['font_family']) : 'Arial, sans-serif';
        
        // Custom CSS
        $output['custom_css'] = isset($input['custom_css']) ? wp_strip_all_tags($input['custom_css']) : '';

        // Official Contacts (array of contacts)
        if (isset($input['official_contacts']) && is_array($input['official_contacts'])) {
            $output['official_contacts'] = array();
            foreach ($input['official_contacts'] as $contact) {
                if (!empty($contact['name']) || !empty($contact['value'])) {
                    $output['official_contacts'][] = array(
                        'name' => sanitize_text_field($contact['name']),
                        'value' => sanitize_text_field($contact['value']),
                    );
                }
            }
        } else {
            $output['official_contacts'] = array();
        }
        
        // URL Links (array of links)
        if (isset($input['url_links'])) {
            $raw_links = sanitize_textarea_field($input['url_links']);
            // Store the raw text format
            $output['url_links'] = $raw_links;
        } else {
            $output['url_links'] = array();
        }




        // Custom Facts (key-value pairs)
        if (isset($input['custom_facts']) && is_array($input['custom_facts'])) {
            $output['custom_facts'] = array();
            foreach ($input['custom_facts'] as $fact) {
                if (!empty($fact['key']) || !empty($fact['value'])) {
                    $output['custom_facts'][] = array(
                        'key' => sanitize_text_field($fact['key']),
                        'value' => sanitize_text_field($fact['value']),
                    );
                }
            }
        } else {
            $output['custom_facts'] = array();
        }
        
        // Knowledge Base (array of objects)
        if (isset($input['knowledge_base']) && is_array($input['knowledge_base'])) {
            $output['knowledge_base'] = array();
            foreach ($input['knowledge_base'] as $item) {
                $sanitized_item = array(
                    'question' => isset($item['question']) ? sanitize_text_field($item['question']) : '',
                    'answer' => isset($item['answer']) ? wp_kses_post($item['answer']) : '',
                );
                $output['knowledge_base'][] = $sanitized_item;
            }
        } else {
            $output['knowledge_base'] = array();
        }

        return $output;
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_deepseek_chatbot_settings');
                do_settings_sections('wp_deepseek_chatbot_settings');
                submit_button(__('Save Settings', 'wp-deepseek-chatbot'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure the general settings for the DeepSeek AI chatbot.', 'wp-deepseek-chatbot') . '</p>';
    }

    /**
     * API key callback
     */
    public function api_key_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        ?>
        <input type="password" id="api_key" name="wp_deepseek_chatbot_settings[api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Enter your OpenRouter API key to access DeepSeek AI.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Website URL callback
     */
    public function website_url_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $website_url = isset($settings['website_url']) ? $settings['website_url'] : get_site_url();
        ?>
        <input type="url" id="website_url" name="wp_deepseek_chatbot_settings[website_url]" value="<?php echo esc_attr($website_url); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Enter your website URL for the HTTP-Referer header.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Website name callback
     */
    public function website_name_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $website_name = isset($settings['website_name']) ? $settings['website_name'] : get_bloginfo('name');
        ?>
        <input type="text" id="website_name" name="wp_deepseek_chatbot_settings[website_name]" value="<?php echo esc_attr($website_name); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Enter your website name for the X-Title header.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Agent name callback
     */
    public function agent_name_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $agent_name = isset($settings['agent_name']) ? $settings['agent_name'] : __('AI Assistant', 'wp-deepseek-chatbot');
        ?>
        <input type="text" id="agent_name" name="wp_deepseek_chatbot_settings[agent_name]" value="<?php echo esc_attr($agent_name); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Enter a name for your chatbot agent.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Language callback
     */
    public function language_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $language = isset($settings['language']) ? $settings['language'] : 'id_ID';
        $languages = array(
            'id_ID' => __('Indonesian', 'wp-deepseek-chatbot'),
            'en_US' => __('English (US)', 'wp-deepseek-chatbot'),
        );
        ?>
        <select id="language" name="wp_deepseek_chatbot_settings[language]">
            <?php foreach ($languages as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($language, $code); ?>><?php echo esc_html($name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Select the default language for the chatbot.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Appearance section callback
     */
    public function appearance_section_callback() {
        echo '<p>' . esc_html__('Customize the appearance of the DeepSeek AI chatbot.', 'wp-deepseek-chatbot') . '</p>';
    }

    /**
     * Primary color callback
     */
    public function primary_color_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#4a90e2';
        ?>
        <input type="color" id="primary_color" name="wp_deepseek_chatbot_settings[primary_color]" value="<?php echo esc_attr($primary_color); ?>">
        <p class="description"><?php esc_html_e('Select the primary color for the chatbot.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Secondary color callback
     */
    public function secondary_color_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#f5f5f5';
        ?>
        <input type="color" id="secondary_color" name="wp_deepseek_chatbot_settings[secondary_color]" value="<?php echo esc_attr($secondary_color); ?>">
        <p class="description"><?php esc_html_e('Select the secondary color for the chatbot.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Font family callback
     */
    public function font_family_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $font_family = isset($settings['font_family']) ? $settings['font_family'] : 'Arial, sans-serif';
        $common_fonts = array(
            'Arial, sans-serif' => 'Arial',
            'Helvetica, sans-serif' => 'Helvetica',
            'Georgia, serif' => 'Georgia',
            'Times New Roman, serif' => 'Times New Roman',
            'Verdana, sans-serif' => 'Verdana',
        );
        ?>
        <select id="font_family" name="wp_deepseek_chatbot_settings[font_family]">
            <?php foreach ($common_fonts as $font => $label) : ?>
                <option value="<?php echo esc_attr($font); ?>" <?php selected($font_family, $font); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Select the font family for the chatbot.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }
    /**
     * Custom CSS callback
     */
    public function custom_css_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $custom_css = isset($settings['custom_css']) ? $settings['custom_css'] : '';
        ?>
        <textarea id="custom_css" name="wp_deepseek_chatbot_settings[custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
        <p class="description"><?php esc_html_e('Enter custom CSS to further customize the chatbot appearance.', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }

    /**
     * Training section callback
     */
    public function training_section_callback() {
        echo '<p>' . esc_html__('Configure custom facts and knowledge base to improve chatbot responses.', 'wp-deepseek-chatbot') . '</p>';
    }

        /**
     * Official contacts callback
     */
    public function official_contacts_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $official_contacts = isset($settings['official_contacts']) ? $settings['official_contacts'] : array(array('name' => '', 'value' => ''));
        ?>
        <div id="official-contacts-container">
            <?php foreach ($official_contacts as $index => $contact) : ?>
                <div class="official-contact-row">
                    <input type="text" name="wp_deepseek_chatbot_settings[official_contacts][<?php echo esc_attr($index); ?>][name]" 
                           value="<?php echo esc_attr($contact['name']); ?>" class="regular-text" 
                           placeholder="<?php esc_attr_e('Contact Name (e.g. Sales Department)', 'wp-deepseek-chatbot'); ?>">
                    <span class="contact-separator">:</span>
                    <input type="text" name="wp_deepseek_chatbot_settings[official_contacts][<?php echo esc_attr($index); ?>][value]" 
                           value="<?php echo esc_attr($contact['value']); ?>" class="large-text" 
                           placeholder="<?php esc_attr_e('Contact Details (e.g. 021-555-1234 or sales@example.com)', 'wp-deepseek-chatbot'); ?>">
                    <button type="button" class="button remove-official-contact" <?php echo (count($official_contacts) <= 1) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Remove', 'wp-deepseek-chatbot'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button add-official-contact"><?php esc_html_e('Add Official Contact', 'wp-deepseek-chatbot'); ?></button>
        <p class="description"><?php esc_html_e('Add official contact information that the chatbot can provide to customers when they need human assistance.', 'wp-deepseek-chatbot'); ?></p>

        <script>
            jQuery(document).ready(function($) {
                // Add official contact
                $('.add-official-contact').on('click', function() {
                    var index = $('.official-contact-row').length;
                    var row = $('<div class="official-contact-row"></div>');
                    
                    var nameInput = $('<input type="text" name="wp_deepseek_chatbot_settings[official_contacts][' + index + '][name]" value="" class="regular-text" placeholder="<?php esc_attr_e('Contact Name (e.g. Sales Department)', 'wp-deepseek-chatbot'); ?>">');
                    var separator = $('<span class="contact-separator">:</span>');
                    var valueInput = $('<input type="text" name="wp_deepseek_chatbot_settings[official_contacts][' + index + '][value]" value="" class="large-text" placeholder="<?php esc_attr_e('Contact Details (e.g. 021-555-1234 or sales@example.com)', 'wp-deepseek-chatbot'); ?>">');
                    var removeButton = $('<button type="button" class="button remove-official-contact"><?php esc_html_e('Remove', 'wp-deepseek-chatbot'); ?></button>');
                    
                    row.append(nameInput);
                    row.append(separator);
                    row.append(valueInput);
                    row.append(removeButton);
                    $('#official-contacts-container').append(row);
                    
                    // Show all remove buttons when there's more than one row
                    if ($('.official-contact-row').length > 1) {
                        $('.remove-official-contact').show();
                    }
                });
                
                // Remove official contact
                $(document).on('click', '.remove-official-contact', function() {
                    $(this).parent().remove();
                    
                    // Update input names
                    $('.official-contact-row').each(function(index) {
                        $(this).find('input').first().attr('name', 'wp_deepseek_chatbot_settings[official_contacts][' + index + '][name]');
                        $(this).find('input').last().attr('name', 'wp_deepseek_chatbot_settings[official_contacts][' + index + '][value]');
                    });
                    
                    // Hide remove button if only one row remains
                    if ($('.official-contact-row').length <= 1) {
                        $('.remove-official-contact').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * URL links callback
     */

    public function url_links_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $url_links = isset($settings['url_links']) ? $settings['url_links'] : '';
        
        // Format existing links if they exist in array format
        if (is_array($settings['url_links'])) {
            $formatted_links = "";
            foreach ($settings['url_links'] as $link) {
                if (!empty($link['label']) && !empty($link['url'])) {
                    $formatted_links .= "- " . $link['label'] . " = " . $link['url'] . "\n";
                }
            }
            $url_links = $formatted_links;
        }
        ?>
        <textarea id="url_links" name="wp_deepseek_chatbot_settings[url_links]" rows="10" class="large-text"><?php echo esc_textarea($url_links); ?></textarea>
        <p class="description"><?php esc_html_e('Masukkan daftar URL penting dalam format: "- Label = URL" (satu link per baris).', 'wp-deepseek-chatbot'); ?></p>
        <p class="description"><?php esc_html_e('Contoh: "- Halaman Utama = https://example.com/"', 'wp-deepseek-chatbot'); ?></p>
        <?php
    }
    /**
     * Conversation starters callback
     */
    public function custom_facts_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $custom_facts = isset($settings['custom_facts']) ? $settings['custom_facts'] : array(array('key' => '', 'value' => ''));
        ?>
        <div id="custom-facts-container">
            <?php foreach ($custom_facts as $index => $fact) : ?>
                <div class="custom-fact-row">
                    <input type="text" name="wp_deepseek_chatbot_settings[custom_facts][<?php echo esc_attr($index); ?>][key]" 
                           value="<?php echo esc_attr($fact['key']); ?>" class="regular-text" 
                           placeholder="<?php esc_attr_e('Fact Name (e.g. BR-V)', 'wp-deepseek-chatbot'); ?>">
                    <span class="fact-separator">:</span>
                    <textarea name="wp_deepseek_chatbot_settings[custom_facts][<?php echo esc_attr($index); ?>][value]" 
                           class="large-text fact-details" rows="3"
                           placeholder="<?php esc_attr_e('Fact Details (e.g. Honda BR-V is priced at IDR 275,900,000)', 'wp-deepseek-chatbot'); ?>"><?php echo esc_textarea($fact['value']); ?></textarea>
                    <button type="button" class="button remove-custom-fact" <?php echo (count($custom_facts) <= 1) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Remove', 'wp-deepseek-chatbot'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button add-custom-fact"><?php esc_html_e('Add Custom Fact', 'wp-deepseek-chatbot'); ?></button>
        <p class="description"><?php esc_html_e('Add custom facts about your products, services, or business that the chatbot should know.', 'wp-deepseek-chatbot'); ?></p>
        <style>
            .custom-fact-row {
                display: flex;
                flex-direction: column;
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 5px;
            }
            .custom-fact-row input {
                margin-bottom: 8px;
            }
            .fact-separator {
                margin: 5px 0;
                font-weight: bold;
            }
            .fact-details {
                margin-bottom: 10px;
                width: 100%;
                min-height: 80px;
            }
            .remove-custom-fact {
                align-self: flex-end;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Add custom fact
                $('.add-custom-fact').on('click', function() {
                    var index = $('.custom-fact-row').length;
                    var row = $('<div class="custom-fact-row"></div>');
                    
                    var keyInput = $('<input type="text" name="wp_deepseek_chatbot_settings[custom_facts][' + index + '][key]" value="" class="regular-text" placeholder="<?php esc_attr_e('Fact Name (e.g. BR-V)', 'wp-deepseek-chatbot'); ?>">');
                    var separator = $('<span class="fact-separator">:</span>');
                    var valueInput = $('<textarea name="wp_deepseek_chatbot_settings[custom_facts][' + index + '][value]" class="large-text fact-details" rows="3" placeholder="<?php esc_attr_e('Fact Details (e.g. Honda BR-V is priced at IDR 275,900,000)', 'wp-deepseek-chatbot'); ?>"></textarea>');
                    var removeButton = $('<button type="button" class="button remove-custom-fact"><?php esc_html_e('Remove', 'wp-deepseek-chatbot'); ?></button>');
                    
                    row.append(keyInput);
                    row.append(separator);
                    row.append(valueInput);
                    row.append(removeButton);
                    $('#custom-facts-container').append(row);
                    
                    // Show all remove buttons when there's more than one row
                    if ($('.custom-fact-row').length > 1) {
                        $('.remove-custom-fact').show();
                    }
                });
                
                // Remove custom fact
                $(document).on('click', '.remove-custom-fact', function() {
                    $(this).parent().remove();
                    
                    // Update input names
                    $('.custom-fact-row').each(function(index) {
                        $(this).find('input').first().attr('name', 'wp_deepseek_chatbot_settings[custom_facts][' + index + '][key]');
                        $(this).find('textarea').attr('name', 'wp_deepseek_chatbot_settings[custom_facts][' + index + '][value]');
                    });
                    
                    // Hide remove button if only one row remains
                    if ($('.custom-fact-row').length <= 1) {
                        $('.remove-custom-fact').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Knowledge base callback
     */
    public function knowledge_base_callback() {
        $settings = get_option('wp_deepseek_chatbot_settings');
        $knowledge_base = isset($settings['knowledge_base']) ? $settings['knowledge_base'] : array(array('question' => '', 'answer' => ''));
        ?>
        <div id="knowledge-base-container">
            <?php foreach ($knowledge_base as $index => $item) : ?>
                <div class="knowledge-base-item">
                    <h4><?php esc_html_e('FAQ Item', 'wp-deepseek-chatbot'); ?> #<?php echo esc_html($index + 1); ?></h4>
                    <div>
                        <label><?php esc_html_e('Question', 'wp-deepseek-chatbot'); ?></label>
                        <input type="text" name="wp_deepseek_chatbot_settings[knowledge_base][<?php echo esc_attr($index); ?>][question]" value="<?php echo esc_attr($item['question']); ?>" class="large-text">
                    </div>
                    <div>
                        <label><?php esc_html_e('Answer', 'wp-deepseek-chatbot'); ?></label>
                        <?php 
                        $editor_id = 'knowledge_base_answer_' . $index;
                        $editor_content = isset($item['answer']) ? $item['answer'] : '';
                        $editor_name = 'wp_deepseek_chatbot_settings[knowledge_base][' . $index . '][answer]';
                        
                        wp_editor($editor_content, $editor_id, array(
                            'textarea_name' => $editor_name,
                            'textarea_rows' => 5,
                            'media_buttons' => false,
                            'teeny' => true,
                        ));
                        ?>
                    </div>
                    <button type="button" class="button remove-knowledge-base-item" <?php echo (count($knowledge_base) <= 1) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Remove This FAQ', 'wp-deepseek-chatbot'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button add-knowledge-base-item"><?php esc_html_e('Add FAQ Item', 'wp-deepseek-chatbot'); ?></button>
        <p class="description"><?php esc_html_e('Add frequently asked questions and their answers to train the chatbot.', 'wp-deepseek-chatbot'); ?></p>

        <script>
            jQuery(document).ready(function($) {
                // Add knowledge base item
                $('.add-knowledge-base-item').on('click', function() {
                    var index = $('.knowledge-base-item').length;
                    var item = $('<div class="knowledge-base-item"></div>');
                    
                    item.append('<h4><?php esc_html_e('FAQ Item', 'wp-deepseek-chatbot'); ?> #' + (index + 1) + '</h4>');
                    
                    var questionDiv = $('<div></div>');
                    questionDiv.append('<label><?php esc_html_e('Question', 'wp-deepseek-chatbot'); ?></label>');
                    questionDiv.append('<input type="text" name="wp_deepseek_chatbot_settings[knowledge_base][' + index + '][question]" value="" class="large-text">');
                    item.append(questionDiv);
                    
                    var answerDiv = $('<div></div>');
                    answerDiv.append('<label><?php esc_html_e('Answer', 'wp-deepseek-chatbot'); ?></label>');
                    answerDiv.append('<textarea name="wp_deepseek_chatbot_settings[knowledge_base][' + index + '][answer]" rows="5" class="large-text"></textarea>');
                    item.append(answerDiv);
                    
                    var removeButton = $('<button type="button" class="button remove-knowledge-base-item"><?php esc_html_e('Remove This FAQ', 'wp-deepseek-chatbot'); ?></button>');
                    item.append(removeButton);
                    
                    $('#knowledge-base-container').append(item);
                    
                    // Show all remove buttons when there's more than one item
                    if ($('.knowledge-base-item').length > 1) {
                        $('.remove-knowledge-base-item').show();
                    }
                });

                // Remove knowledge base item
                $(document).on('click', '.remove-knowledge-base-item', function() {
                    $(this).parent().remove();
                    
                    // Update item numbers
                    $('.knowledge-base-item').each(function(index) {
                        $(this).find('h4').text('<?php esc_html_e('FAQ Item', 'wp-deepseek-chatbot'); ?> #' + (index + 1));
                        
                        // Update input names
                        $(this).find('input').attr('name', 'wp_deepseek_chatbot_settings[knowledge_base][' + index + '][question]');
                        $(this).find('textarea').attr('name', 'wp_deepseek_chatbot_settings[knowledge_base][' + index + '][answer]');
                    });
                    
                    // Hide remove button if only one item remains
                    if ($('.knowledge-base-item').length <= 1) {
                        $('.remove-knowledge-base-item').hide();
                    }
                });
            });
        </script>
        <?php
    }
}

// Initialize settings
new WP_DeepSeek_Settings();