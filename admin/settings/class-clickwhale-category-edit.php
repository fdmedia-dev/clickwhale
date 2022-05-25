<?php 
class Clickwhale_Category_Edit{
    function __construct(){

    }

    public function clickwhale_validate_category($item){
        $messages = array();

        if (empty($item['title'])) $messages[] = __('Title is required', 'clickwhale');

        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }

    public function clear_category_slug($item){
        $slug = $item['slug'] ? sanitize_title($item['slug']) : sanitize_title($item['title']);

        $item['slug'] = $slug;

        return $item;
    }

}