=== WP DeepSeek AI Chatbot ===
Contributors: amosrid
Tags: chatbot, ai, deepseek, chat, support
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress chatbot plugin that integrates with DeepSeek AI API through OpenRouter and prioritizes internal WordPress search.

== Description ==

The WP DeepSeek AI Chatbot plugin allows you to add a smart chatbot to your WordPress website. The chatbot uses internal WordPress search to find relevant content on your site before querying the DeepSeek AI API for responses, ensuring accurate and site-specific answers.

= Key Features =

* **Intelligent Search**: First searches your WordPress content before querying the AI API
* **Customizable Appearance**: Easily change colors, fonts, and layout through the settings panel
* **Multilingual Support**: Default language is Indonesian, but supports any language
* **Knowledge Base Training**: Add FAQs and train the chatbot with your content
* **Time-sensitive Content Handling**: Properly handles promotions and time-limited content
* **Mobile-friendly**: Responsive design that works on all devices
* **Light-weight**: Optimized for performance

= Settings & Configuration =

The plugin includes a comprehensive settings panel where you can:

* Enter your OpenRouter API key for DeepSeek AI access
* Customize the chatbot appearance (colors, fonts, custom CSS)
* Define your website name and chatbot agent name
* Add conversation starters and knowledge base entries
* Select your preferred language

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-deepseek-chatbot` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the DeepSeek Chatbot menu item in your admin sidebar to configure the plugin
4. Enter your OpenRouter API key to connect with DeepSeek AI

== Frequently Asked Questions ==

= Do I need an API key? =

Yes, you need to sign up for an OpenRouter account and get an API key to use the DeepSeek AI model.

= How does the chatbot prioritize search results? =

The chatbot first searches your WordPress site content, including posts, pages, FAQs, and structured data. If relevant information is found, it uses that to generate a response. Only if no useful information is found does it rely solely on the DeepSeek AI.

= Can I customize the appearance of the chatbot? =

Yes, you can customize colors, fonts, layout, and add your own custom CSS through the settings panel.

= Is the chatbot mobile-friendly? =

Yes, the chatbot is responsive and works on all devices including desktops, tablets, and mobile phones.

== Screenshots ==

1. Chatbot in action on a WordPress site
2. Settings panel - General settings
3. Settings panel - Appearance settings
4. Settings panel - Chat training options

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the WP DeepSeek AI Chatbot plugin.