<?php
/*
Plugin Name: Room Calendars
Description: Room Calendars – Multi-ICS Viewer with list view filters. Admin under Settings.
Version: 1.2.1
Author: Sebastian Kedzior
Text Domain: room-calendars
*/
if (!defined('ABSPATH')) exit;

class Room_Calendars {
    public function __construct() {
        add_action('init',[ $this,'load_textdomain' ]);
        add_action('admin_menu',[ $this,'register_admin_page' ]);
        add_action('admin_init',[ $this,'register_settings' ]);
        add_action('admin_enqueue_scripts',[ $this,'enqueue_admin_assets' ]);
        add_action('wp_enqueue_scripts',[ $this,'register_assets' ]);
        add_shortcode('room_calendars',[ $this,'render_shortcode' ]);
        add_action('wp_ajax_nopriv_rc_fetch_ics',[ $this,'ajax_fetch_ics' ]);
        add_action('wp_ajax_rc_fetch_ics',[ $this,'ajax_fetch_ics' ]);
    }

    public function load_textdomain() {
        load_plugin_textdomain('room-calendars',false,dirname(plugin_basename(__FILE__)).'/languages/');
    }

    public function register_admin_page() {
        add_options_page(
            __( 'Room Calendars', 'room-calendars' ),
            __( 'Room Calendars', 'room-calendars' ),
            'manage_options',
            'room-calendars',
            [ $this, 'admin_page_callback' ]
        );
    }

    public function register_settings() {
        register_setting('rc_settings','room_calendars_data',[ $this,'sanitize_settings' ]);
        add_settings_section('rc_section','','', 'room-calendars');
        add_settings_field('rc_calendars',__('Kalendarze','room-calendars'),[ $this,'field_calendars_callback'],'room-calendars','rc_section');
    }

    public function sanitize_settings($input) {
        $clean=[];
        if(isset($input['name']) && is_array($input['name'])) {
            foreach($input['name'] as $i=>$name) {
                $name = sanitize_text_field($name);
                $url = esc_url_raw($input['url'][$i] ?? '');
                $color = $input['color'][$i] != null && $input['color'][$i] != '' ? $input['color'][$i] : sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                if($name && $url) $clean[]=['name'=>$name,'url'=>$url, 'color'=>$color];
            }
        }
        return $clean;
    }

    public function enqueue_admin_assets($hook) {
        if($hook!='settings_page_room-calendars') return;
        wp_enqueue_script('rc-admin',plugins_url('assets/js/admin.js',__FILE__),['jquery'],'1.0',true);
        wp_enqueue_style('rc-admin',plugins_url('assets/css/style.css',__FILE__));
    }

    public function field_calendars_callback() {
        $data = get_option('room_calendars_data',[]);
        echo '<table id="rc-calendar-table" class="widefat"><thead><tr><th>Name</th><th>ICS URL</th><th>Color</th><th></th></tr></thead><tbody>';
        foreach($data as $row){
            echo '<tr><td><input name="room_calendars_data[name][]" value="'.esc_attr($row['name'] ?? '').'" /></td>';
            echo '<td><input name="room_calendars_data[url][]" value="'.esc_attr($row['url'] ?? '').'" style="width:100%;" /></td>';
            echo '<td><input name="room_calendars_data[color][]" value="'.esc_attr($row['color'] ?? '').'" /></td>';
            echo '<td><button class="rc-remove-row button">-</button></td></tr>';
        }
        echo '</tbody></table>';
        echo '<p><button id="rc-add-row" class="button">+ Add Calendar</button></p>';
    }

    public function admin_page_callback() {
        ?>
        <div class="wrap">
        <h1><?php esc_html_e('Room Calendars','room-calendars');?></h1>
        <form method="post" action="options.php">
        <?php settings_fields('rc_settings'); do_settings_sections('room-calendars'); submit_button();?>
        </form>
        </div>
        <?php
    }

    public function register_assets() {
        wp_register_script( 'ical-js',
            'https://cdnjs.cloudflare.com/ajax/libs/ical.js/1.4.0/ical.min.js',
            [], '1.4.0', true
        );
        wp_register_script( 'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js',
            [], '5.11.0', true
        );
        wp_register_script( 'fullcalendar-locale-pl',
            'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/locales/pl.js',
            [ 'fullcalendar' ], '5.11.0', true
        );
        wp_register_style( 'fullcalendar-css',
            'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css',
            [], '5.11.0'
        );

        wp_register_script( 'rc-calendars',
            plugins_url( 'assets/js/calendars.js', __FILE__ ),
            [ 'ical-js','fullcalendar','fullcalendar-locale-pl' ],
            '1.1.21', true
        );
        wp_register_style( 'rc-style',
            plugins_url( 'assets/css/style.css', __FILE__ ),
            [], '1.1.21'
        );
    }

    public function render_shortcode($atts){
        wp_enqueue_script( 'ical-js' );
        wp_enqueue_script( 'fullcalendar' );
        wp_enqueue_script( 'fullcalendar-locale-pl' );
        wp_enqueue_script( 'rc-calendars' );
        wp_enqueue_style( 'fullcalendar-css' );

        wp_localize_script( 'rc-calendars', 'rc_params', [
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ]);
        wp_enqueue_style( 'rc-style' );

        $rooms = get_option('room_calendars_data',[]);
        $json = wp_json_encode($rooms);
        ob_start();?>
        <div id="rc-calendars" data-rooms="<?php echo esc_attr($json); ?>">
            <div class="rc-options">
                <?php foreach($rooms as $r):
                    $k=sanitize_title($r['name']);?>
                    <span class="rc-option active" data-room="<?php echo esc_attr($k);?>"><?php echo esc_html($r['name']);?></span>
                <?php endforeach;?>
            </div>
            <div id="rc-calendar-container"></div>
        </div>
        <?php return ob_get_clean();
    }

    public function ajax_fetch_ics(){
        if(!isset($_POST['url'])) wp_send_json_error('No URL');
        $res=wp_remote_get(esc_url_raw($_POST['url']),['timeout'=>15]);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success(wp_remote_retrieve_body($res));
    }
}
new Room_Calendars();
