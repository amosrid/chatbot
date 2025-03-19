<?php
/**
 * WP DeepSeek API Integration
 *
 * @package WP_DeepSeek_Chatbot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Integration class
 */
class WP_DeepSeek_API {

    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Model name
     */
    const MODEL_NAME = 'qwen/qwen-vl-plus:free';

    

/**
 * Get response from DeepSeek AI
 *
 * @param string $query User query
 * @param array $context Context from WordPress search (optional)
 * @param array $conversation_history Previous messages (optional)
 * @return string Response from DeepSeek AI
 */
public function get_response($query, $context = array(), $conversation_history = array()) {
    $settings = get_option('wp_deepseek_chatbot_settings', array());
    $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
    $website_url = isset($settings['website_url']) ? $settings['website_url'] : get_site_url();
    $website_name = isset($settings['website_name']) ? $settings['website_name'] : get_bloginfo('name');

    if (empty($api_key)) {
        return __('Error: API key is not configured. Please contact the administrator.', 'wp-deepseek-chatbot');
    }

    // Prepare system message with context if available
    $system_message = $this->prepare_system_message($context);
    
    // Prepare messages array
    $messages = array();
    
    // Add system message if available
    if (!empty($system_message)) {
        $messages[] = array(
            'role' => 'system',
            'content' => $system_message
        );
    }
    
    // Add conversation history if available
    if (!empty($conversation_history)) {
        // Skip the system message if it exists in history
        $start_index = ($conversation_history[0]['role'] === 'system') ? 1 : 0;
        
        // Add previous messages
        for ($i = $start_index; $i < count($conversation_history); $i++) {
            $messages[] = $conversation_history[$i];
        }
    } else {
        // Add user message if no history
        $messages[] = array(
            'role' => 'user',
            'content' => $query
        );
    }
    
    // If current query is not already in conversation history
    if (empty($conversation_history) || $conversation_history[count($conversation_history) - 1]['content'] !== $query) {
        $messages[] = array(
            'role' => 'user',
            'content' => $query
        );
    }

        
        // Prepare API request data
        $data = array(
            'model' => self::MODEL_NAME,
            'messages' => $messages,
            'max_tokens' => 800,
            'temperature' => 0.7,
            'frequency_penalty' => 0.5  // Add this to reduce repetition
        );

        // Prepare API request
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'HTTP-Referer' => $website_url,
                'X-Title' => $website_name,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data),
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => true,
            'httpversion' => '1.1',
            'sslverify' => true
        );

        // Make API request
        $response = wp_remote_post(self::API_ENDPOINT, $args);

        // Check for errors
        if (is_wp_error($response)) {
            return __('Error: Unable to connect to DeepSeek AI API. Please try again later.', 'wp-deepseek-chatbot');
        }

        // Get response body
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check for API errors
        if (isset($data['error'])) {
            return sprintf(
                __('Error: %s', 'wp-deepseek-chatbot'),
                isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'wp-deepseek-chatbot')
            );
        }

        // Extract response content
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        return __('Error: Unable to get a response from DeepSeek AI.', 'wp-deepseek-chatbot');
    }

    /**
     * Prepare system message with context
     *
     * @param array $context Context from WordPress search
     * @return string System message
     */

     private function prepare_system_message($context = array()) {
        $settings = get_option('wp_deepseek_chatbot_settings', array());
        $website_name = isset($settings['website_name']) ? $settings['website_name'] : get_bloginfo('name');
        $agent_name = isset($settings['agent_name']) ? $settings['agent_name'] : __('AI Assistant', 'wp-deepseek-chatbot');
        
        // Get URL links and contacts from settings
        $url_links = isset($settings['url_links']) ? $settings['url_links'] : array();
        $official_contacts = isset($settings['official_contacts']) ? $settings['official_contacts'] : array();
        
        // Format URL links if available
        $url_links_text = '';
$url_links = isset($settings['url_links']) ? $settings['url_links'] : '';

if (!empty($url_links)) {
    $url_links_text = "\n\nURL links penting:\n";
    
    // If url_links is a string (new format), use directly
    if (is_string($url_links)) {
        // Remove any square brackets if present
        $url_links = str_replace(['[', ']'], '', $url_links);
        // Split by lines and format each line
        $lines = explode("\n", $url_links);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Make sure the line starts with a dash
                if (strpos($line, '-') !== 0) {
                    $line = '- ' . $line;
                }
                $url_links_text .= $line . "\n";
            }
        }
    } 
    // For backward compatibility with the old array format
    else if (is_array($url_links)) {
        foreach ($url_links as $link) {
            if (!empty($link['label']) && !empty($link['url'])) {
                $url_links_text .= "- " . $link['label'] . ": " . $link['url'] . "\n";
            }
        }
    }
}
        
        // Format official contacts if available
        $official_contacts_text = '';
        if (!empty($official_contacts)) {
            $official_contacts_text = "\n\nKontak resmi:\n";
            foreach ($official_contacts as $contact) {
                if (!empty($contact['name']) && !empty($contact['value'])) {
                    $official_contacts_text .= "- " . $contact['name'] . ": " . $contact['value'] . "\n";
                }
            }
        }
    
        // Default system message with URL links and contacts included
        $system_message = sprintf(
            __('Anda adalah %1$s, asisten AI pintar untuk dealer mobil %2$s. Tugas Anda adalah membantu pelanggan dalam mencari mobil yang sesuai dengan kebutuhan mereka, memberikan informasi tentang spesifikasi kendaraan, harga, promo yang sedang berlangsung, serta membantu dalam proses pembelian dan layanan purna jual. Jawablah dalam bahasa Indonesia secara default, kecuali pengguna bertanya dalam bahasa lain.
        
            **Fungsi utama Anda:**  
            - **Rekomendasi Mobil**: Tanyakan kebutuhan pelanggan (misalnya, mobil keluarga, mobil hemat BBM, mobil sport, dll.), lalu berikan saran terbaik.  
            - **Informasi Produk**: Jelaskan spesifikasi, fitur, harga, dan perbedaan antar model.  
            - **Promo & Pembiayaan**: Berikan informasi tentang diskon, kredit, leasing, dan program tukar tambah.  
            - **Test Drive & Pemesanan**: Bantu pelanggan menjadwalkan test drive atau menghubungkan mereka dengan tim sales.  
            - **Layanan Purna Jual**: Informasikan tentang servis berkala, garansi, dan ketersediaan suku cadang.  
        
            **Instruksi Penting:**  
            1. **Selalu prioritaskan fakta dari dealer ini** dalam menjawab pertanyaan seputar produk dan layanan kami.  
            2. **Jika tersedia, sertakan URL relevan** untuk informasi lebih lanjut. Berikut daftar URL yang bisa Anda gunakan dalam jawaban Anda: [%3$s].  
            3. **Jika pengguna membutuhkan bantuan lebih lanjut, arahkan ke kontak resmi dealer**: [%4$s].  
            4. **Jawablah dengan bahasa yang ramah, profesional, dan jelas**, serta hindari memberikan informasi yang tidak relevan atau spekulatif.  
            5. **Jika pengguna bertanya sesuatu di luar cakupan dealer mobil**, beri tahu mereka dengan sopan bahwa Anda hanya bisa membantu dalam topik terkait mobil, pembelian, dan layanan dealer.  
        
            ', 'wp-deepseek-chatbot'),
            $agent_name,
            $website_name,
            $url_links_text,  // Daftar URL yang relevan
            $official_contacts_text  // Daftar kontak dealer
        );
        
    
        // Tambahkan fakta dealer jika tersedia
        $custom_facts = isset($settings['custom_facts']) ? $settings['custom_facts'] : array();
        if (!empty($custom_facts)) {
            $system_message .= "\n\n" . __('Informasi dealer kami:', 'wp-deepseek-chatbot');
            foreach ($custom_facts as $fact) {
                if (!empty($fact['key']) && !empty($fact['value'])) {
                    $system_message .= "\n• " . $fact['key'] . ": " . $fact['value'];
                }
            }
        }
    
        // Tambahkan FAQ jika tersedia
        $knowledge_base = isset($settings['knowledge_base']) ? $settings['knowledge_base'] : array();
        if (!empty($knowledge_base)) {
            $system_message .= "\n\n" . __('Pertanyaan yang sering diajukan:', 'wp-deepseek-chatbot');
            foreach ($knowledge_base as $item) {
                if (!empty($item['question']) && !empty($item['answer'])) {
                    $system_message .= "\n\n**Pertanyaan:** " . $item['question'];
                    $system_message .= "\n**Jawaban:** " . wp_strip_all_tags($item['answer']);
                }
            }
        }
    
        // Tambahkan konteks pencarian jika ada
        if (!empty($context)) {
            $system_message .= "\n\n" . __('Berikut informasi terkait dari website yang mungkin relevan:', 'wp-deepseek-chatbot');
            foreach ($context as $item) {
                $system_message .= "\n\n" . $item['title'] . ": " . $item['content'];
            }
        }
    
        return $system_message;
    }
    

}