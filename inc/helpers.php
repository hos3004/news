<?php
if ( ! defined('ABSPATH') ) exit;

function nt_css_vars_inline(){
    $o = nt_get_options();
    $vars = sprintf(
        '--nt-bg:%s;--nt-color:%s;--nt-arrow:%s;font-family:%s;font-size:%dpx;font-weight:%s;',
        esc_attr($o['bg']), esc_attr($o['color']), esc_attr($o['arrow_color']),
        esc_attr($o['font_family']), absint($o['font_size']), esc_attr($o['font_weight'])
    );
    echo '<style id="nt-vars">.nt-ticker{'.$vars.'}</style>';
}
add_action('wp_head', 'nt_css_vars_inline');

register_activation_hook(__FILE__ , function(){ nt_get_options(); });
