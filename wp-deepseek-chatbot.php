<?php
/**
 * Plugin Name: WP DeepSeek AI Chatbot
 * Plugin URI: https://yourwebsite.com/wp-deepseek-chatbot
 * Description: A WordPress chatbot plugin that integrates with DeepSeek AI API and prioritizes internal WordPress search.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: wp-deepseek-chatbot
 * Domain Path: /languages
 * License: GPL-2.0+
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class WP_DeepSeek_Chatbot {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * Plugin directory path
     */
    public $plugin_dir;

    /**
     * Plugin directory URL
     */
    public $plugin_url;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        $this->define_constants();
        $this->includes();
        $this->init_hooks();

        add_action('init', array($this, 'init_session'));
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Define constants
     */
    private function define_constants() {
        define('WP_DEEPSEEK_CHATBOT_VERSION', self::VERSION);
        define('WP_DEEPSEEK_CHATBOT_PLUGIN_DIR', $this->plugin_dir);
        define('WP_DEEPSEEK_CHATBOT_PLUGIN_URL', $this->plugin_url);
    }

    /**
     * Include required files
     */
    private function includes() {
        // Include Settings Class
        require_once $this->plugin_dir . 'includes/class-wp-deepseek-settings.php';
        
        // Include API Class
        require_once $this->plugin_dir . 'includes/class-wp-deepseek-api.php';
        
        // Include Search Class
        // require_once $this->plugin_dir . 'includes/class-wp-deepseek-search.php';
        
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Add settings link to plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        // Add the chatbot to the footer
        add_action('wp_footer', array($this, 'render_chatbot'));
        
        // Register AJAX handlers
        add_action('wp_ajax_wp_deepseek_chatbot_request', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_wp_deepseek_chatbot_request', array($this, 'handle_chat_request'));
        add_action('wp_ajax_wp_deepseek_reset_chat', array($this, 'reset_chat'));
        add_action('wp_ajax_nopriv_wp_deepseek_reset_chat', array($this, 'reset_chat'));
    }

    private function init_session() {
        if (!session_id()) {
            session_start();
        }
        
        // Initialize conversation history if not exists
        if (!isset($_SESSION['wp_deepseek_conversation'])) {
            $_SESSION['wp_deepseek_conversation'] = array();
        }
    }
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default settings
        $default_settings = array(
            'api_key' => '',
            'website_url' => get_site_url(),
            'website_name' => get_bloginfo('name'),
            'agent_name' => __('AI Assistant', 'wp-deepseek-chatbot'),
            'primary_color' => '#4a90e2',
            'secondary_color' => '#f5f5f5',
            'font_family' => 'Arial, sans-serif',
            'custom_css' => '',
            'language' => 'id_ID',
            'custom_facts' => array(array('key' => '', 'value' => '')),
        );
        
        add_option('wp_deepseek_chatbot_settings', $default_settings);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('wp-deepseek-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Enqueue styles
        wp_enqueue_style(
            'wp-deepseek-chatbot-style',
            $this->plugin_url . 'assets/css/wp-deepseek-chatbot.css',
            array(),
            WP_DEEPSEEK_CHATBOT_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'wp-deepseek-chatbot-script',
            $this->plugin_url . 'assets/js/wp-deepseek-chatbot.js',
            array('jquery'),
            WP_DEEPSEEK_CHATBOT_VERSION,
            true
        );

        // Pass data to script
        $settings = get_option('wp_deepseek_chatbot_settings', array());
        
        wp_localize_script('wp-deepseek-chatbot-script', 'wpDeepseekChatbot', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_deepseek_chatbot_nonce'),
            'website_name' => isset($settings['website_name']) ? $settings['website_name'] : get_bloginfo('name'),
            'agent_name' => isset($settings['agent_name']) ? $settings['agent_name'] : __('AI Assistant', 'wp-deepseek-chatbot'),
            'primary_color' => isset($settings['primary_color']) ? $settings['primary_color'] : '#4a90e2',
            'strings' => array(
                'placeholder' => __('Type your message...', 'wp-deepseek-chatbot'),
                'send' => __('Send', 'wp-deepseek-chatbot'),
                'typing' => __('Typing...', 'wp-deepseek-chatbot'),
            )
        ));
    }

    /**
     * Add settings link to plugin page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wp-deepseek-chatbot-settings') . '">' . __('Settings', 'wp-deepseek-chatbot') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Render chatbot HTML
     */
    public function render_chatbot() {
        $settings = get_option('wp_deepseek_chatbot_settings', array());
        $agent_name = isset($settings['agent_name']) ? $settings['agent_name'] : __('AI Assistant', 'wp-deepseek-chatbot');
        
        // Apply custom CSS if available
        $custom_css = isset($settings['custom_css']) ? '<style>' . esc_html($settings['custom_css']) . '</style>' : '';
        
        echo $custom_css;
        ?>
        <div id="wp-deepseek-chatbot-container" class="wp-deepseek-chatbot-container">
            <div id="wp-deepseek-chatbot-toggle" class="wp-deepseek-chatbot-toggle">
                <h1>AI</h1>
            </div>
            <div id="wp-deepseek-chatbot-widget" class="wp-deepseek-chatbot-widget">
                <div class="wp-deepseek-chatbot-header">
                    <span class="wp-deepseek-chatbot-title"><?php echo esc_html($agent_name); ?></span>
                    <span class="wp-deepseek-chatbot-close">&times;</span>
                </div>
                <div class="wp-deepseek-chatbot-messages" id="wp-deepseek-chatbot-messages">
                    <div class="wp-deepseek-chatbot-message wp-deepseek-chatbot-message-bot">
                        <div class="wp-deepseek-chatbot-message-content">
                            <?php esc_html_e('Halo, apa yang bisa saya bantu?', 'wp-deepseek-chatbot'); ?>
                        </div>
                    </div>
                    <div class="wp-deepseek-chatbot-context-active">Saya akan mengingat percakapan kita</div>
                    <div class="wp-deepseek-chatbot-reset" id="wp-deepseek-reset-chat"><?php esc_html_e('Mulai percakapan baru', 'wp-deepseek-chatbot'); ?></div>
                </div>
                <div class="wp-deepseek-chatbot-input-container">
                    <input type="text" id="wp-deepseek-chatbot-input" class="wp-deepseek-chatbot-input" placeholder="<?php esc_attr_e('Type your message...', 'wp-deepseek-chatbot'); ?>">
                    <button id="wp-deepseek-chatbot-submit" class="wp-deepseek-chatbot-submit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M1.946 9.315c-.522-.174-.527-.455.01-.634l19.087-6.362c.529-.176.832.12.684.638l-5.454 19.086c-.15.529-.455.547-.679.045L12 14l6-8-8 6-8.054-2.685z"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle chat request AJAX
     */
    
     public function handle_chat_request() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_deepseek_chatbot_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-deepseek-chatbot')));
        }
    
        // Get user query
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
        if (empty($query)) {
            wp_send_json_error(array('message' => __('Please provide a valid query.', 'wp-deepseek-chatbot')));
        }
        
        // Save user message to conversation history
        $_SESSION['wp_deepseek_conversation'][] = array(
            'role' => 'user',
            'content' => $query
        );
    
        // Get conversation history (limit to last 10 messages for performance)
        $conversation_history = array_slice($_SESSION['wp_deepseek_conversation'], -10);
        
        // Initialize API class with conversation history
        $api = new WP_DeepSeek_API();
        
        // Use DeepSeek AI directly without WordPress search
        $response = $api->get_response($query, array(), $conversation_history);
        
        // Save bot response to conversation history
        $_SESSION['wp_deepseek_conversation'][] = array(
            'role' => 'assistant',
            'content' => $response
        );
        
        wp_send_json_success(array(
            'message' => $response,
            'source' => 'ai',
        ));
    }
    
public function reset_chat() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_deepseek_chatbot_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'wp-deepseek-chatbot')));
    }
    
    // Reset conversation history
    $_SESSION['wp_deepseek_conversation'] = array();
    
    wp_send_json_success(array(
        'message' => __('Conversation history has been reset.', 'wp-deepseek-chatbot')
    ));
}
}

// Initialize the plugin
function wp_deepseek_chatbot_init() {
    return WP_DeepSeek_Chatbot::get_instance();
}

// Start the plugin
wp_deepseek_chatbot_init();
