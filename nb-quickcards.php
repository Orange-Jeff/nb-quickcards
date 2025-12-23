<?php
/**
 * Plugin Name: NB QuickCards
 * Plugin URI: https://netbound.ca/plugins/nb-quickcards
 * Description: Beautiful link preview cards, quote cards, and post-it notes with customizable styling.
 * Version: 1.0.2
 * Author: Orange Jeff
 * Author URI: https://netbound.ca
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nb-quickcards
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Version: 1.0.2 - December 23, 2025 - Added menu icon
// Version: 1.0.1 - December 23, 2025 - Fixed menu to use NetBound Tools instead of Settings
// Version: 1.0.0 - Initial release with link cards, quote cards, and post-it style

if (!defined('ABSPATH')) {
    exit;
}

class NB_QuickCards {

    private static $instance = null;
    private $defaults;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->defaults = get_option('nb_quickcards_defaults', array(
            'border_color' => '#e0e0e0',
            'border_width' => '1',
            'border_style' => 'solid',
            'border_radius' => '8',
            'shadow' => 'true',
            'cache_duration' => 24 // hours
        ));

        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_shortcodes() {
        add_shortcode('nb_link_card', array($this, 'render_link_card'));
        add_shortcode('nb_quote_card', array($this, 'render_quote_card'));
        add_shortcode('nb_link_list', array($this, 'render_link_list'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'nb-quickcards',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Link Card - Auto-fetches URL metadata
     */
    public function render_link_card($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'style' => 'card',
            'border_color' => $this->defaults['border_color'],
            'border_width' => $this->defaults['border_width'],
            'border_style' => $this->defaults['border_style'],
            'border_radius' => $this->defaults['border_radius'],
            'border_left' => '',
            'shadow' => $this->defaults['shadow'],
            'postit_color' => 'yellow',
            'target' => '_blank',
            'nofollow' => 'true',
        ), $atts, 'nb_link_card');

        if (empty($atts['url'])) {
            return '<!-- NB QuickCards: No URL provided -->';
        }

        // Fetch URL metadata (cached)
        $meta = $this->fetch_url_metadata($atts['url']);

        if (!$meta) {
            return '<!-- NB QuickCards: Could not fetch URL metadata -->';
        }

        $style_attr = $this->build_style_attribute($atts);
        $classes = $this->build_classes($atts);
        $rel = $atts['nofollow'] === 'true' ? 'noopener noreferrer nofollow' : 'noopener noreferrer';

        $output = '<div class="nb-quickcard ' . esc_attr($classes) . '" style="' . esc_attr($style_attr) . '">';

        if ($atts['style'] === 'postit') {
            $output .= '<div class="nb-postit-fold" style="' . $this->get_postit_fold_style($atts['postit_color']) . '"></div>';
        }

        $output .= '<a href="' . esc_url($atts['url']) . '" target="' . esc_attr($atts['target']) . '" rel="' . $rel . '" class="nb-quickcard-link">';

        // Image
        if (!empty($meta['image']) && $atts['style'] !== 'postit') {
            $output .= '<div class="nb-quickcard-image">';
            $output .= '<img src="' . esc_url($meta['image']) . '" alt="' . esc_attr($meta['title']) . '" loading="lazy">';
            $output .= '</div>';
        }

        $output .= '<div class="nb-quickcard-content">';
        $output .= '<div class="nb-quickcard-title">' . esc_html($meta['title']) . '</div>';

        if (!empty($meta['description'])) {
            $output .= '<div class="nb-quickcard-description">' . esc_html($meta['description']) . '</div>';
        }

        $output .= '<div class="nb-quickcard-domain">' . esc_html($meta['domain']) . '</div>';
        $output .= '</div>';

        $output .= '</a>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Quote Card - Manual quote with attribution
     */
    public function render_quote_card($atts, $content = null) {
        $atts = shortcode_atts(array(
            'source' => '',
            'author' => '',
            'url' => '',
            'style' => 'card',
            'border_color' => $this->defaults['border_color'],
            'border_width' => $this->defaults['border_width'],
            'border_style' => $this->defaults['border_style'],
            'border_radius' => $this->defaults['border_radius'],
            'border_left' => '',
            'shadow' => $this->defaults['shadow'],
            'postit_color' => 'yellow',
        ), $atts, 'nb_quote_card');

        if (empty($content)) {
            return '<!-- NB QuickCards: No quote content provided -->';
        }

        $style_attr = $this->build_style_attribute($atts);
        $classes = $this->build_classes($atts) . ' nb-quickcard-quote';

        $output = '<div class="nb-quickcard ' . esc_attr($classes) . '" style="' . esc_attr($style_attr) . '">';

        if ($atts['style'] === 'postit') {
            $output .= '<div class="nb-postit-fold" style="' . $this->get_postit_fold_style($atts['postit_color']) . '"></div>';
        }

        $output .= '<div class="nb-quickcard-content">';

        // Quote icon
        $output .= '<div class="nb-quickcard-quote-icon">"</div>';

        // Quote text
        $output .= '<div class="nb-quickcard-quote-text">' . wp_kses_post(wpautop($content)) . '</div>';

        // Attribution
        if (!empty($atts['author']) || !empty($atts['source'])) {
            $output .= '<div class="nb-quickcard-attribution">';

            // Initial/Avatar
            $initial = !empty($atts['author']) ? strtoupper(substr($atts['author'], 0, 1)) : '?';
            $output .= '<div class="nb-quickcard-avatar">' . $initial . '</div>';

            $output .= '<div class="nb-quickcard-meta">';
            if (!empty($atts['author'])) {
                $output .= '<div class="nb-quickcard-author">' . esc_html($atts['author']) . '</div>';
            }
            if (!empty($atts['source'])) {
                if (!empty($atts['url'])) {
                    $output .= '<a href="' . esc_url($atts['url']) . '" class="nb-quickcard-source" target="_blank" rel="noopener noreferrer">' . esc_html($atts['source']) . '</a>';
                } else {
                    $output .= '<div class="nb-quickcard-source">' . esc_html($atts['source']) . '</div>';
                }
            }
            $output .= '</div>';

            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Link List - Compact list of multiple URLs
     */
    public function render_link_list($atts, $content = null) {
        $atts = shortcode_atts(array(
            'border_color' => $this->defaults['border_color'],
            'border_width' => $this->defaults['border_width'],
            'border_style' => $this->defaults['border_style'],
            'border_radius' => $this->defaults['border_radius'],
            'shadow' => $this->defaults['shadow'],
            'target' => '_blank',
            'nofollow' => 'true',
        ), $atts, 'nb_link_list');

        if (empty($content)) {
            return '<!-- NB QuickCards: No URLs provided -->';
        }

        // Parse URLs from content (one per line)
        $urls = array_filter(array_map('trim', explode("\n", strip_tags($content))));

        if (empty($urls)) {
            return '<!-- NB QuickCards: No valid URLs found -->';
        }

        $style_attr = $this->build_style_attribute($atts);
        $rel = $atts['nofollow'] === 'true' ? 'noopener noreferrer nofollow' : 'noopener noreferrer';

        $output = '<div class="nb-quickcard-list" style="' . esc_attr($style_attr) . '">';

        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $meta = $this->fetch_url_metadata($url);

            if (!$meta) {
                continue;
            }

            $output .= '<a href="' . esc_url($url) . '" target="' . esc_attr($atts['target']) . '" rel="' . $rel . '" class="nb-quickcard-list-item">';

            // Favicon
            $output .= '<div class="nb-quickcard-favicon">';
            if (!empty($meta['favicon'])) {
                $output .= '<img src="' . esc_url($meta['favicon']) . '" alt="" loading="lazy">';
            } else {
                $output .= '<span class="nb-quickcard-favicon-placeholder">🔗</span>';
            }
            $output .= '</div>';

            $output .= '<div class="nb-quickcard-list-content">';
            $output .= '<div class="nb-quickcard-list-title">' . esc_html($meta['title']) . '</div>';
            $output .= '<div class="nb-quickcard-list-domain">' . esc_html($meta['domain']) . '</div>';
            $output .= '</div>';

            $output .= '</a>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Fetch URL metadata with caching
     */
    private function fetch_url_metadata($url) {
        $cache_key = 'nb_qc_' . md5($url);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'user-agent' => 'Mozilla/5.0 (compatible; NB QuickCards WordPress Plugin)'
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $meta = array(
            'title' => '',
            'description' => '',
            'image' => '',
            'favicon' => '',
            'domain' => wp_parse_url($url, PHP_URL_HOST)
        );

        // Parse Open Graph tags
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/', $body, $matches)) {
            $meta['title'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']/', $body, $matches)) {
            $meta['title'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $body, $matches)) {
            $meta['title'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/', $body, $matches)) {
            $meta['description'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:description["\']/', $body, $matches)) {
            $meta['description'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/', $body, $matches)) {
            $meta['description'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }

        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $body, $matches)) {
            $meta['image'] = $matches[1];
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $body, $matches)) {
            $meta['image'] = $matches[1];
        }

        // Favicon - try common locations
        $parsed_url = wp_parse_url($url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

        if (preg_match('/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']+)["\']/', $body, $matches)) {
            $favicon = $matches[1];
            if (strpos($favicon, '//') === 0) {
                $meta['favicon'] = 'https:' . $favicon;
            } elseif (strpos($favicon, '/') === 0) {
                $meta['favicon'] = $base_url . $favicon;
            } elseif (strpos($favicon, 'http') !== 0) {
                $meta['favicon'] = $base_url . '/' . $favicon;
            } else {
                $meta['favicon'] = $favicon;
            }
        } else {
            $meta['favicon'] = $base_url . '/favicon.ico';
        }

        // Truncate description
        if (strlen($meta['description']) > 160) {
            $meta['description'] = substr($meta['description'], 0, 157) . '...';
        }

        // Fallback title
        if (empty($meta['title'])) {
            $meta['title'] = $meta['domain'];
        }

        // Cache for configured duration
        $cache_hours = intval($this->defaults['cache_duration']);
        set_transient($cache_key, $meta, $cache_hours * HOUR_IN_SECONDS);

        return $meta;
    }

    /**
     * Build inline style attribute
     */
    private function build_style_attribute($atts) {
        $styles = array();

        // Border left accent (takes priority if set)
        if (!empty($atts['border_left'])) {
            $styles[] = 'border-left: ' . intval($atts['border_left']) . 'px solid ' . sanitize_hex_color_no_hash($atts['border_color']);
            $styles[] = 'border-top: none';
            $styles[] = 'border-right: none';
            $styles[] = 'border-bottom: none';
        } else {
            $styles[] = 'border: ' . intval($atts['border_width']) . 'px ' . esc_attr($atts['border_style']) . ' ' . $this->sanitize_color($atts['border_color']);
        }

        $styles[] = 'border-radius: ' . intval($atts['border_radius']) . 'px';

        if ($atts['shadow'] !== 'true') {
            $styles[] = 'box-shadow: none';
        }

        // Post-it background
        if (isset($atts['style']) && $atts['style'] === 'postit') {
            $styles[] = 'background-color: ' . $this->get_postit_color($atts['postit_color']);
        }

        return implode('; ', $styles);
    }

    /**
     * Build CSS classes
     */
    private function build_classes($atts) {
        $classes = array();

        if (isset($atts['style'])) {
            $classes[] = 'nb-quickcard-style-' . sanitize_html_class($atts['style']);
        }

        if ($atts['shadow'] === 'true') {
            $classes[] = 'nb-quickcard-shadow';
        }

        return implode(' ', $classes);
    }

    /**
     * Get post-it note color
     */
    private function get_postit_color($color) {
        $colors = array(
            'yellow' => '#fff9c4',
            'pink' => '#f8bbd9',
            'blue' => '#bbdefb',
            'green' => '#c8e6c9',
            'orange' => '#ffe0b2',
            'purple' => '#e1bee7',
        );

        if (isset($colors[$color])) {
            return $colors[$color];
        }

        // Allow custom hex colors
        return $this->sanitize_color($color);
    }

    /**
     * Get post-it fold style (corner triangle)
     */
    private function get_postit_fold_style($color) {
        $base = $this->get_postit_color($color);
        // Darken the color slightly for the fold
        return 'border-bottom-color: ' . $this->darken_color($base, 15);
    }

    /**
     * Darken a hex color
     */
    private function darken_color($hex, $percent) {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Sanitize color input (hex or named)
     */
    private function sanitize_color($color) {
        // Named colors
        $named_colors = array('red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'gray', 'grey', 'black', 'white');
        if (in_array(strtolower($color), $named_colors)) {
            return strtolower($color);
        }

        // Hex colors
        if (preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color)) {
            return '#' . ltrim($color, '#');
        }

        return '#e0e0e0'; // Default fallback
    }

    /**
     * Admin menu - Add to NetBound Tools
     */
    public function add_admin_menu() {
        // Check if NetBound Tools menu exists, create if not
        global $menu;
        $netbound_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'netbound-tools') {
                $netbound_exists = true;
                break;
            }
        }

        if (!$netbound_exists) {
            add_menu_page(
                'NetBound Tools',
                'NetBound Tools',
                'manage_options',
                'netbound-tools',
                array($this, 'render_settings_page'),
                'dashicons-superhero',
                30
            );
        }

        add_submenu_page(
            'netbound-tools',
            'QuickCards',
            '🃏 QuickCards',
            'manage_options',
            'nb-quickcards',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nb_quickcards', 'nb_quickcards_defaults', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $sanitized['border_color'] = $this->sanitize_color($input['border_color'] ?? '#e0e0e0');
        $sanitized['border_width'] = absint($input['border_width'] ?? 1);
        $sanitized['border_style'] = sanitize_text_field($input['border_style'] ?? 'solid');
        $sanitized['border_radius'] = absint($input['border_radius'] ?? 8);
        $sanitized['shadow'] = ($input['shadow'] ?? 'true') === 'true' ? 'true' : 'false';
        $sanitized['cache_duration'] = absint($input['cache_duration'] ?? 24);
        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $defaults = $this->defaults;
        ?>
        <div class="wrap">
            <h1>NB QuickCards Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields('nb_quickcards'); ?>

                <h2>Default Styling</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Border Color</th>
                        <td>
                            <input type="text" name="nb_quickcards_defaults[border_color]"
                                   value="<?php echo esc_attr($defaults['border_color']); ?>"
                                   class="regular-text" placeholder="#e0e0e0">
                            <p class="description">Hex color or color name (e.g., #3498db, orange)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Border Width</th>
                        <td>
                            <input type="number" name="nb_quickcards_defaults[border_width]"
                                   value="<?php echo esc_attr($defaults['border_width']); ?>"
                                   min="0" max="10" class="small-text"> px
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Border Style</th>
                        <td>
                            <select name="nb_quickcards_defaults[border_style]">
                                <option value="solid" <?php selected($defaults['border_style'], 'solid'); ?>>Solid</option>
                                <option value="dashed" <?php selected($defaults['border_style'], 'dashed'); ?>>Dashed</option>
                                <option value="dotted" <?php selected($defaults['border_style'], 'dotted'); ?>>Dotted</option>
                                <option value="none" <?php selected($defaults['border_style'], 'none'); ?>>None</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Border Radius</th>
                        <td>
                            <input type="number" name="nb_quickcards_defaults[border_radius]"
                                   value="<?php echo esc_attr($defaults['border_radius']); ?>"
                                   min="0" max="50" class="small-text"> px
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Drop Shadow</th>
                        <td>
                            <label>
                                <input type="checkbox" name="nb_quickcards_defaults[shadow]" value="true"
                                       <?php checked($defaults['shadow'], 'true'); ?>>
                                Enable subtle drop shadow
                            </label>
                        </td>
                    </tr>
                </table>

                <h2>Caching</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Cache Duration</th>
                        <td>
                            <input type="number" name="nb_quickcards_defaults[cache_duration]"
                                   value="<?php echo esc_attr($defaults['cache_duration']); ?>"
                                   min="1" max="168" class="small-text"> hours
                            <p class="description">How long to cache fetched URL metadata (1-168 hours)</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>Shortcode Reference</h2>

            <h3>Link Card</h3>
            <pre style="background:#f5f5f5;padding:15px;border-radius:4px;">[nb_link_card url="https://example.com" style="card" border_color="#3498db" border_width="2"]</pre>
            <p>Auto-fetches title, description, and image from the URL.</p>

            <h3>Quote Card</h3>
            <pre style="background:#f5f5f5;padding:15px;border-radius:4px;">[nb_quote_card source="Website Name" author="Author Name" url="https://source-url.com"]
Your quote text here...
[/nb_quote_card]</pre>

            <h3>Post-it Note</h3>
            <pre style="background:#f5f5f5;padding:15px;border-radius:4px;">[nb_quote_card style="postit" postit_color="yellow"]
Remember to call Mom!
[/nb_quote_card]</pre>
            <p>Colors: yellow, pink, blue, green, orange, purple (or any hex)</p>

            <h3>Link List</h3>
            <pre style="background:#f5f5f5;padding:15px;border-radius:4px;">[nb_link_list]
https://site1.com/article
https://site2.com/resource
https://site3.com/page
[/nb_link_list]</pre>

            <h3>Common Attributes</h3>
            <table class="widefat" style="max-width:600px;">
                <thead>
                    <tr>
                        <th>Attribute</th>
                        <th>Default</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>border_color</code></td><td>#e0e0e0</td><td>Border color (hex or name)</td></tr>
                    <tr><td><code>border_width</code></td><td>1</td><td>Border width in pixels</td></tr>
                    <tr><td><code>border_style</code></td><td>solid</td><td>solid, dashed, dotted, none</td></tr>
                    <tr><td><code>border_radius</code></td><td>8</td><td>Corner radius in pixels</td></tr>
                    <tr><td><code>border_left</code></td><td></td><td>Left accent stripe width (overrides border)</td></tr>
                    <tr><td><code>shadow</code></td><td>true</td><td>Show drop shadow (true/false)</td></tr>
                    <tr><td><code>style</code></td><td>card</td><td>card, minimal, postit</td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Initialize
NB_QuickCards::get_instance();
