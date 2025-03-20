/**
 * WP DeepSeek Chatbot Frontend Script - Optimized
 */
(function($) {
    'use strict';

    // Variables
    var chatbotWidget = $('#wp-deepseek-chatbot-widget'),
        chatbotMessages = $('#wp-deepseek-chatbot-messages'),
        chatbotInput = $('#wp-deepseek-chatbot-input'),
        isProcessing = false;

    // Add message to the chat
    function addMessage(message, isUser) {
        var messageClass = isUser ? 'wp-deepseek-chatbot-message-user' : 'wp-deepseek-chatbot-message-bot';
        
        // Parse markdown in bot messages
        if (!isUser) {
            message = parseMarkdown(message);
        }
        
        chatbotMessages.append(
            '<div class="wp-deepseek-chatbot-message ' + messageClass + '">' +
            '<div class="wp-deepseek-chatbot-message-content">' + message + '</div>' +
            '</div>'
        );
        scrollToBottom();
    }
    
    // Parse markdown with simplified regex
    function parseMarkdown(text) {
        text = text.replace(/\n/g, '<br>')
            .replace(/## (.*?)(<br>|$)/g, '<h2>$1</h2>')
            .replace(/### (.*?)(<br>|$)/g, '<h3>$1</h3>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/- (.*?)(<br>|$)/g, '<span class="wp-deepseek-list-item">• $1</span>$2')
            .replace(/^(\d+)\.\s+(.*?)$/gm, function(match, num, content) {
                return '<span class="wp-deepseek-list-item"><span class="wp-deepseek-list-number">' + num + '.</span> ' + content + '</span><br>';
            })
            .replace(/---+(<br>|$)/g, '<hr>$1')
            .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        
        return text;
    }

    // Typing indicator functions
    function addTypingIndicator() {
        chatbotMessages.append(
            '<div class="wp-deepseek-chatbot-typing" id="wp-deepseek-chatbot-typing">' +
            '<span class="wp-deepseek-chatbot-typing-dot"></span>' +
            '<span class="wp-deepseek-chatbot-typing-dot"></span>' +
            '<span class="wp-deepseek-chatbot-typing-dot"></span>' +
            '</div>'
        );
        scrollToBottom();
    }
    
    function removeTypingIndicator() {
        $('#wp-deepseek-chatbot-typing').remove();
    }

    // Scroll to bottom of messages
    function scrollToBottom() {
        chatbotMessages.scrollTop(chatbotMessages[0].scrollHeight);
    }

    // Send message to server
    function sendMessage() {
        var message = chatbotInput.val().trim();
        
        if (message === '' || isProcessing) return;
        
        chatbotInput.val('');
        addMessage(message, true);
        addTypingIndicator();
        isProcessing = true;
        
        $.ajax({
            url: wpDeepseekChatbot.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_deepseek_chatbot_request',
                nonce: wpDeepseekChatbot.nonce,
                query: message
            },
            success: function(response) {
                removeTypingIndicator();
                
                if (response.success && response.data.message) {
                    addMessage(response.data.message, false);
                } else {
                    var errorMsg = response.data && response.data.error ? response.data.error : 'Unknown error occurred';
                    console.error('API Response Error:', errorMsg);
                    addMessage('Sorry, I encountered an error. Please try again later. (Error: ' + errorMsg + ')', false);
                }
            },
            error: function(xhr, status) {
                removeTypingIndicator();
                console.error('AJAX Error:', status);
                addMessage('Sorry, I encountered a connection error. Please check your internet connection and try again.', false);
            },
            complete: function() {
                isProcessing = false;
            }
        });
    }

    // Apply theme colors
    function applyThemeColors() {
        try {
            document.documentElement.style.setProperty('--wp-deepseek-primary-color', wpDeepseekChatbot.primary_color);
            document.documentElement.style.setProperty('--wp-deepseek-primary-color-dark', adjustColor(wpDeepseekChatbot.primary_color, -20));
            document.documentElement.style.setProperty('--wp-deepseek-font-family', wpDeepseekChatbot.font_family || 'Arial, sans-serif');
        } catch (error) {
            console.error('Error applying theme colors:', error);
        }
    }

    // Adjust color brightness
    function adjustColor(color, percent) {
        var R = parseInt(color.substring(1, 3), 16),
            G = parseInt(color.substring(3, 5), 16),
            B = parseInt(color.substring(5, 7), 16);

        R = Math.min(255, parseInt(R * (100 + percent) / 100));
        G = Math.min(255, parseInt(G * (100 + percent) / 100));
        B = Math.min(255, parseInt(B * (100 + percent) / 100));

        return "#" + 
            ((R.toString(16).length === 1) ? "0" + R.toString(16) : R.toString(16)) +
            ((G.toString(16).length === 1) ? "0" + G.toString(16) : G.toString(16)) +
            ((B.toString(16).length === 1) ? "0" + B.toString(16) : B.toString(16));
    }

    // Reset chat conversation
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
                    
                    // Reattach the click event
                    $('#wp-deepseek-reset-chat').on('click', resetChat);
                }
            }
        });
    }

    // Initialize
    function init() {
        try {
            applyThemeColors();
            
            // Event handlers
            $('#wp-deepseek-chatbot-toggle').on('click', function() {
                chatbotWidget.toggleClass('active');
                if (chatbotWidget.hasClass('active')) chatbotInput.focus();
            });
            
            $('.wp-deepseek-chatbot-close').on('click', function() {
                chatbotWidget.removeClass('active');
            });
            
            chatbotInput.on('keypress', function(e) {
                if (e.which === 13) {
                    sendMessage();
                    e.preventDefault();
                }
            });
            
            $('#wp-deepseek-chatbot-submit').on('click', sendMessage);
            $('#wp-deepseek-reset-chat').on('click', resetChat);
            
        } catch (error) {
            console.error('Error initializing chatbot:', error);
        }
    }

    $(document).ready(init);

})(jQuery);