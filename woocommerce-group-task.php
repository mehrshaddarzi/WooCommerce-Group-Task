<?php
/**
 * Plugin Name: WordPress Group Task
 * Description: A Plugin For Group Task in WooCommerce
 * Plugin URI:  https://realwp.net
 * Version:     1.0.0
 * Author:      Mehrshad Darzi
 * Author URI:  https://realwp.net
 * License:     MIT
 * Text Domain: wc-group-task
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Group_Task {


	/**
	 * Minimum PHP version required
	 *
	 * @var string
	 */
	private $min_php = '5.4.0';

	/**
	 * Use plugin's translated strings
	 *
	 * @var string
	 * @default true
	 */
	public static $use_i18n = true;

	/**
	 * List Of Class
	 * @var array
	 */
	public static $providers = array(
		'core\\Utility'
	);

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 * @status Core
	 */
	public static $plugin_url;

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 * @status Core
	 */
	public static $plugin_path;

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 * @status Core
	 */
	public static $plugin_version;

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @status Core
	 */
	protected static $_instance = null;

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 * @since   2012.09.13
	 */
	public static function instance() {
		null === self::$_instance and self::$_instance = new self;
		return self::$_instance;
	}

	/**
	 * WC_Group_Task constructor.
	 */
	public function __construct() {

		/*
		 * Check Require Php Version
		 */
		if ( version_compare( PHP_VERSION, $this->min_php, '<=' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}

		/*
		 * Define Variable
		 */
		$this->define_constants();

		/*
		 * include files
		 */
		$this->includes();

		/*
		 * init Wordpress hook
		 */
		$this->init_hooks();

		/*
		 * Plugin Loaded Action
		 */
		do_action( 'WC_Group_Task_loaded' );
	}

	/**
	 * Define Constant
	 */
	public function define_constants() {

		/*
		 * Get Plugin Data
		 */
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugin_data = get_plugin_data( __FILE__ );

		/*
		 * Set Plugin Version
		 */
		self::$plugin_version = $plugin_data['Version'];

		/*
		 * Set Plugin Url
		 */
		self::$plugin_url = plugins_url( '', __FILE__ );

		/*
		 * Set Plugin Path
		 */
		self::$plugin_path = plugin_dir_path( __FILE__ );
	}

	/**
	 * include Plugin Require File
	 */
	public function includes() {

		/*
		 * autoload plugin files
		 */
		include_once dirname( __FILE__ ) . '/inc/config/i18n.php';
		include_once dirname( __FILE__ ) . '/inc/config/install.php';
		include_once dirname( __FILE__ ) . '/inc/config/uninstall.php';
		include_once dirname( __FILE__ ) . '/inc/helper.php';
		include_once dirname( __FILE__ ) . '/inc/core/utility.php';

        /**
         * Plugin File
         */
        include_once dirname( __FILE__ ) . '/inc/task.php';

		/*
		 * Load List Of classes
		 */
		foreach ( self::$providers as $class ) {
			$class_object = '\WC_Group_Task\\' . $class;
			new $class_object;
		}

	}

	/**
	 * Used for regular plugin work.
	 *
	 * @wp-hook init Hook
	 * @return  void
	 */
	public function init_hooks() {

		/*
		 * Activation Plugin Hook
		 */
		register_activation_hook( __FILE__, array( '\WC_Group_Task\config\install', 'run_install' ) );

		/*
		 * Uninstall Plugin Hook
		 */
		register_deactivation_hook( __FILE__, array( '\WC_Group_Task\config\uninstall', 'run_uninstall' ) );

		/*
		 * Load i18n
		 */
		if ( self::$use_i18n === true ) {
			new \WC_Group_Task\config\i18n( 'wc-group-task' );
		}
	}

	/**
	 * Show notice about PHP version
	 *
	 * @return void
	 */
	function php_version_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$error = __( 'Your installed PHP Version is: ', 'wc-group-task' ) . PHP_VERSION . '. ';
		$error .= __( 'The <strong>WP Plugin</strong> plugin requires PHP version <strong>', 'wc-group-task' ) . $this->min_php . __( '</strong> or greater.', 'wc-group-task' );
		?>
        <div class="error">
            <p><?php printf( $error ); ?></p>
        </div>
		<?php
	}

}

/**
 * Main instance of WC_Group_Task.
 *
 * @since  1.1.0
 */
function WC_Group_Task() {
	return WC_Group_Task::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc-group-task'] = WC_Group_Task();
