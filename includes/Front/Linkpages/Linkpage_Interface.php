<?php
namespace Clickwhale\Front\Linkpages;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Linkpage_Interface {

    function getUrl(): string;

    function setUrl( string $url ): Linkpage;

    function getTitle(): string;

    function setTitle( string $title ): Linkpage;

    function getTemplate(): string;

    function setTemplate( string $template ): Linkpage;

    function setContent( string $content ): Linkpage;

    function setLinkpage( array $linkpage ): Linkpage;

    /**
     * Get a WP_Post build using virtual Page object
     *
     * @return WP_Post
     */
    function asWpPost(): WP_Post;
}
