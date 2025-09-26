<?php
if ( ! defined('ABSPATH') ) exit;

class NT_VC {
    public static function init(){
        // Map element if WPBakery is active
        if ( function_exists('vc_map') ) {
            add_action('vc_before_init', [__CLASS__, 'map']);
        }
    }
    public static function map(){
        vc_map(array(
            'name'        => __('News Ticker','news-ticker'),
            'base'        => 'news_ticker',
            'description' => __('Ticker of latest posts with marquee/typewriter','news-ticker'),
            'category'    => __('Content','js_composer'),
            'icon'        => 'icon-wpb-ui-accordion',
            'params'      => array(
                array(
                    'type' => 'textfield',
                    'heading' => __('Category Slug','news-ticker'),
                    'param_name' => 'cat',
                    'value' => ''
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Mode','news-ticker'),
                    'param_name' => 'mode',
                    'value' => array('Marquee'=>'marquee','Typewriter'=>'typewriter')
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Direction','news-ticker'),
                    'param_name' => 'direction',
                    'value' => array('RTL'=>'rtl','LTR'=>'ltr')
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __('Items Count','news-ticker'),
                    'param_name' => 'count',
                    'value' => '7'
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __('Speed (ms)','news-ticker'),
                    'param_name' => 'speed',
                    'value' => '5000'
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Sticky','news-ticker'),
                    'param_name' => 'sticky',
                    'value' => array('Off'=>'off','Sticky First'=>'first','Sticky Only'=>'only')
                ),
            )
        ));
    }
}
NT_VC::init();
