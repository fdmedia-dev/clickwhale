<?php

abstract class ClickWhale_Migration_Notice{
    /**
     * Initialize Hooks
     *
     * @since 1.0.0
     * @return void
     */
    abstract public static function init();

    /**
     * Showing Notice Output
     *
     * @since 1.0.0
     * @return void
     */
    abstract public function migration_notice();
    
    /**
     * Showing Notice Output
     *
     * @since 1.0.0
     * @return void
     */
    abstract public function deactive_notice();

    /**
     * Load Javascript for send ajax request
     *
     * @since 1.0.0
     * @return void
     */
    abstract public function admin_scripts();
}
