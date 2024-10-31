<?php

class ossm_sendle_tracking_widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'ossm_sendle_tracking_widget',
            __('Sendle Tracking', 'joovii'),
            array( 'description' => __( 'Track Sendle Parcel', 'joovii' ), )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        echo $args['before_widget'];
        if ( ! empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; }
        ?>
        <div class="sendle_tracking_wrapper">
            <div class="sendle_tracking_form">
                <input type="text" name="sendle_reference" value="" placeholder="Sendle Reference"/><button>LookUp</button>
            </div>
            <div class="sendle_tracking_info"></div>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
	if ( isset( $instance[ 'title' ] ) ) { $title = $instance[ 'title' ]; }else { $title = __( 'New title', 'joovii' ); }
	?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

function ossm_sendle_tracking_load_widget() {
    if(!ossm_is_sendle_widget_enable()){
        return false;
    }
    register_widget( 'ossm_sendle_tracking_widget' );
}
add_action( 'widgets_init', 'ossm_sendle_tracking_load_widget' );

function ossm_sendle_tracking_scripts_basic(){
    wp_enqueue_style( 'sendle-tracking-style', plugins_url( '/style.css', __FILE__ ) );
    wp_register_script( 'sendle-tracking-script', plugins_url( '/scripts.js', __FILE__ ), array( 'jquery' ) );

    //wp_enqueue_script( 'sendle-tracking-script' );
    wp_register_script( 'sendle-tracking-script', 'sendletracking',array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

}
add_action( 'wp_enqueue_scripts', 'ossm_sendle_tracking_scripts_basic' );
add_action( 'wp_ajax_sendletrack', 'ossm_sendle_track_ajax' );



function ossm_sendle_track_ajax() {

	      global $wpdb; // this is how you get access to the database
	      $reference = trim( sanitize_text_field($_POST['reference']) );
        $reference = preg_replace("/[^A-Za-z0-9 ]/", '', $reference );
        if(strlen($reference) != 6){
            $error = "Invalid Sendle reference number.";
        }
        $result = array();
        if(!empty($error)){
            $result["result"] = 0;
            $result["info"] = $error;
        }else{

            $api_mode = get_option('sendle_shipping_api_mode');
            $api_id = get_option('sendle_shipping_api_id');
            $api_key= get_option('sendle_shipping_api_key');

            if(!empty($api_mode) && !empty($api_id) && !empty($api_key)){

                if($api_mode  == "live"){
                    $apiurl = "https://api.sendle.com";
                }else{
                    $apiurl = SENDLE_JOOVII_API_SANDBOX_URL;
                }

                $url = $apiurl."/api/tracking/".$reference;
                $args= array();
                $response =  wp_remote_get( $url, $args );
                $content = wp_remote_retrieve_body( $response );
                $sendle_result = json_decode( $content);

                if(isset($sendle_result->tracking_events) ){

                    if(count($sendle_result->tracking_events) > 0 ){
                        $info = "<ul>";
                        foreach($sendle_result->tracking_events as $t){
                            $sendle_time = str_replace(array("T","Z"),"",$t->scan_time);
                            $info .= "<li><div class='sendle-column-left'><div class='sendle_event_type'>".$t->event_type."</div><div class='sendle_scan_time'>".$sendle_time."</div></div><div class='sendle-column-right'><div class='sendle_description'>".$t->description."</div></div></li>";
                        }
                        $info .= "</ul>";
                        $result["result"] = 1;
                        $result["info"] = $info;
                    }else{
                        $result["result"] = 1;
                        $result["info"] = "Please try again later, we are awaiting tracking info from Sendle.";
                    }
                }else{

                    $result["result"] = 1;
                    $result["info"] = "Your requested reference number was not found.";
                }
            }else{

                $result["result"] = 1;
                $result["info"] = "Your requested reference number was not found.";
            }
        }

        echo json_encode($result);
	wp_die();
}

add_shortcode('sendle_tracking', 'ossm_sendle_tracking_shortcode');

function ossm_sendle_tracking_shortcode() {
    if(!ossm_is_sendle_widget_enable()){
        return false;
    }
?>
<div class="sendle_tracking_wrapper">
<div class="sendle_tracking_form"><input type="text" name="sendle_reference" id="sendle_reference" value="" placeholder="Sendle Reference"/><button onclick="window.open('https://track.sendle.com/tracking?ref='+getElementById('sendle_reference').value,'_blank');">LookUp</button></div>
<div class="sendle_tracking_info"></div>
</div>
<?php } ?>
