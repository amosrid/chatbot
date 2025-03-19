<?php
/**
 * WP DeepSeek Search
 *
 * @package WP_DeepSeek_Chatbot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search class
 */
class WP_DeepSeek_Search {

    /**
     * Search WordPress content
     *
     * @param string $query Search query
     * @return array Search results
     */
    public function search($query) {
        // Initialize results array
        $results = array();
        
        // First, check for structured data (FAQs, schema markup)
        $structured_results = $this->search_structured_data($query);
        if (!empty($structured_results)) {
            $results = array_merge($results, $structured_results);
        }
        
        // Check for specific keywords related to promotions
        if ($this->is_promo_related_query($query)) {
            // This is a promotion-related query, prioritize promotion search
            $promo_results = $this->search_promotions($query);
            if (!empty($promo_results)) {
                $results = array_merge($results, $promo_results);
                // If we have good promo results, return immediately
                if (count($promo_results) >= 1) {
                    return array_slice($results, 0, 5);
                }
            }
        }
        
        // Next, check for time-sensitive content (promotions, events)
        $time_sensitive_results = $this->search_time_sensitive_content($query);
        if (!empty($time_sensitive_results)) {
            $results = array_merge($results, $time_sensitive_results);
        }
        
        // If not enough results, search in regular content
        if (count($results) < 3) {
            $post_results = $this->search_posts_and_pages($query);
            if (!empty($post_results)) {
                $results = array_merge($results, $post_results);
            }
        }
        
        // If still not enough results, search in comments
        if (count($results) < 3) {
            $comment_results = $this->search_comments($query);
            if (!empty($comment_results)) {
                $results = array_merge($results, $comment_results);
            }
        }
        
        // Return up to 5 most relevant results
        return array_slice($results, 0, 5);
    }

    /**
     * Check if query is promotion-related
     *
     * @param string $query
     * @return bool
     */
    private function is_promo_related_query($query) {
        $query = strtolower($query);
        $promo_keywords = [
            'promo', 'promosi', 'diskon', 'discount', 'offer',
            'penawaran', 'harga', 'price', 'spesial', 'special',
            'sale', 'discount', 'cashback', 'voucher', 'hadiah',
            'gift', 'bonus', 'gratis', 'free', 'hemat', 'save',
            'berlaku', 'valid', 'batas', 'limit', 'kupon', 'coupon',
            'potongan', 'cut', 'harga khusus', 'special price',
        ];

        foreach ($promo_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Search specifically for promotions
     *
     * @param string $query
     * @return array
     */
    private function search_promotions($query) {
        $results = array();
        $current_date = current_time('Y-m-d');

        // First search method: look for posts with "promo" in the title, URL, or category
        $promo_args = array(
            'post_type' => array('post', 'page', 'product', 'any'),
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                // Standard date meta keys
                array(
                    'relation' => 'AND',
                    array(
                        'key' => '_promotion_end_date',
                        'value' => $current_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_promotion_start_date',
                        'value' => $current_date,
                        'compare' => '<=',
                        'type' => 'DATE'
                    )
                ),
                // Alternative date meta keys
                array(
                    'relation' => 'AND',
                    array(
                        'key' => 'promo_end_date',
                        'value' => $current_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'promo_start_date',
                        'value' => $current_date,
                        'compare' => '<=',
                        'type' => 'DATE'
                    )
                ),
                // Any meta key with 'promo' in it
                array(
                    'key' => '_promo_details',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => 'promo_details',
                    'compare' => 'EXISTS',
                )
            ),
            'tax_query' => array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => array('promo', 'promotion', 'promosi', 'offer', 'special', 'deal')
                ),
                array(
                    'taxonomy' => 'post_tag',
                    'field' => 'slug',
                    'terms' => array('promo', 'promotion', 'promosi', 'offer', 'special', 'deal')
                )
            ),
            's' => $query,
        );

        $promo_query = new WP_Query($promo_args);
        
