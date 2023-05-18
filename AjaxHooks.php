<?php 

class AjaxHooks {

    public function __construct(){
        $this->applyActions();
    }

    public function applyActions(){
        add_action( 'wp_ajax_MyFunctions', [ $this, 'MyFunctions']);
        add_action( 'wp_ajax_nopriv_MyFunctions', [ $this, 'MyFunctions']);
    }

    public static function MyFunctions(){
        $result     = ['status' => 0, 'message' => '', 'data' => ''];
        $data       = $_POST['data_from_ajax'];

        wp_send_json( $result );
    }

}
new AjaxHooks;