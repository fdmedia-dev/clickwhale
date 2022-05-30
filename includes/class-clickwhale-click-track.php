<?php 
class ClickWhale_Click_Track{
    /**
     * Link ID
     * 
     * @since    1.0.0
     * @access   protected
     */
    protected int $link_id;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $link_id       Link id.
	 */
    public function __construct($link_id = 0){
        $this->link_id = $link_id;
    }

    private function get_user_ip(){
        return wp_privacy_anonymize_ip($_SERVER['REMOTE_ADDR']);
    }

    private function get_user_salt(){
        return date('Y-m-d');
    }

    private function get_user_device_info(){
        $result = new WhichBrowser\Parser(getallheaders(), [ 'detectBots' => false ]);

        $resultArr              = [];
        $resultArr['browser']   = $result->browser->toString();
        $resultArr['os']        = $result->os->toString();

        return $resultArr;
    }

    private function get_user_browser_UA(){
        $browser = new WhichBrowser\Model\Browser(getallheaders(), [ 'detectBots' => false ]);
        $result = get_object_vars($browser);
        return $result['User-Agent'];
    }

    private function get_referer(){
        $referer = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'direct';
        return $referer;
    }

    private function get_host(){
        return '';
    }


    private function generate_hash(){
        $ip = $this->get_user_ip();
        $date = $this->get_user_salt();
        $browser = $this->get_user_browser_UA();
        $hash = $date . $ip . $browser;

        return hash('md5', $hash);
    }

    private function update_clicks_database(){
        global $wpdb;
		$table_name = $wpdb->prefix . 'clickwhale_clicks';
        $device     = $this->get_user_device_info();

        $item                   = [];
        $item['link_id']        = $this->link_id;
        $item['visitor_hash']   = $this->generate_hash();
        $item['ip']             = $this->get_user_ip();
        $item['browser']        = $device['browser'];
        $item['os']             = $device['os'];
        $item['referer']        = $this->get_referer();
        $item['host']           = $this->get_host();
        $item['created_at']     = date('Y-m-d H:m:s');

        $result = $wpdb->insert($table_name, $item);
    }

    public function track(){

        $this->update_clicks_database();

        //return $trackArr;
    }

}