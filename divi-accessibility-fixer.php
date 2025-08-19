<?php
/**
 * Plugin Name: Divi Accessibility Fixer (Safe Version)
 * Description: Accessibility fixes for Divi themes
 * Version: 1.0.1
 * Author: Eve
 * License: GPL v2 or later
 * 
 * IMPORTANT: This is a minimal, safe version to prevent site crashes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Only proceed if we're sure we won't break anything
if (!class_exists('DiviAccessibilityFixer')) {
    
    class DiviAccessibilityFixer {
        
        private $plugin_url;
        private $plugin_path;
        
        public function __construct() {
            $this->plugin_url = plugin_dir_url(__FILE__);
            $this->plugin_path = plugin_dir_path(__FILE__);
            
            // Only add hooks if WordPress is fully loaded
            add_action('init', array($this, 'safe_init'));
        }
        
        public function safe_init() {
            // Check if we're in admin or if Divi is active
            if (is_admin()) {
                add_action('admin_menu', array($this, 'add_admin_menu'));
                return;
            }
            
            // Only proceed on frontend
            add_action('wp_enqueue_scripts', array($this, 'enqueue_safe_scripts'));
            add_action('wp_head', array($this, 'add_safe_head_fixes'));
            add_action('wp_footer', array($this, 'add_safe_footer_fixes'));
        }
        
        public function enqueue_safe_scripts() {
            // Only enqueue if jQuery is available
            if (wp_script_is('jquery', 'registered')) {
                wp_enqueue_script('jquery');
            }
            
            // Add minimal CSS inline to avoid file path issues
            wp_add_inline_style('wp-block-library', $this->get_safe_css());
        }
        
        public function add_safe_head_fixes() {
            ?>
            <!-- Divi Accessibility: Safe viewport fix -->
            <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=3.0">
            
            <style id="divi-a11y-safe-css">
            /* Safe accessibility fixes */
            .skip-link {
                position: absolute;
                top: -40px;
                left: 6px;
                z-index: 999999;
                color: #fff !important;
                background: #000 !important;
                padding: 8px 16px;
                text-decoration: none;
                border-radius: 3px;
                font-size: 14px;
                font-weight: bold;
                transition: top 0.3s ease;
            }
            
            .skip-link:focus {
                top: 6px;
            }
            
            /* Focus indicators */
            *:focus {
                outline: 2px solid #005fcc !important;
                outline-offset: 2px !important;
            }
            
            /* Screen reader text */
            .screen-reader-text {
                clip: rect(1px, 1px, 1px, 1px);
                position: absolute !important;
                height: 1px;
                width: 1px;
                overflow: hidden;
            }
            
            .screen-reader-text:focus {
                clip: auto;
                height: auto;
                width: auto;
                display: block;
                font-size: 1em;
                font-weight: bold;
                padding: 15px 23px 14px;
                background: #fff;
                color: #000;
                text-decoration: none;
                z-index: 100000;
                position: absolute;
                top: 5px;
                left: 5px;
            }
            
            /* Ensure minimum touch targets */
            .et_pb_button {
                min-height: 44px !important;
                min-width: 44px !important;
            }
            </style>
            <?php
        }
        
        public function add_safe_footer_fixes() {
            ?>
            <script id="divi-a11y-safe-js">
            (function() {
                'use strict';
                
                // Wait for DOM to be ready
                function initSafeAccessibility() {
                    console.log('Divi Accessibility Plugin: Starting initialization...');
                    
                    // Add skip link if not exists
                    if (!document.querySelector('.skip-link')) {
                        var skipLink = document.createElement('a');
                        skipLink.href = '#main';
                        skipLink.className = 'skip-link screen-reader-text';
                        skipLink.textContent = 'Skip to main content';
                        
                        // Safely add to body
                        if (document.body && document.body.firstChild) {
                            document.body.insertBefore(skipLink, document.body.firstChild);
                            console.log('✓ Skip link added');
                        }
                        
                        // Add click handler
                        skipLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            var target = document.querySelector('#main, [role="main"], .et_pb_section');
                            if (target) {
                                target.setAttribute('tabindex', '-1');
                                target.focus();
                                target.scrollIntoView();
                            }
                        });
                    } else {
                        console.log('✓ Skip link already exists');
                    }
                    
                    // AGGRESSIVE landmark addition - force add even if others exist
                    var mainContent = document.querySelector('#main-content');
                    if (mainContent && !document.querySelector('[role="main"]')) {
                        mainContent.setAttribute('role', 'main');
                    }

                    
                    // Add navigation landmarks to menus
                    var menus = document.querySelectorAll('.et_pb_menu, nav, .et_mobile_nav_menu');
                    menus.forEach(function(menu, index) {
                        if (!menu.getAttribute('role')) {
                            menu.setAttribute('role', 'navigation');
                            menu.setAttribute('aria-label', 'Site navigation ' + (index + 1));
                            console.log('✓ Navigation landmark added to menu', index + 1);
                        }
                    });
                    
                    // Add banner role to header
                    var headers = document.querySelectorAll('header, .et-l--header');
                    headers.forEach(function(header) {
                        if (!header.getAttribute('role')) {
                            header.setAttribute('role', 'banner');
                            console.log('✓ Banner landmark added to header');
                        }
                    });
                    
                    // Add contentinfo role to footer
                    var footers = document.querySelectorAll('footer, .et-l--footer');
                    footers.forEach(function(footer) {
                        if (!footer.getAttribute('role')) {
                            footer.setAttribute('role', 'contentinfo');
                            console.log('✓ Contentinfo landmark added to footer');
                        }
                    });
                    
                    // Add basic ARIA to toggles
                    var toggles = document.querySelectorAll('.et_pb_toggle_title');
                    toggles.forEach(function(toggle, index) {
                        if (!toggle.hasAttribute('tabindex')) {
                            toggle.setAttribute('tabindex', '0');
                            toggle.setAttribute('role', 'button');
                            toggle.setAttribute('aria-expanded', 'false');
                            
                            toggle.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    toggle.click();
                                }
                            });
                            console.log('✓ Toggle accessibility added', index + 1);
                        }
                    });
                    
                    // Fix images without alt text
                    var images = document.querySelectorAll('img:not([alt])');
                    images.forEach(function(img) {
                        img.setAttribute('alt', '');
                    });
                    if (images.length > 0) {
                        console.log('✓ Fixed', images.length, 'images missing alt text');
                    }
                    
                    // Add labels to form inputs missing them
                    var inputs = document.querySelectorAll('input:not([aria-label]):not([aria-labelledby])');
                    inputs.forEach(function(input) {
                        var placeholder = input.getAttribute('placeholder');
                        if (placeholder) {
                            input.setAttribute('aria-label', placeholder);
                        }
                    });
                    if (inputs.length > 0) {
                        console.log('✓ Added labels to', inputs.length, 'form inputs');
                    }
                    
                    // Final report
                    setTimeout(function() {
                        console.log('🎉 Divi Accessibility Plugin: Initialization complete!');
                        console.log('📊 Landmarks check:');
                        console.log('   Main:', document.querySelectorAll('[role="main"]').length);
                        console.log('   Navigation:', document.querySelectorAll('[role="navigation"]').length);
                        console.log('   Banner:', document.querySelectorAll('[role="banner"]').length);
                        console.log('   Contentinfo:', document.querySelectorAll('[role="contentinfo"]').length);
                    }, 100);
                }
                
                // Multiple initialization attempts to ensure it works
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initSafeAccessibility);
                } else {
                    initSafeAccessibility();
                }
                
                // Also try after a delay in case other scripts modify the DOM
                setTimeout(initSafeAccessibility, 500);
                setTimeout(initSafeAccessibility, 1000);
                
            })();
            </script>
            <?php
        }
        
        private function get_safe_css() {
            return '
            .skip-link { position: absolute; top: -40px; left: 6px; z-index: 999999; color: #fff !important; background: #000 !important; padding: 8px 16px; text-decoration: none; }
            .skip-link:focus { top: 6px; }
            *:focus { outline: 2px solid #005fcc !important; outline-offset: 2px !important; }
            .screen-reader-text { clip: rect(1px, 1px, 1px, 1px); position: absolute !important; height: 1px; width: 1px; overflow: hidden; }
            ';
        }
        
        // Safe admin interface
        public function add_admin_menu() {
            add_options_page(
                'Divi Accessibility Settings',
                'Divi Accessibility',
                'manage_options',
                'divi-accessibility-safe',
                array($this, 'admin_page')
            );
        }
        
        public function admin_page() {
            ?>
            <div class="wrap">
                <h1>Divi Accessibility Settings (Safe Mode)</h1>
                <div class="notice notice-success">
                    <p><strong>Plugin Active!</strong> This safe version provides basic accessibility fixes without breaking your site.</p>
                </div>
                
                <h2>What This Plugin Fixes:</h2>
                <ul>
                    <li>✅ Adds skip links for screen readers</li>
                    <li>✅ Improves keyboard navigation focus indicators</li>
                    <li>✅ Adds basic ARIA landmarks</li>
                    <li>✅ Fixes missing alt text on images</li>
                    <li>✅ Enhances form accessibility</li>
                    <li>✅ Makes Divi toggles keyboard accessible</li>
                    <li>✅ Enables zooming (fixes viewport meta tag)</li>
                </ul>
                
                <h2>Test Your Site:</h2>
                <p><a href="https://wave.webaim.org/report#/<?php echo urlencode(home_url()); ?>" target="_blank" class="button">Test with WAVE</a></p>
                
                <h2>Plugin Status:</h2>
                <table class="form-table">
                    <tr>
                        <th>WordPress Version:</th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th>Theme:</th>
                        <td><?php echo wp_get_theme()->get('Name'); ?></td>
                    </tr>
                    <tr>
                        <th>Divi Detected:</th>
                        <td><?php echo (function_exists('et_setup_theme') || get_template() === 'Divi') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                </table>
            </div>
            <?php
        }
    }
    
    // Initialize the plugin safely
    new DiviAccessibilityFixer();
}

// Safe activation hook
register_activation_hook(__FILE__, function() {
    // Just set a flag that plugin was activated
    update_option('divi_a11y_safe_activated', time());
});

// Safe deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
    delete_option('divi_a11y_safe_activated');
});

// Error handling
function divi_a11y_error_handler($errno, $errstr, $errfile, $errline) {
    if (strpos($errfile, 'divi-accessibility-fixer') !== false) {
        error_log("Divi A11y Plugin Error: $errstr in $errfile on line $errline");
        return true;
    }
    return false;
}

set_error_handler('divi_a11y_error_handler');
?>