<?php
/**
 * WP DeepSeek API Integration - Optimized
 *
 * @package WP_DeepSeek_Chatbot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * API Integration class
 */
class WP_DeepSeek_API {

    const API_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';
    const MODEL_NAME = 'qwen/qwen-2-7b-instruct:free';

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

        $system_message = $this->prepare_system_message($context);
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
            $start_index = ($conversation_history[0]['role'] === 'system') ? 1 : 0;
            
            for ($i = $start_index; $i < count($conversation_history); $i++) {
                $messages[] = $conversation_history[$i];
            }
        } 
        
        // Add current query if not already in history
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
            'frequency_penalty' => 0.5
        );

        // Make API request
        $response = wp_remote_post(self::API_ENDPOINT, array(
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
        ));

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
        
        // Prepare URL links text
        $url_links_text = $this->prepare_url_links_text($settings);
        
        // Prepare contacts text
        $official_contacts_text = $this->prepare_contacts_text($settings);
        
        // Prepare custom facts text
        $custom_facts_text = $this->prepare_custom_facts_text($settings);
        
        // Prepare knowledge base text
        $knowledge_base_text = $this->prepare_knowledge_base_text($settings);
        
        // Combine everything into a single, efficient system message
        $system_message = sprintf(
            __('Anda adalah %1$s, asisten AI cerdas untuk dealer mobil %2$s. Tugas Anda adalah membantu pelanggan menemukan mobil yang sesuai dengan kebutuhan mereka, memberikan informasi tentang spesifikasi kendaraan, harga, promo, serta membantu dalam proses pembelian dan layanan purna jual. Jawablah dalam bahasa Indonesia secara default, kecuali pengguna bertanya dalam bahasa lain.  
        
**Fungsi Utama Anda:**  
- **Rekomendasi Mobil**: Tanyakan kebutuhan pelanggan (misalnya, mobil keluarga, mobil hemat BBM, mobil sport, dll.), lalu berikan saran terbaik.  
- **Informasi Produk**: Jelaskan spesifikasi, fitur, harga, serta perbandingan antar model dengan objektif.  
- **Promo & Pembiayaan**: Informasikan tentang diskon, kredit, leasing, dan program tukar tambah yang sedang berlaku.  
- **Test Drive & Pemesanan**: Bantu pelanggan menjadwalkan test drive dengan hubungkan mereka dengan tim sales.  
- **Layanan Purna Jual**: Berikan informasi tentang servis berkala, garansi, dan ketersediaan suku cadang.  

**Instruksi Penting:**  
1. **Utamakan informasi resmi dari dealer ini** dalam menjawab pertanyaan terkait produk dan layanan.  
2. **Jika tersedia, sertakan URL yang relevan** untuk informasi lebih lanjut.
3. **Jika pelanggan membutuhkan bantuan lebih lanjut, arahkan mereka ke kontak resmi dealer**.
4. **Gunakan bahasa yang ramah, profesional, dan jelas**, serta hindari memberikan informasi yang tidak relevan atau spekulatif.  
5. **Jika ada pertanyaan di luar cakupan dealer mobil**, beri tahu dengan sopan bahwa Anda hanya dapat membantu dalam topik terkait mobil, pembelian, dan layanan dealer.  

**Batasan Jawaban:**  
- Pastikan respons tidak melebihi 750 token. Berikan jawaban yang ringkas, informatif, dan langsung ke poin utama.

%3$s

%4$s

%5$s

%6$s', 'wp-deepseek-chatbot'),
            $agent_name,
            $website_name,
            $url_links_text,        // URL links penting
            $official_contacts_text, // Kontak resmi
            $custom_facts_text,     // Informasi dealer
            $knowledge_base_text    // FAQ
        );
    
    
        return $system_message;
    }
    
    /**
     * Prepare URL links text
     * 
     * @param array $settings Plugin settings
     * @return string Formatted URL links text
     */
    private function prepare_url_links_text($settings) {
        $url_links_text = '';
        $url_links = isset($settings['url_links']) ? $settings['url_links'] : '';

        if (!empty($url_links)) {
            $url_links_text = "\n\n**URL Links Penting:**\n";
            
            // If url_links is a string (new format), use directly
            if (is_string($url_links)) {
                $url_links = str_replace(['[', ']'], '', $url_links);
                $lines = explode("\n", $url_links);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
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
        
        return $url_links_text;
    }
    
    /**
     * Prepare contacts text
     * 
     * @param array $settings Plugin settings
     * @return string Formatted contacts text
     */
    private function prepare_contacts_text($settings) {
        $official_contacts_text = '';
        $official_contacts = isset($settings['official_contacts']) ? $settings['official_contacts'] : array();
        
        if (!empty($official_contacts)) {
            $official_contacts_text = "\n\n**Kontak Resmi Dealer:**\n";
            foreach ($official_contacts as $contact) {
                if (!empty($contact['name']) && !empty($contact['value'])) {
                    $official_contacts_text .= "- " . $contact['name'] . ": " . $contact['value'] . "\n";
                }
            }
        }
        
        return $official_contacts_text;
    }
    
    /**
     * Prepare custom facts text
     * 
     * @param array $settings Plugin settings
     * @return string Formatted custom facts text
     */
    private function prepare_custom_facts_text($settings) {
        $custom_facts_text = '';
        $custom_facts = isset($settings['custom_facts']) ? $settings['custom_facts'] : array();
        
        if (!empty($custom_facts)) {
            $custom_facts_text = "\n\n**Informasi Dealer Kami:**\n";
            foreach ($custom_facts as $fact) {
                if (!empty($fact['key']) && !empty($fact['value'])) {
                    $custom_facts_text .= "- " . $fact['key'] . ": " . $fact['value'] . "\n";
                }
            }
        }
        
        return $custom_facts_text;
    }
    
    /**
     * Prepare knowledge base text
     * 
     * @param array $settings Plugin settings
     * @return string Formatted knowledge base text
     */
    private function prepare_knowledge_base_text($settings) {
        $knowledge_base_text = '';
        $knowledge_base = isset($settings['knowledge_base']) ? $settings['knowledge_base'] : array();
        
        if (!empty($knowledge_base)) {
            $knowledge_base_text = "\n\n**Pertanyaan yang Sering Diajukan:**\n";
            foreach ($knowledge_base as $item) {
                if (!empty($item['question']) && !empty($item['answer'])) {
                    $knowledge_base_text .= "\n- **Q: " . $item['question'] . "**\n  A: " . wp_strip_all_tags($item['answer']) . "\n";
                }
            }
        }
        
        return $knowledge_base_text;
    }
}