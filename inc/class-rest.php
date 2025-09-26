<?php
if ( ! defined('ABSPATH') ) exit;

class NT_REST {
    const NS = 'nt/v1';

    public static function init(){
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes(){
        register_rest_route(self::NS, '/news', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'get_news'],
            'permission_callback' => '__return_true',
            'args' => [
                'count'  => ['validate_callback'=>'absint'],
                'cat'    => ['sanitize_callback'=>'sanitize_title'],
                'sticky' => ['sanitize_callback'=>'sanitize_text_field'],
            ],
        ]);
    }

    public static function get_news($req){
        if ( ! wp_verify_nonce( $req->get_header('x_wp_nonce'), 'wp_rest' ) ) {
            return new WP_Error('forbidden', __('Invalid nonce','news-ticker'), ['status'=>403]);
        }

        $count  = max(1, min(50, absint($req['count'] ?? nt_get_options()['count'])));
        $cat    = sanitize_title($req['cat'] ?? nt_get_options()['category']);
        $sticky = in_array(($req['sticky'] ?? nt_get_options()['sticky']), ['off','first','only'], true) ? $req['sticky'] : 'off';

        // Transient cache for guests only
        $cache_key = 'nt_news_' . md5(serialize([$count,$cat,$sticky]));
        if ( ! is_user_logged_in() ) {
            $cached = get_transient($cache_key);
            if ( $cached ) {
                return rest_ensure_response($cached);
            }
        }

        $args = [
            'post_type' => 'post',
            'posts_per_page' => $count,
            'no_found_rows' => true,
            'ignore_sticky_posts' => ($sticky === 'off') ? 1 : 0,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        if ($cat) $args['category_name'] = $cat;

        $items = [];

        if ( $sticky !== 'off' ) {
            $sticky_ids = get_option('sticky_posts');
            $sticky_ids = array_filter(array_map('intval', (array) $sticky_ids));
            if ( ! empty($sticky_ids) ) {
                if ( $sticky === 'only' ) {
                    $args['post__in'] = $sticky_ids;
                    $args['orderby']  = 'post__in';
                } elseif ( $sticky === 'first' ) {
                    // Fetch sticky first, then fill rest with recent non-sticky
                    $stickies = get_posts([
                        'post_type'=>'post','post__in'=>$sticky_ids,'post_status'=>'publish',
                        'numberposts'=>min(count($sticky_ids), $count), 'orderby'=>'post__in'
                    ]);
                    foreach ($stickies as $p){
                        $items[] = [
                            'id'    => $p->ID,
                            'title' => wp_strip_all_tags(get_the_title($p)),
                            'url'   => get_permalink($p),
                            'date'  => get_the_date('', $p),
                            'sticky'=> true,
                        ];
                    }
                    $count -= count($items);
                    if ( $count <= 0 ) {
                        $result = ['items'=>$items];
                        if ( ! is_user_logged_in() ) set_transient($cache_key, $result, MINUTE_IN_SECONDS*5);
                        return rest_ensure_response($result);
                    }
                    $args['post__not_in'] = $sticky_ids;
                }
            }
        }

        $q = new WP_Query($args);
        foreach ($q->posts as $p){
            $items[] = [
                'id'    => $p->ID,
                'title' => wp_strip_all_tags(get_the_title($p)),
                'url'   => get_permalink($p),
                'date'  => get_the_date('', $p),
                'sticky'=> is_sticky($p->ID),
            ];
        }

        $result = ['items'=>$items];
        if ( ! is_user_logged_in() ) {
            set_transient($cache_key, $result, MINUTE_IN_SECONDS*5);
        }
        return rest_ensure_response($result);
    }
}
NT_REST::init();
