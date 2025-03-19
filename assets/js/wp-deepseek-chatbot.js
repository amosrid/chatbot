/**
 * WP DeepSeek Chatbot Frontend Script
 */

(function($) {
    'use strict';

    // Variables
    var chatbotContainer = $('#wp-deepseek-chatbot-container');
    var chatbotToggle = $('#wp-deepseek-chatbot-toggle');
    var chatbotWidget = $('#wp-deepseek-chatbot-widget');
    var chatbotClose = $('.wp-deepseek-chatbot-close');
    var chatbotMessages = $('#wp-deepseek-chatbot-messages');
    var chatbotInput = $('#wp-deepseek-chatbot-input');
    var chatbotSubmit = $('#wp-deepseek-chatbot-submit');
    var isProcessing = false;

    // Error handling function
    function handleError(errorMessage, details) {
        console.error('WP DeepSeek Chatbot Error:', errorMessage, details || '');
        return 'Sorry, error: ' + errorMessage;
    }

    // Apply theme colors from settings
    function applyThemeColors() {
        try {
            document.documentElement.style.setProperty('--wp-deepseek-primary-color', wpDeepseekChatbot.primary_color);
            document.documentElement.style.setProperty('--wp-deepseek-primary-color-dark', adjustColor(wpDeepseekChatbot.primary_color, -20));
            document.documentElement.style.setProperty('--wp-deepseek-font-family', wpDeepseekChatbot.font_family || 'Arial, sans-serif');
        } catch (error) {
            console.error('Error applying theme colors:', error);
        }
    }

    // Adjust color brightness (for hover states)
    function adjustColor(color, percent) {
        var R = parseInt(color.substring(1, 3), 16);
        var G = parseInt(color.substring(3, 5), 16);
        var B = parseInt(color.substring(5, 7), 16);

        R = parseInt(R * (100 + percent) / 100);
        G = parseInt(G * (100 + percent) / 100);
        B = parseInt(B * (100 + percent) / 100);

        R = (R < 255) ? R : 255;
        G = (G < 255) ? G : 255;
        B = (B < 255) ? B : 255;

        R = Math.round(R);
        G = Math.round(G);
        B = Math.round(B);

        var RR = ((R.toString(16).length === 1) ? "0" + R.toString(16) : R.toString(16));
        var GG = ((G.toString(16).length === 1) ? "0" + G.toString(16) : G.toString(16));
        var BB = ((B.toString(16).length === 1) ? "0" + B.toString(16) : B.toString(16));

        return "#" + RR + GG + BB;
    }

    // Toggle chatbot widget
    function toggleChatbot() {
        chatbotWidget.toggleClass('active');
        
        if (chatbotWidget.hasClass('active')) {
            chatbotInput.focus();
            
            // Add conversation starters if they don't exist yet
            if ($('.wp-deepseek-chatbot-starters').length === 0) {
                addConversationStarters();
            }
        }
    }

    // Add a message to the chat
    function addMessage(message, isUser) {
        var messageClass = isUser ? 'wp-deepseek-chatbot-message-user' : 'wp-deepseek-chatbot-message-bot';
        
        // Parse markdown in messages (convert ** text ** to <strong>text</strong>)
        if (!isUser) {
            message = parseMarkdown(message);
        }
        
        var messageHTML = '<div class="wp-deepseek-chatbot-message ' + messageClass + '">' +
            '<div class="wp-deepseek-chatbot-message-content">' + message + '</div>' +
            '</div>';
        
        chatbotMessages.append(messageHTML);
        scrollToBottom();
    }
    
    function parseMarkdown(text) {
        // Replace line breaks with <br>
        text = text.replace(/\n/g, '<br>');
        
        // Headers (## Header -> <h2>Header</h2>)
        text = text.replace(/## (.*?)(<br>|$)/g, '<h2>$1</h2>');
        text = text.replace(/### (.*?)(<br>|$)/g, '<h3>$1</h3>');
        
        // Bold text
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Italic text
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Bullet lists
        text = text.replace(/- (.*?)(<br>|$)/g, '<span class="wp-deepseek-list-item">• $1</span>$2');
        
        // Numbered lists - UPDATED REGEX to handle multi-line items better
        text = text.replace(/^(\d+)\.\s+(.*?)$/gm, function(match, listNumber, content) {
            // Only replace if it's at the beginning of a line and followed by space
            return '<span class="wp-deepseek-list-item"><span class="wp-deepseek-list-number">' + listNumber + '.</span> ' + content + '</span><br>';
        });
        
        // Horizontal rule
        text = text.replace(/---+(<br>|$)/g, '<hr>$1');
        
        // Convert URLs to clickable links
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        
        return text;
    }

    // Add typing indicator
    function addTypingIndicator() {
        var typingHTML = '<div class="wp-deepseek-chatbot-typing" id="wp-deepseek-chatbot-typing">' +
            '<span class="wp-deepseek-chatbot-typing-dot"></span>' +
            '<span class="wp-deepseek-chatbot-typing-dot"></span>' +
            '<span class="wp-deepseek-chatbot-typing-dot"></span>' +
            '</div>';
        
        chatbotMessages.append(typingHTML);
        scrollToBottom();
    }

    // Remove typing indicator
    function removeTypingIndicator() {
        $('#wp-deepseek-chatbot-typing').remove();
    }

    // Scroll messages to bottom
    function scrollToBottom() {
        chatbotMessages.scrollTop(chatbotMessages[0].scrollHeight);
    }

    // Send message to server
    function sendMessage() {
        var message = chatbotInput.val().trim();
        
        if (message === '' || isProcessing) {
            return;
        }
        
        // Clear input
        chatbotInput.val('');
        
        // Add user message to chat
        addMessage(message, true);
        
        // Show typing indicator
        addTypingIndicator();
        
        // Set processing flag
        isProcessing = true;
        
        // Send request to server
        $.ajax({
            url: wpDeepseekChatbot.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_deepseek_chatbot_request',
                nonce: wpDeepseekChatbot.nonce,
                query: message
            },
            success: function(response) {
                // Remove typing indicator
                removeTypingIndicator();
                
                if (response.success && response.data.message) {
                    // Add bot response to chat
                    addMessage(response.data.message, false);
                } else {
                    // Log specific error from response
                    var errorMsg = response.data && response.data.error ? response.data.error : 'Unknown error occurred';
                    console.error('API Response Error:', errorMsg);
                    
                    // Add error message
                    addMessage('Sorry, I encountered an error. Please try again later. (Error: ' + errorMsg + ')', false);
                }
            },
            error: function(xhr, status, error) {
                // Remove typing indicator
                removeTypingIndicator();
                
                // Log detailed error information
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                // Add error message
                addMessage('Sorry, I encountered a connection error: ' + status + '. Please check your internet connection and try again.', false);
            },
            complete: function() {
                // Reset processing flag
                isProcessing = false;
            }
        });
    }

    // Add conversation starters
    function addConversationStarters() {
        $.ajax({
            url: wpDeepseekChatbot.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_deepseek_chatbot_get_starters',
                nonce: wpDeepseekChatbot.nonce
            },
            success: function(response) {
                if (response.success && response.data.starters && response.data.starters.length > 0) {
                    // Rest of code remains the same...
                } else {
                    console.warn('No conversation starters found or invalid response format');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading conversation starters:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
            }
        });
    }

    // Initialize
    function init() {
        try {
            // Apply theme colors
            applyThemeColors();
            
            // Add missing event listeners
            chatbotToggle.on('click', function(e) {
                e.preventDefault();
                toggleChatbot();
            });
            
            chatbotClose.on('click', function(e) {
                e.preventDefault();
                chatbotWidget.removeClass('active');
            });
            
            // Setup input event handlers
            chatbotInput.on('keypress', function(e) {
                if (e.which === 13) {
                    sendMessage();
                    e.preventDefault();
                }
            });
            
            chatbotSubmit.on('click', function(e) {
                e.preventDefault();
                sendMessage();
            });
            
        } catch (error) {
            console.error('Error initializing chatbot:', error);
        }

        $('#wp-deepseek-reset-chat').on('click', resetChat);
    }
    function resetChat() {
        $.ajax({
            url: wpDeepseekChatbot.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_deepseek_reset_chat',
                nonce: wpDeepseekChatbot.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Clear chat messages except the first welcome message
                    var firstMessage = chatbotMessages.children().first();
                    chatbotMessages.empty().append(firstMessage);
                    chatbotMessages.append('<div class="wp-deepseek-chatbot-context-active">Saya akan mengingat percakapan kita</div>');
                    chatbotMessages.append('<div class="wp-deepseek-chatbot-reset" id="wp-deepseek-reset-chat">Mulai percakapan baru</div>');
                    
                    // Reattach the click event to the new reset button
                    $('#wp-deepseek-reset-chat').on('click', resetChat);
                }
            }
        });
    }

    // Run when document is ready
    $(document).ready(function() {
        try {
            init();
        } catch (error) {
            console.error('Error during chatbot initialization:', error);
        }
    });

})(jQuery);