<?php
if ( ! defined('ABSPATH') ) exit;

class NT_Settings {
    const KEY = 'nt_options';

    public static function init() {
        add_action('admin_init', [__CLASS__, 'register']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
    }

    public static function assets($hook) {
        if ($hook !== 'toplevel_page_nt') return;
        wp_enqueue_style('nt-admin', NT_URL.'assets/admin.css', [], NT_VER);
    }

    public static function register() {
        register_setting(self::KEY, self::KEY, ['sanitize_callback' => [__CLASS__, 'sanitize']]);

        add_settings_section('nt_main', __('General', 'news-ticker'), '__return_false', 'nt');
        add_settings_field('count', __('Items Count', 'news-ticker'), [__CLASS__, 'field_number'], 'nt', 'nt_main', ['key'=>'count', 'min'=>1, 'max'=>20]);
        add_settings_field('category', __('Category Slug', 'news-ticker'), [__CLASS__, 'field_text'], 'nt', 'nt_main', ['key'=>'category']);
        add_settings_field('logo', __('Small Logo URL', 'news-ticker'), [__CLASS__, 'field_text'], 'nt', 'nt_main', ['key'=>'logo']);
        add_settings_field('speed', __('Speed (ms)', 'news-ticker'), [__CLASS__, 'field_number'], 'nt', 'nt_main', ['key'=>'speed','min'=>100,'max'=>20000]);
        add_settings_field('direction', __('Direction', 'news-ticker'), [__CLASS__, 'field_select'], 'nt', 'nt_main', ['key'=>'direction','choices'=>['ltr'=>'LTR','rtl'=>'RTL']]);
        add_settings_field('mode', __('Mode', 'news-ticker'), [__CLASS__, 'field_select'], 'nt', 'nt_main', ['key'=>'mode','choices'=>['marquee'=>'Marquee','typewriter'=>'Typewriter']]);

        // Sticky/Urgent
        add_settings_section('nt_sticky', __('Sticky / Urgent', 'news-ticker'), '__return_false', 'nt');
        add_settings_field('sticky', __('Sticky Behavior', 'news-ticker'), [__CLASS__, 'field_select'], 'nt', 'nt_sticky', ['key'=>'sticky','choices'=>['off'=>'Off','first'=>'Sticky First','only'=>'Sticky Only']]);
        add_settings_field('urgent_text', __('Urgent Label', 'news-ticker'), [__CLASS__, 'field_text'], 'nt', 'nt_sticky', ['key'=>'urgent_text']);

        // Typography & Colors
        add_settings_section('nt_style', __('Typography & Colors', 'news-ticker'), '__return_false', 'nt');
        add_settings_field('font_family', __('Font Family', 'news-ticker'), [__CLASS__, 'field_text'], 'nt','nt_style',['key'=>'font_family']);
        add_settings_field('font_size', __('Font Size (px)', 'news-ticker'), [__CLASS__, 'field_number'], 'nt','nt_style',['key'=>'font_size','min'=>10,'max'=>48]);
        add_settings_field('font_weight', __('Font Weight', 'news-ticker'), [__CLASS__, 'field_text'], 'nt','nt_style',['key'=>'font_weight']);
        add_settings_field('color', __('Text Color', 'news-ticker'), [__CLASS__, 'field_text'], 'nt','nt_style',['key'=>'color']);
        add_settings_field('bg', __('Background', 'news-ticker'), [__CLASS__, 'field_text'], 'nt','nt_style',['key'=>'bg']);
        add_settings_field('arrow_color', __('Arrows Color', 'news-ticker'), [__CLASS__, 'field_text'], 'nt','nt_style',['key'=>'arrow_color']);
    }

    public static function sanitize($in) {
        $out = nt_default_options();
        $out['count']      = isset($in['count']) ? max(1, min(50, absint($in['count']))) : $out['count'];
        $out['category']   = isset($in['category']) ? sanitize_title($in['category']) : '';
        $out['logo']       = isset($in['logo']) ? esc_url_raw($in['logo']) : '';
        $out['speed']      = isset($in['speed']) ? absint($in['speed']) : 5000;
        $out['direction']  = in_array($in['direction'] ?? 'rtl', ['rtl','ltr'], true) ? $in['direction'] : 'rtl';
        $out['mode']       = in_array($in['mode'] ?? 'marquee', ['marquee','typewriter'], true) ? $in['mode'] : 'marquee';
        $out['sticky']     = in_array($in['sticky'] ?? 'off', ['off','first','only'], true) ? $in['sticky'] : 'off';
        $out['urgent_text']= isset($in['urgent_text']) ? sanitize_text_field($in['urgent_text']) : '';

        foreach (['font_family','font_weight','color','bg','arrow_color'] as $k) {
            $out[$k] = isset($in[$k]) ? sanitize_text_field($in[$k]) : '';
        }
        $out['font_size']  = isset($in['font_size']) ? absint($in['font_size']) : 16;
        return $out;
    }

    public static function field_text($args){ self::field_generic('text', $args); }
    public static function field_number($args){ self::field_generic('number', $args); }
    public static function field_select($args){
        $opts = nt_get_options();
        $val = $opts[$args['key']] ?? '';
        echo '<select name="'.esc_attr(self::KEY.'['.$args['key'].']').'">';
        foreach (($args['choices'] ?? []) as $k=>$label){
            printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($val, $k, false), esc_html($label));
        }
        echo '</select>';
    }
    private static function field_generic($type, $args){
        $opts = nt_get_options();
        $k = $args['key']; $val = $opts[$k] ?? '';
        $extra = '';
        if ($type==='number'){
            $min = isset($args['min']) ? ' min="'.intval($args['min']).'"' : '';
            $max = isset($args['max']) ? ' max="'.intval($args['max']).'"' : '';
            $extra = $min.$max;
        }
        printf('<input type="%s" name="%s" value="%s"%s />',
            esc_attr($type),
            esc_attr(self::KEY.'['.$k.']'),
            esc_attr($val),
            $extra
        );
    }

    public static function render(){
        if ( ! current_user_can('manage_options') ) return;
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('News Ticker', 'news-ticker');?></h1>
          <form method="post" action="options.php">
            <?php
              settings_fields(self::KEY);
              do_settings_sections('nt');
              submit_button();
            ?>
          </form>
          <p><code>[news_ticker]</code></p>
        </div>
        <?php
    }
}
NT_Settings::init();

function nt_default_options(){
    return [
        'count' => 7, 'category' => '', 'logo' => '',
        'speed' => 5000, 'direction' => is_rtl() ? 'rtl' : 'ltr', 'mode' => 'marquee',
        'sticky' => 'off', 'urgent_text' => 'عاجل',
        'font_family' => 'system-ui, Arial, sans-serif', 'font_size' => 16,
        'font_weight' => '600', 'color' => '#111', 'bg' => '#f7f7f7', 'arrow_color' => '#333'
    ];
}
function nt_get_options(){
    $o = get_option(NT_Settings::KEY);
    return wp_parse_args($o ?: [], nt_default_options());
}
function nt_logo_url(){
    $o = nt_get_options(); return $o['logo'] ?: '';
}