        if ($promo_query->have_posts()) {
            while ($promo_query->have_posts()) {
                $promo_query->the_post();
                
                $start_date = '';
                $end_date = '';
                
                // Try to get dates from various meta keys
                $meta_keys = array(
                    '_promotion_start_date', 'promo_start_date', 'start_date', 'valid_from',
                    'promotion_start', 'promo_start', 'sale_start_date'
                );
                foreach ($meta_keys as $key) {
                    $value = get_post_meta(get_the_ID(), $key, true);
                    if (!empty($value)) {
                        $start_date = $value;
                        break;
                    }
                }
                
                $meta_keys = array(
                    '_promotion_end_date', 'promo_end_date', 'end_date', 'valid_until',
                    'promotion_end', 'promo_end', 'sale_end_date', 'expiry_date'
                );
                foreach ($meta_keys as $key) {
                    $value = get_post_meta(get_the_ID(), $key, true);
                    if (!empty($value)) {
                        $end_date = $value;
                        break;
                    }
                }
                
                $content = get_the_content();
                $excerpt = get_the_excerpt();
                
                // If we don't have dates from meta, try to extract from content with regex
                if (empty($start_date) || empty($end_date)) {
                    // Regex patterns for date extraction
                    $date_patterns = array(
                        '/valid(?:\s+from|\s+since)?\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})(?:\s+(?:to|until|hingga|sampai)\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}))?/i',
                        '/berlaku(?:\s+mulai|\s+dari)?\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})(?:\s+(?:hingga|sampai)\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}))?/i',
                        '/(?:promo|promosi)(?:\s+berlaku)?\s+(?:dari|mulai)\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})(?:\s+(?:hingga|sampai)\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}))?/i',
                        '/(?:mulai|dari)\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})(?:\s+(?:hingga|sampai)\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}))/i',
                    );
                    
                    foreach ($date_patterns as $pattern) {
                        if (preg_match($pattern, $content, $matches) || preg_match($pattern, $excerpt, $matches)) {
                            if (isset($matches[1])) {
                                $start_date = $matches[1];
                            }
                            if (isset($matches[2])) {
                                $end_date = $matches[2];
                            }
                            break;
                        }
                    }
                }
                
                // Create the date validity string if we have dates
                $date_validity = '';
                if (!empty($start_date) && !empty($end_date)) {
                    $date_validity = sprintf(' (Berlaku dari %s hingga %s)', $start_date, $end_date);
                } elseif (!empty($end_date)) {
                    $date_validity = sprintf(' (Berlaku hingga %s)', $end_date);
                } elseif (!empty($start_date)) {
                    $date_validity = sprintf(' (Berlaku mulai %s)', $start_date);
                }
                
                // Extract relevant promo content
                $promo_content = $content;
                // If content is too long, try to extract just the promo details
                if (strlen($promo_content) > 300) {
                    $promo_details = $this->extract_promo_details($content);
                    if (!empty($promo_details)) {
                        $promo_content = $promo_details;
                    } else {
                        // If no specific details found, use excerpt + first 200 chars
                        $promo_content = $excerpt . ' ' . substr(wp_strip_all_tags($content), 0, 200) . '...';
                    }
                }
                
                $results[] = array(
                    'title' => get_the_title(),
                    'content' => wp_strip_all_tags($promo_content) . $date_validity,
                    'url' => get_permalink(),
                    'type' => 'promotion',
                    'relevance' => 10, // Highest relevance for promotions
                    'meta' => array(
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    )
                );
            }
            wp_reset_postdata();
        }
        
