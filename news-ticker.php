<?php
/**
 * Plugin Name: News Ticker
 * Description: شريط أخبار حديث (Marquee/Typewriter) مع لوحة تحكم وبلوك Gutenberg + WPBakery.
 * Version: 1.1.0
 * Author: Hossam + GPT-5 Thinking
 * License: GPLv2 or later
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Text Domain: news-ticker
 */

if ( ! defined('ABSPATH') ) exit;

define('NT_VER', '1.1.0');
define('NT_PATH', plugin_dir_path(__FILE__));
define('NT_URL',  plugin_dir_url(__FILE__));

require_once NT_PATH . 'inc/helpers.php';
require_once NT_PATH . 'inc/class-settings.php';
require_once NT_PATH . 'inc/class-rest.php';
require_once NT_PATH . 'inc/class-vc.php'; // WPBakery

add_action('plugins_loaded', function () {
    load_plugin_textdomain('news-ticker', false, dirname(plugin_basename(__FILE__)).'/languages');
});

add_action('init', function () {
    // Frontend assets
    wp_register_script(
        'nt-frontend',
        NT_URL . 'build/index.js',
        array(), NT_VER, array('in_footer' => true, 'strategy' => 'defer')
    );
    wp_register_style(
        'nt-style',
        NT_URL . 'build/style.css',
        array(), NT_VER
    );

    // Shortcode
    add_shortcode('news_ticker', 'nt_render_shortcode');

    // Block
    if ( function_exists('register_block_type') ) {
        register_block_type( __DIR__ . '/blocks/news-ticker' );
    }
});

// Top-level admin menu
add_action('admin_menu', function(){
    add_menu_page(
        __('News Ticker', 'news-ticker'),
        __('News Ticker', 'news-ticker'),
        'manage_options',
        'nt',
        ['NT_Settings', 'render'],
        'dashicons-megaphone',
        58
    );
});

function nt_render_shortcode($atts = []) {
    $opts = nt_get_options();
    $atts = shortcode_atts(array(
        'count'       => $opts['count'],
        'cat'         => $opts['category'],
        'speed'       => $opts['speed'],
        'direction'   => $opts['direction'],
        'mode'        => $opts['mode'],
        'sticky'      => $opts['sticky'],       // off | first | only
        'urgent_text' => $opts['urgent_text'],  // "عاجل" label
    ), $atts, 'news_ticker');

    wp_enqueue_style('nt-style');
    wp_enqueue_script('nt-frontend');

    $nonce = wp_create_nonce('wp_rest');

    ob_start(); ?>
    <div
      class="nt-ticker"
      data-nonce="<?php echo esc_attr( $nonce ); ?>"
      data-count="<?php echo absint( $atts['count'] ); ?>"
      data-cat="<?php echo esc_attr( $atts['cat'] ); ?>"
      data-speed="<?php echo esc_attr( $atts['speed'] ); ?>"
      data-direction="<?php echo esc_attr( $atts['direction'] ); ?>"
      data-mode="<?php echo esc_attr( $atts['mode'] ); ?>"
      data-logo="<?php echo esc_url( nt_logo_url() ); ?>"
      data-sticky="<?php echo esc_attr( $atts['sticky'] ); ?>"
      data-urgent="<?php echo esc_attr( $atts['urgent_text'] ); ?>"
      role="region" aria-label="<?php esc_attr_e('Latest news', 'news-ticker');?>"
      tabindex="0"
    >
      <div class="nt-track" data-wp-interactive="nt" data-wp-class--paused="state.paused">
        <button class="nt-prev" aria-label="<?php esc_attr_e('Previous', 'news-ticker');?>">‹</button>
        <div class="nt-items"></div>
        <button class="nt-next" aria-label="<?php esc_attr_e('Next', 'news-ticker');?>">›</button>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
