<?php 
class Clickwhale_Link_Edit{
    function __construct(){

    }

    public function clickwhale_validate_link($item){
        $messages = array();

        if (empty($item['link_title'])) $messages[] = __('Title is required', 'clickwhale');
        if (empty($item['link_url'])) $messages[] = __('Target URL is required', 'clickwhale');
        if (empty($item['link_slug'])) $messages[] = __('Slug is required', 'clickwhale');
        if (!ctype_digit($item['link_redirection'])) $messages[] = __('Wrong redirection code', 'clickwhale');
        if (!empty($item['link_redirection']) && !absint(intval($item['link_redirection'])))  $messages[] = __('Redirection code can not be less than zero');
        if (!empty($item['link_redirection']) && !preg_match('/[0-9]+/', $item['link_redirection'])) $messages[] = __('Redirection code must be number');
        if (empty($item['link_slug'])) $messages[] = __('Slug is required', 'clickwhale');
        //if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'clickwhale');

        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }

}