        // If no results, try a wider search for promo-related content
        if (empty($results)) {
            $promo_keywords = array('promo', 'promosi', 'diskon', 'discount', 'offer', 'penawaran', 'special');
            $wider_args = array(
                'post_type' => array('post', 'page', 'product'),
                'posts_per_page' => 5,
                's' => implode(' OR ', $promo_keywords),
                'post_status' => 'publish',
            );
            
            $wider_query = new WP_Query($wider_args);
            
            if ($wider_query->have_posts()) {
                while ($wider_query->have_posts()) {
                    $wider_query->the_post();
                    
                    // Check if the content seems promotion-related
                    $content = get_the_content();
                    $relevance = 0;
                    
                    foreach ($promo_keywords as $keyword) {
                        $relevance += substr_count(strtolower($content), strtolower($keyword));
                    }
                    
                    if ($relevance > 0) {
                        // Extract short promotion details
                        $promo_details = $this->extract_promo_details($content);
                        if (empty($promo_details)) {
                            $promo_details = get_the_excerpt();
                        }
                        
                        $results[] = array(
                            'title' => get_the_title(),
                            'content' => wp_strip_all_tags($promo_details),
                            'url' => get_permalink(),
                            'type' => 'promotion',
                            'relevance' => 5, // Lower relevance for general promo content
                        );
                    }
                }
                wp_reset_postdata();
            }
        }
        
        // Check if we need to search menu / footer for promo info
        if (empty($results) || count($results) < 2) {
            // Also search for promotions in menu items and widget areas
            $nav_menu_locations = get_nav_menu_locations();
            foreach ($nav_menu_locations as $location => $menu_id) {
                if (strpos(strtolower($location), 'promo') !== false) {
                    $menu_items = wp_get_nav_menu_items($menu_id);
                    if ($menu_items) {
                        foreach ($menu_items as $item) {
                            if (strpos(strtolower($item->title), 'promo') !== false || 
                                strpos(strtolower($item->title), 'diskon') !== false) {
                                $results[] = array(
                                    'title' => $item->title,
                                    'content' => __('Informasi promo tersedia di halaman ini', 'wp-deepseek-chatbot'),
                                    'url' => $item->url,
                                    'type' => 'menu_item',
                                    'relevance' => 4,
                                );
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }
    
    /**
     * Extract promotion details from content
     *
     * @param string $content
     * @return string
     */
    private function extract_promo_details($content) {
        $details = '';
        
        // Look for lists which often contain promo details
        if (preg_match('/<[uo]l>(.+?)<\/[uo]l>/is', $content, $matches)) {
            $details .= $matches[1];
        }
        
        // Look for paragraphs with promo keywords
        $promo_indicators = array(
            'diskon', 'potongan', 'harga', 'promo', 'spesial', 
            'offer', 'bonus', 'gratis', 'free', 'discount'
        );
        
        $paragraphs = explode('</p>', $content);
        foreach ($paragraphs as $paragraph) {
            $paragraph = wp_strip_all_tags($paragraph);
            foreach ($promo_indicators as $indicator) {
                if (stripos($paragraph, $indicator) !== false) {
                    $details .= ' ' . $paragraph;
                    break;
                }
            }
        }
        
        return wp_strip_all_tags($details);
    }

    /**
     * Search structured data
     *
     * @param string $query Search query
     * @return array Search results from structured data
     */
    private function search_structured_data($query) {
        $results = array();
        
        // Search for FAQ blocks in content
        $faq_args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 10,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_page_template',
                    'value' => 'faq',
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_wp_page_template',
                    'value' => 'template-faq',
                    'compare' => 'LIKE',
                ),
            ),
        );
        
        $faq_query = new WP_Query($faq_args);
        
        if ($faq_query->have_posts()) {
            while ($faq_query->have_posts()) {
                $faq_query->the_post();
                
                // Check if content contains FAQ schema or blocks
                $content = get_the_content();
                if (strpos($content, 'wp:yoast/faq-block') !== false || 
                    strpos($content, 'schema.org/FAQPage') !== false ||
                    strpos($content, 'itemtype="https://schema.org/FAQPage"') !== false) {
                    
                    $results[] = array(
                        'title' => get_the_title(),
                        'content' => wp_strip_all_tags($content),
                        'url' => get_permalink(),
                        'type' => 'faq',
                        'relevance' => 10, // Higher relevance for FAQs
                    );
                }
            }
            wp_reset_postdata();
        }
        
        return $results;
    }

    /**
     * Search time-sensitive content
     *
     * @param string $query Search query
     * @return array Search results from time-sensitive content
     */
    private function search_time_sensitive_content($query) {
        $results = array();
        $current_date = current_time('Y-m-d');
        
        // Keywords related to time-sensitive queries
        $time_keywords = array(
            'promotion', 'promosi', 'diskon', 'discount', 'sale', 'event', 'acara',
            'promo', 'offer', 'penawaran', 'special', 'spesial', 'limited', 'terbatas'
        );
        
        $has_time_keyword = false;
        foreach ($time_keywords as $keyword) {
            if (stripos($query, $keyword) !== false) {
                $has_time_keyword = true;
                break;
            }
        }
        
        if ($has_time_keyword) {
            // Search for posts with time-sensitive meta
            $time_args = array(
                'post_type' => array('post', 'page', 'product'), // Include WooCommerce products if available
                'post_status' => 'publish',
                's' => $query,
                'posts_per_page' => 5,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_promotion_end_date',
                        'value' => $current_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_promotion_start_date',
                        'value' => $current_date,
                        'compare' => '<=',
                        'type' => 'DATE'
                    )
                ),
            );
            
            $time_query = new WP_Query($time_args);
            
            if ($time_query->have_posts()) {
                while ($time_query->have_posts()) {
                    $time_query->the_post();
                    
                    $start_date = get_post_meta(get_the_ID(), '_promotion_start_date', true);
                    $end_date = get_post_meta(get_the_ID(), '_promotion_end_date', true);
                    
                    $results[] = array(
                        'title' => get_the_title(),
                        'content' => wp_strip_all_tags(get_the_content()) . sprintf(
                            __(' (Valid from %1$s to %2$s)', 'wp-deepseek-chatbot'),
                            $start_date,
                            $end_date
                        ),
                        'url' => get_permalink(),
                        'type' => 'promotion',
                        'relevance' => 8,
                        'meta' => array(
                            'start_date' => $start_date,
                            'end_date' => $end_date
                        )
                    );
                }
                wp_reset_postdata();
            }
        }
        
        return $results;
    }

