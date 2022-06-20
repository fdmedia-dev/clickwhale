<?php 
class ClickWhale_Click_Track{
    /**
     * Link ID
     * 
     * @since    1.0.0
     * @access   protected
     */
    protected $link_id;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $link_id       Link id.
	 */
    public function __construct($link_id = 0){
        $this->link_id = (int) $link_id;
    }

    private function get_user_ip(){
        return wp_privacy_anonymize_ip($_SERVER['REMOTE_ADDR']);
    }

    private function get_user_salt(){
        return date('Y-m-d');
    }

    private function get_user_device_info(){
        $result = new Clickwhale\Vendor\WhichBrowser\Parser(getallheaders(), [ 'detectBots' => true ]);

        $resultArr              = [];
        //$resultArr['browser']   = $result->browser->toString(); // Chrome 27.0
        $resultArr['os']        = $result->os->toString();      // Windows 10
        $resultArr['type']      = $result->device->type;        // desktop

        return $resultArr['type'] !== 'bot' ? $resultArr : false;
    }

    private function get_user_agent_string(){
        $browser    = new Clickwhale\Vendor\WhichBrowser\Model\Browser(getallheaders(), [ 'detectBots' => true ]);
        $result     = get_object_vars($browser);

        return $result['User-Agent'];
    }

    private function get_link_referer(){
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        return $referer;
    }

    private function generate_hash(){
        $ip         = $this->get_user_ip();
        $date       = $this->get_user_salt();
        $browser    = $this->get_user_agent_string();
        $hash       = $date . $ip . $browser;

        return hash('md5', $hash);
    }

    private function update_clicks_database(){
        global $wpdb;

		$table_name = $wpdb->prefix . 'clickwhale_clicks';
        $device     = $this->get_user_device_info();
        //var_dump($device);
        if($device) {

            $id     = $this->link_id;
            $hash   = $this->generate_hash();
            //$check  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE link_id=$id AND visitor_hash='$hash'");

            //if(!$check){
                $item                   = [];
                $item['link_id']        = $id;
                $item['visitor_hash']   = $hash;
                $item['browser']        = $this->get_user_agent_string();
                $item['os']             = $device['os'];
                $item['device']         = $device['type'];
                $item['referer']        = $this->get_link_referer();
                $item['created_at']     = date('Y-m-d H:m:s');

                $result = $wpdb->insert($table_name, $item);
            //}
        } else {
            return false;
        }
    }

    public function track(){

        $this->update_clicks_database();

    }

}