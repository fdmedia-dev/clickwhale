<?php
namespace clickwhale\includes\admin\migration;

use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Migration {

    /**
     * @var string
     */
    private string $options;

    /**
     * @var string
     */
    private string $last_migration;

    public function __construct() {
        $this->options        = 'clickwhale_tools_migration_options';
        $this->last_migration = 'clickwhale_tools_last_migration_options';

        $this->load_dependencies();

        // Actions
        add_action( 'admin_init', array( $this, 'add_migration_options' ) );
        add_action( 'admin_init', array( $this, 'add_migration_settings' ) );
        add_action( 'admin_init', array( $this, 'add_notice_migrate_options' ) );
        add_action( 'admin_init', array( $this, 'add_notice_deactive_options' ) );
        add_action( 'admin_init', array( $this, 'dispath_actions' ) );

        // add js
        add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
    }

    private function load_dependencies() {
        // load classes if available plugin is active
        foreach ( $this->available_migrations() as $item ) {
            if ( $this->check_active( $item['path'] ) ) {
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'migration/' . $item['class'] . '.php';
            }
        }
    }

    /**
     * Set target plugins data for migration
     *
     * @return array[]
     * @since 1.0.0
     */
    public static function available_migrations(): array {
        return array(
            'betterlinks'       => array(
                'slug'  => 'betterlinks',
                'name'  => 'Betterlinks',
                'path'  => 'betterlinks/betterlinks.php',
                'class' => 'BetterLinks_To_Clickwhale'
            ),
            'thirstyaffiliates' => array(
                'slug'  => 'thirstyaffiliates',
                'name'  => 'ThirstyAffiliates',
                'path'  => 'thirstyaffiliates/thirstyaffiliates.php',
                'class' => 'ThirstyAffiliates_To_Clickwhale'
            ),
            'prettylinks'       => array(
                'slug'  => 'prettylinks',
                'name'  => 'PrettyLinks',
                'path'  => 'pretty-link/pretty-link.php',
                'class' => 'PrettyLinks_To_Clickwhale'
            )
        );
    }

    /**
     * Add default options if not exists
     * @return void
     * @since 1.6.0
     */
    public function add_migration_options() {
        if ( false === get_option( $this->options ) ) {
            $defaults = array();

            foreach ( $this->available_migrations() as $item ) {
                $defaults[$item['slug'] . '_categories'] = true;
                $defaults[$item['slug'] . '_links']      = true;
            }

            add_option( $this->options, $defaults );
        }
    }

    /**
     * Add option to hide notice that some migration is available
     * @return void
     * @since 1.6.0
     */
    public function add_notice_migrate_options() {
        if ( ! get_option( 'clickwhale_hide_notice_migrate' ) ) {
            $notice_migrate_options = array();

            foreach ( $this->available_migrations() as $item ) {
                $notice_migrate_options[$item['slug']] = false;
            }

            add_option( 'clickwhale_hide_notice_migrate', $notice_migrate_options );
        }
    }

    /**
     * Add option to hide notice that some plugin can be deactivated
     * @return void
     * @since 1.6.0
     */
    public function add_notice_deactive_options() {
        if ( ! get_option( 'clickwhale_hide_notice_deactive' ) ) {
            $notice_deactive_options = array();

            foreach ( $this->available_migrations() as $item ) {
                $notice_deactive_options[$item['slug']] = true;
            }

            add_option( 'clickwhale_hide_notice_deactive', $notice_deactive_options );
        }
    }

    /**
     * Check if plugin is active
     *
     * @param string $path
     *
     * @return bool
     * @since 1.0.0
     */
    public function check_active( string $path ): bool {
        return in_array( $path, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    /**
     * Count links and categories for plugins
     *
     * @param string $plugin
     *
     * @return array
     * @since 1.0.0
     */
    public function get_plugin_data( string $plugin ): array {
        global $wpdb;
        $data = array();

        switch ( $plugin ) {
            case 'betterlinks':
                $data['links']      = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}betterlinks" ) );
                $data['categories'] = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}betterlinks_terms" ) );
                break;

            case 'thirstyaffiliates':
                $data['links']      = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type='thirstylink' AND post_status='publish'" ) );
                $data['categories'] = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy='thirstylink-category'" ) );
                break;

            case 'prettylinks':
                $data['links']      = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}prli_links" ) );
                $data['categories'] = '';
                break;
        }

        return $data;
    }

    public function dispath_actions() {
        if ( clickwhale_fs()->is_activation_mode() ) {
            return;
        }

        if ( ! clickwhale()->user->is_current_user_role_access_granted() ) {
            return;
        }

        $available_migrations = $this->available_migrations();

        foreach ( $available_migrations as $item ) {
            if ( $this->check_active( $item['path'] ) ) {
                $migration = new Clickwhale_Migration_Notice( $item['slug'], $item['name'], $item['path'] );
                $migration->init();
            }
        }
    }

    /**
     * Add settings for each plugin if it is active
     * @since 1.0.0
     */
    public function add_migration_settings() {
        foreach ( $this->available_migrations() as $item ) {
            if ( ! $this->check_active( $item['path'] ) ) {
                continue;
            }

            $options = get_option( $this->options );

            add_settings_section(
                'clickwhale_tools_migration_' . $item['slug'] . '_section',
                $item['name'],
                function () use ( $item ) {
                    $this->migration_settings_section_callback( $item );
                },
                'clickwhale_tools_' . $item['slug'] . '_migration_options'
            );

            add_settings_field(
                "{$item['slug']}_categories",
                __( 'Categories', 'clickwhale' ),
                array( $this, 'render_controls' ),
                "clickwhale_tools_{$item['slug']}_migration_options",
                "clickwhale_tools_migration_{$item['slug']}_section",
                array(
                    'control' => 'checkbox',
                    'id'      => "{$item['slug']}_categories",
                    'name'    => "$this->options[{$item['slug']}_categories]",
                    'value'   => ! empty( $options[$item['slug'] . '_categories'] ) ? 1 : 0,
                    'label'   => __( 'Migrate categories', 'clickwhale' ),
                )
            );

            add_settings_field(
                "{$item['slug']}_links",
                __( 'Links', 'clickwhale' ),
                array( $this, 'render_controls' ),
                "clickwhale_tools_{$item['slug']}_migration_options",
                "clickwhale_tools_migration_{$item['slug']}_section",
                array(
                    'control' => 'checkbox',
                    'id'      => "{$item['slug']}_links",
                    'name'    => "$this->options[{$item['slug']}_links]",
                    'value'   => ! empty( $options[$item['slug'] . '_links'] ) ? 1 : 0,
                    'label'   => __( 'Migrate links', 'clickwhale' ),
                )
            );

            register_setting(
                'clickwhale_tools_' . $item['slug'] . '_migration_options',
                'clickwhale_tools_' . $item['slug'] . '_migration_options'
            );
        }
    }

    /**
     * This function provides a simple description for the Options section
     */
    public function migration_settings_section_callback( $item ) {
        $data         = $this->get_plugin_data( $item['slug'] );
        $options      = get_option( $this->last_migration );
        $allowed_html = wp_kses_allowed_html( 'post' );
        $links           = $data['links'] ? intval( $data['links'] ) : 0;
        $links_text      = $data['links'] > 1 ? __( 'links', 'clickwhale' ) : __( 'link', 'clickwhale' );
        $categories      = $data['categories'] ? intval( $data['categories'] ) : 0;
        $categories_text = $data['categories'] > 1 ? __( 'categories', 'clickwhale' ) : __( 'category', 'clickwhale' );
        $result = $data['links'] || $data['categories']
            ? sprintf( __( 'Found %1$s %2$s and %3$s %4$s.', 'clickwhale' ),
                $categories, $categories_text, $links, $links_text ) . '<br>'
            : '';
        $result .= ! empty( $options[$item['slug'] . '_last_migration'] )
            ? sprintf( __( 'Last migration at %1$s', 'clickwhale' ),
                $options[$item['slug'] . '_last_migration'] ) . '<br>'
            : '';
        $result .= sprintf( __( 'Set what you want to migrate from %s to CLickWhale', 'clickwhale' ), $item['name'] );
        ?>
        <p><?php echo wp_kses( $result, $allowed_html ); ?></p>
        <?php
    }

    /**
     * @param $args
     * @return void
     */
    public static function render_controls( $args ) {
        echo Helper::render_control( $args );
    }

    public function admin_scripts() {

        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( sanitize_key( $_GET['page'] ) !== CLICKWHALE_SLUG . '-tools' ) {
            return;
        }
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){

                jQuery('.clickwhale-migration-section [type="checkbox"]').on('change', function(){
                    let migrationContainer = jQuery(this).closest('.clickwhale-migration-section'),
                        migrationButton = jQuery(migrationContainer).find('.button_start_migrate'),
                        checkbox = jQuery(this),
                        name = jQuery(checkbox).attr('name'),
                        matches = name.match(/\[(.*?)\]/),
                        value = jQuery(checkbox).prop('checked') ? 1 : 0;

                    jQuery(migrationButton).prop('disabled', true);

                    jQuery.post(ajaxurl, {
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'migration_to_clickwhale' ) ); ?>,
                        'action': 'clickwhale/admin/save_migration_option',
                        'name': matches[1],
                        'value': value
                    }, function(response){
                        jQuery(migrationButton).prop('disabled', false);
                    });
                });

                jQuery('.button_start_migrate').on('click', function(e){
                    e.preventDefault();

                    let migrationContainer = jQuery(this).closest('.clickwhale-migration-section'),
                        migrationButton = jQuery(this),
                        migrationSpinner = jQuery(migrationContainer).find('.spinner'),
                        migrationResult = jQuery(migrationContainer).find('.results');

                    jQuery(migrationButton).prop('disabled', true);
                    jQuery(migrationSpinner).addClass("is-active");
                    jQuery(migrationResult).removeClass("is-active").html('');

                    jQuery.post(ajaxurl, {
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'migration_to_clickwhale' ) ); ?>,
                        'action': 'clickwhale/admin/migration_to_clickwhale',
                        'migrant': migrationButton.data('migration')
                    }, function(response){
                        if (response.success){
                            let result = response.data;

                            if ('string' === typeof result.data){
                                jQuery(migrationResult).append('<p>' + result.data + '</p>');
                            } else if ('object' === typeof result.data){

                                for (let type in result.data){
                                    let categories = result.data[type].categories,
                                        links = result.data[type].links;

                                    if (categories !== null){
                                        for (let category in categories){
                                            jQuery(migrationResult).append('<p>' + categories[category] + '</p>');
                                        }
                                    }

                                    if (links !== null){
                                        for (let link in links){
                                            jQuery(migrationResult).append('<p>' + links[link] + '</p>');
                                        }
                                    }
                                }
                            }

                            jQuery(migrationResult).addClass("is-active");
                            jQuery(migrationButton).prop('disabled', false);
                            jQuery(migrationSpinner).removeClass("is-active");

                            if ('object' === typeof result.data){
                                jQuery(migrationResult).append('<br>' +
                                    '<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG ) ); ?>" class="button-primary"> ' +
                                    '<?php echo esc_js( __( 'Get started with ClickWhale now', 'clickwhale' ) ); ?>' +
                                    '</a>');
                            }
                        }
                    });
                });

                jQuery('.button_reset_migrate').on('click', function(e){
                    e.preventDefault();

                    let resetContainer = jQuery('#clickwhale-tools-migration-reset'),
                        resetButton = jQuery(this),
                        resetSpinner = jQuery(resetContainer).find('.spinner'),
                        resetResult = jQuery(resetContainer).find('.results');

                    jQuery(resetButton).prop('disabled', true);
                    jQuery(resetSpinner).addClass("is-active");

                    jQuery.post(ajaxurl, {
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'migration_reset' ) ); ?>,
                        'action': 'clickwhale/admin/migration_reset'
                    }, function(response){
                        if (response.success){
                            jQuery(resetButton).prop('disabled', false);
                            jQuery(resetSpinner).removeClass("is-active");
                            jQuery(resetResult).html(response.data);
                            location.reload();
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