    /**
     * Search posts and pages
     *
     * @param string $query Search query
     * @return array Search results from posts and pages
     */
    private function search_posts_and_pages($query) {
        $results = array();
        
        // Search in posts and pages
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 5,
        );
        
        $search_query = new WP_Query($args);
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                
                // Calculate relevance based on keyword occurrence
                $content = get_the_content();
                $title = get_the_title();
                $excerpt = get_the_excerpt();
                
                $relevance = 0;
                $relevance += substr_count(strtolower($title), strtolower($query)) * 3;
                $relevance += substr_count(strtolower($excerpt), strtolower($query)) * 2;
                $relevance += substr_count(strtolower($content), strtolower($query));
                
                $results[] = array(
                    'title' => $title,
                    'content' => wp_strip_all_tags($excerpt . ' ' . $content),
                    'url' => get_permalink(),
                    'type' => get_post_type(),
                    'relevance' => $relevance,
                );
            }
            wp_reset_postdata();
        }
        
        // Sort results by relevance
        usort($results, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return $results;
    }

    /**
     * Search comments
     *
     * @param string $query Search query
     * @return array Search results from comments
     */
    private function search_comments($query) {
        $results = array();
        
        // Search in comments
        $comment_args = array(
            'search' => $query,
            'status' => 'approve',
            'number' => 3,
        );
        
        $comments = get_comments($comment_args);
        
        foreach ($comments as $comment) {
            $post = get_post($comment->comment_post_ID);
            
            // Only include comments from published posts
            if ($post && $post->post_status === 'publish') {
                $results[] = array(
                    'title' => get_the_title($comment->comment_post_ID),
                    'content' => wp_strip_all_tags($comment->comment_content),
                    'url' => get_comment_link($comment),
                    'type' => 'comment',
                    'relevance' => 3,
                );
            }
        }
        
        return $results;
    }
}