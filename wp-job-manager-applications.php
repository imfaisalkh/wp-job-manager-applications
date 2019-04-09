<?php
/**
 * Plugin Name: WP Job Manager - Applications
 * Plugin URI: https://wpjobmanager.com/add-ons/applications/
 * Description: Lets candidates submit applications to jobs which are stored on the employers jobs page, rather than simply emailed. Works standalone with it's built in application form.
 * Version: 2.4.1
 * Author: Automattic
 * Author URI: https://wpjobmanager.com
 * Requires at least: 4.1
 * Tested up to: 5.0
 *
 * WPJM-Product: wp-job-manager-applications
 *
 * Copyright: 2018 Automattic
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Job_Manager_Applications class.
 */
class WP_Job_Manager_Applications {
	const JOB_MANAGER_CORE_MIN_VERSION = '1.29.0';

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Define constants
		define( 'JOB_MANAGER_APPLICATIONS_VERSION', '2.4.1' );
		define( 'JOB_MANAGER_APPLICATIONS_FILE', __FILE__ );
		define( 'JOB_MANAGER_APPLICATIONS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_APPLICATIONS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		// Check requirements
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				add_action( 'admin_notices', array( $this, 'php_admin_notice' ) );
			}
			return;
		}

		// Set up startup actions
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ), 12 );
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 13 );
		add_action( 'admin_notices', array( $this, 'version_check' ) );

		// Activate
		register_activation_hook( __FILE__, array( $this, 'install' ) );
	}

	/**
	 * Initializes plugin.
	 */
	public function init_plugin() {
		if ( ! class_exists( 'WP_Job_Manager' ) ) {
			return;
		}

		// Includes
		include_once( 'includes/class-wp-job-manager-applications-post-types.php' );
		include_once( 'includes/class-wp-job-manager-applications-apply.php' );
		include_once( 'includes/class-wp-job-manager-applications-dashboard.php' );
		include_once( 'includes/class-wp-job-manager-applications-past.php' );
		include_once( 'includes/wp-job-manager-applications-functions.php' );
		include_once( 'includes/class-wp-job-manager-applications-integration.php' );
		include_once( 'includes/class-wp-job-manager-applications-privacy.php' );

		// Init classes
		$this->post_types = new WP_Job_Manager_Applications_Post_Types();
		WP_Job_Manager_Applications_Privacy::init();

		// Add actions
		add_action( 'init', array( $this, 'load_admin' ), 12 );
		add_action( 'after_setup_theme', array( $this, 'template_functions' ) );
		add_action( 'admin_init', array( $this, 'updater' ) );
	}

	/**
	 * Checks WPJM core version.
	 */
	public function version_check() {
		if ( ! class_exists( 'WP_Job_Manager' ) || ! defined( 'JOB_MANAGER_VERSION' ) ) {
			$screen = get_current_screen();
			if ( null !== $screen && 'plugins' === $screen->id ) {
				$this->display_error( __( '<em>WP Job Manager - Applications</em> requires WP Job Manager to be installed and activated.', 'wp-job-manager-applications' ) );
			}
		} elseif (
			/**
			 * Filters if WPJM core's version should be checked.
			 *
			 * @since 2.3.0
			 *
			 * @param bool   $do_check                       True if the add-on should do a core version check.
			 * @param string $minimum_required_core_version  Minimum version the plugin is reporting it requires.
			 */
			apply_filters( 'job_manager_addon_core_version_check', true, self::JOB_MANAGER_CORE_MIN_VERSION )
			&& version_compare( JOB_MANAGER_VERSION, self::JOB_MANAGER_CORE_MIN_VERSION, '<' )
		) {
			$this->display_error( sprintf( __( '<em>WP Job Manager - Applications</em> requires WP Job Manager %s (you are using %s).', 'wp-job-manager-applications' ), self::JOB_MANAGER_CORE_MIN_VERSION, JOB_MANAGER_VERSION ) );
		}
	}

	/**
	 * Display error message notice in the admin.
	 *
	 * @param string $message
	 */
	private function display_error( $message ) {
		echo '<div class="error">';
		echo '<p>' . $message . '</p>';
		echo '</div>';
	}

	/**
	 * Output a notice when using an old non-supported version of PHP
	 */
	public function php_admin_notice() {
		echo '<div class="error">';
		echo '<p>Unfortunately, WP Job Manager Applications can not run on PHP versions older than 5.3. Read more information about <a href="http://www.wpupdatephp.com/update/">how you can update</a>.</p>';
		echo '</div>';
	}

	/**
	 * Load template functions
	 */
	public function template_functions() {
		include( 'includes/wp-job-manager-applications-template.php' );
	}

	/**
	 * Handle Updates
	 */
	public function updater() {
		if ( version_compare( JOB_MANAGER_APPLICATIONS_VERSION, get_option( 'wp_job_manager_applications_version' ), '>' ) ) {
			$this->install();
		}
	}

	/**
	 * Install
	 */
	public function install() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {
			$capabilities = $this->get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}
		}

		wp_clear_scheduled_hook( 'job_applications_purge' );
		wp_schedule_event( time(), 'daily', 'job_applications_purge' );

		update_option( 'wp_job_manager_applications_version', JOB_MANAGER_APPLICATIONS_VERSION );
	}

	/**
	 * Get capabilities
	 *
	 * @return array
	 */
	public function get_core_capabilities() {
		$capabilities     = array();
		$capability_types = array( 'job_application' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms"
			);
		}

		return $capabilities;
	}

	/**
	 * Localisation
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-applications' );
		load_textdomain( 'wp-job-manager-applications', WP_LANG_DIR . "/wp-job-manager-applications/wp-job-manager-applications-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-applications', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Init the admin area
	 */
	public function load_admin() {
		if ( is_admin() && class_exists( 'WP_Job_Manager' ) ) {
			include_once( 'includes/class-wp-job-manager-applications-admin.php' );
		}
	}
}

$GLOBALS['job_manager_applications'] = new WP_Job_Manager_Applications();
