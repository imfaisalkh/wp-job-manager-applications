<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Job_Manager_Applications_Dashboard class.
 */
class WP_Job_Manager_Applications_Dashboard {

	/**
	 * __construct function.
	 */
	public function __construct() {
		add_filter( 'the_title', array( $this, 'add_breadcrumb_to_the_title' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'wp_loaded', array( $this, 'delete_handler' ) );
		add_action( 'wp_loaded', array( $this, 'edit_handler' ) );
		add_action( 'wp_loaded', array( $this, 'csv_handler' ) );
		add_filter( 'job_manager_job_dashboard_columns', array( $this, 'add_applications_columns' ) );
		add_action( 'job_manager_job_dashboard_column_applications', array( $this, 'applications_column' ) );
		add_action( 'job_manager_job_dashboard_content_show_applications', array( $this, 'show_applications' ) );

		// Ajax
		add_action( 'wp_ajax_add_job_application_note', array( $this, 'add_job_application_note' ) );
		add_action( 'wp_ajax_delete_job_application_note', array( $this, 'delete_job_application_note' ) );

		// Secure order notes
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_application_comments' ), 10, 1 );
		add_action( 'comment_feed_join', array( $this, 'exclude_application_comments_from_feed_join' ) );
		add_action( 'comment_feed_where', array( $this, 'exclude_application_comments_from_feed_where' ) );
	}

	/**
	 * Change page titles
	 */
	public function add_breadcrumb_to_the_title( $post_title ) {
		global $post;

		if ( is_main_query() && is_page() && strstr( $post->post_content, '[job_dashboard' ) && in_the_loop() ) {
			remove_filter( 'the_title', array( $this, 'add_breadcrumb_to_the_title' ) );
			if ( ! empty( $_GET['action'] ) && 'show_applications' === $_GET['action'] ) {
				$job_id = absint( $_GET['job_id'] );
				if ( 'job_listing' === get_post_type( $job_id ) ) {
					$post_title = __( 'Job Applications', 'wp-job-manager-applications' ) . ' &laquo; <a href="' . get_permalink( $post->ID ) . '">' . $post_title . '</a>';
				}
			}
		}

		return $post_title;
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		wp_register_script( 'wp-job-manager-applications-dashboard', JOB_MANAGER_APPLICATIONS_PLUGIN_URL . '/assets/js/application-dashboard.min.js', array( 'jquery' ), JOB_MANAGER_APPLICATIONS_VERSION, true );

		wp_localize_script( 'wp-job-manager-applications-dashboard', 'job_manager_application', array(
			'i18n_confirm_delete'         => __( 'Are you sure you want to delete this? There is no undo.', 'wp-job-manager-applications' ),
			'i18n_toggle_content'         => __( 'Details', 'wp-job-manager-applications' ),
			'i18n_toggle_notes'           => __( 'Notes', 'wp-job-manager-applications' ),
			'i18n_hide'                   => __( 'Hide', 'wp-job-manager-applications' ),
			'ajax_url'                    => admin_url('admin-ajax.php'),
			'job_application_notes_nonce' => wp_create_nonce( "job-application-notes" ),
		) );

		wp_enqueue_style( 'wp-job-manager-applications-frontend', JOB_MANAGER_APPLICATIONS_PLUGIN_URL . '/assets/css/frontend.css' );
	}

	/**
	 * See if user can edit the application
	 * @return bool
	 */
	public function can_edit_application( $application_id ) {
		$application = get_post( $application_id );

		if ( ! $application ) {
			return false;
		}

		$job = get_post( $application->post_parent );

		// Permissions
		if ( ! $job || ! $application || $application->post_type !== 'job_application' || $job->post_type !== 'job_listing' || !job_manager_user_can_edit_job( $job->ID ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Edit an application
	 */
	public function edit_handler() {
		if ( ! empty( $_POST['wp_job_manager_edit_application'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'edit_job_application' ) ) {
			global $wp_post_statuses;

			$application_id = absint( $_POST['application_id'] );

			if ( ! $this->can_edit_application( $application_id ) ) {
				return;
			}

			$application_status = sanitize_text_field( $_POST['application_status'] );
			$application_rating = floatval( $_POST['application_rating'] );
			$application_rating = $application_rating < 0 ? 0 : $application_rating;
			$application_rating = $application_rating > 5 ? 5 : $application_rating;

			update_post_meta( $application_id, '_rating', $application_rating );

			if ( array_key_exists( $application_status, $wp_post_statuses ) ) {
				wp_update_post( array(
					'ID'          => $application_id,
					'post_status' => $application_status
				) );
			}
		}
	}

	/**
	 * Delete an application
	 */
	public function delete_handler() {
		if ( ! empty( $_GET['delete_job_application'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_job_application' ) ) {
			$application_id = absint( $_GET['delete_job_application'] );

			if ( ! $this->can_edit_application( $application_id ) ) {
				return;
			}

			wp_delete_post( $application_id, true );
		}
	}

	/**
	 * Download a CSV
	 */
	public function csv_handler() {
		if ( ! empty( $_GET['download-csv'] ) ) {
			$job_id = absint( $_REQUEST['job_id'] );
			$job    = get_post( $job_id );

			// Permissions
			if ( ! job_manager_user_can_edit_job( $job )  ) {
				return;
			}

			$args = apply_filters( 'job_manager_job_applications_args', array(
				'post_type'           => 'job_application',
				'post_status'         => array_merge( array_keys( get_job_application_statuses() ), array( 'publish' ) ),
				'ignore_sticky_posts' => 1,
				'posts_per_page'      => -1,
				'post_parent'         => $job_id
			) );

			// Filters
			$application_status  = ! empty( $_GET['application_status'] ) ? sanitize_text_field( $_GET['application_status'] ) : '';
			$application_orderby = ! empty( $_GET['application_orderby'] ) ? sanitize_text_field( $_GET['application_orderby'] ) : '';

			if ( $application_status ) {
				$args['post_status'] = $application_status;
			}

			switch ( $application_orderby ) {
				case 'name' :
					$args['order']   = 'ASC';
					$args['orderby'] = 'post_title';
				break;
				case 'rating' :
					$args['order']    = 'DESC';
					$args['orderby']  = 'meta_value';
					$args['meta_key'] = '_rating';
				break;
				default :
					$args['order']   = 'DESC';
					$args['orderby'] = 'date';
				break;
			}

			$applications = get_posts( $args );

			@set_time_limit(0);
			if ( function_exists( 'apache_setenv' ) ) {
				@apache_setenv( 'no-gzip', 1 );
			}
			@ini_set('zlib.output_compression', 0);

			header( 'Content-Type: text/csv; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename=' . __( 'applications', 'wp-job-manager-applications' ) . '.csv' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$fp  = fopen('php://output', 'w');
			$row = array(
				__( 'Application date', 'wp-job-manager-applications' ),
				__( 'Application status', 'wp-job-manager-applications' ),
	   			__( 'Applicant name', 'wp-job-manager-applications' ),
	   			__( 'Applicant email', 'wp-job-manager-applications' ),
	   			__( 'Job applied for', 'wp-job-manager-applications' ),
	   			__( 'Attachment', 'wp-job-manager-applications' ),
	   			__( 'Applicant message', 'wp-job-manager-applications' ),
	   			__( 'Rating', 'wp-job-manager-applications' )
	   		);

	   		// Other custom fields
	   		$custom_fields = array();

	   		foreach ( $applications as $application ) {
	   			$custom_fields = array_merge( $custom_fields, array_keys( get_post_custom( $application->ID ) ) );
	   		}

	   		$custom_fields = array_unique( $custom_fields );
	   		$custom_fields = array_diff( $custom_fields, array(
	   			'_edit_lock',
	   			'_attachment',
	   			'_attachment_file',
	   			'_job_applied_for',
	   			'_candidate_email',
	   			'_candidate_user_id',
	   			'_rating',
				'_application_source',
				'_secret_dir'
	   		) );

	   		foreach ( $custom_fields as $custom_field ) {
	   			$row[] = $custom_field;
	   		}

			fputcsv( $fp, $row );

	   		foreach ( $applications as $application ) {
	   			$row   = array();
	   			$row[] = date_i18n( get_option( 'date_format' ), strtotime( $application->post_date ) );
	   			$row[] = $application->post_status;
	   			$row[] = $application->post_title;
	   			$row[] = get_job_application_email( $application->ID );
	   			$row[] = get_the_title( $application->post_parent );
	   			$row[] = implode( '; ', get_job_application_attachments( $application->ID ) );
	   			$row[] = $application->post_content;
	   			$row[] = get_job_application_rating( $application->ID );

	   			foreach ( $custom_fields as $custom_field ) {
	   				$custom_field_value = get_post_meta( $application->ID, $custom_field, true );;
	   				if ( is_array( $custom_field_value ) ) {
	   					$custom_field_value = wp_json_encode( $custom_field_value );
					}
					$row[] = $custom_field_value;
	   			}

				fputcsv( $fp, $row );
	   		}

			fclose( $fp );
			exit;
		}
	}

	/**
	 * Add a new column to the job dashboard
	 * @param $columns array
	 * @return array
	 */
	public function add_applications_columns( $columns ) {
		$columns['applications'] = __( 'Applications', 'wp-job-manager-applications' );
		return $columns;
	}

	/**
	 * Show the count of applications in the job dashboard
	 * @param  WP_Post Job
	 */
	public function applications_column( $job ) {
		global $post;

		echo ( $count = get_job_application_count( $job->ID ) ) ? '<a href="' . add_query_arg( array( 'action' => 'show_applications', 'job_id' => $job->ID ), get_permalink( $post->ID ) ) . '">' . $count . '</a>' : '&ndash;';
	}

	/**
	 * Show applications on the job dashboard
	 */
	public function show_applications( $atts ) {
		$job_id = absint( $_REQUEST['job_id'] );
		$job    = get_post( $job_id );

		extract( shortcode_atts( array(
			'posts_per_page' => '20',
		), $atts ) );

		remove_filter( 'the_title', array( $this, 'add_breadcrumb_to_the_title' ) );

		// Permissions
		if ( ! job_manager_user_can_edit_job( $job_id)  ) {
			_e( 'You do not have permission to view this job.', 'wp-job-manager-applications' );
			return;
		}

		wp_enqueue_script( 'wp-job-manager-applications-dashboard' );

		$args = apply_filters( 'job_manager_job_applications_args', array(
			'post_type'           => 'job_application',
			'post_status'         => array_diff( array_merge( array_keys( get_job_application_statuses() ), array( 'publish' ) ), array( 'archived' ) ),
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $posts_per_page,
			'offset'              => ( max( 1, get_query_var('paged') ) - 1 ) * $posts_per_page,
			'post_parent'         => $job_id
		) );

		// Filters
		$application_status  = ! empty( $_GET['application_status'] ) ? sanitize_text_field( $_GET['application_status'] ) : '';
		$application_orderby = ! empty( $_GET['application_orderby'] ) ? sanitize_text_field( $_GET['application_orderby'] ) : '';

		if ( $application_status ) {
			$args['post_status'] = $application_status;
		}

		switch ( $application_orderby ) {
			case 'name' :
				$args['order']   = 'ASC';
				$args['orderby'] = 'post_title';
			break;
			case 'rating' :
				$args['order']    = 'DESC';
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_rating';
			break;
			default :
				$args['order']   = 'DESC';
				$args['orderby'] = 'date';
			break;
		}

		$applications = new WP_Query;

		$columns = apply_filters( 'job_manager_job_applications_columns', array(
			'name'  => __( 'Name', 'wp-job-manager-applications' ),
			'email' => __( 'Email', 'wp-job-manager-applications' ),
			'date'  => __( 'Date Received', 'wp-job-manager-applications' ),
		) );

		get_job_manager_template( 'job-applications.php', array( 'applications' => $applications->query( $args ), 'job_id' => $job_id, 'max_num_pages' => $applications->max_num_pages, 'columns' => $columns, 'application_status' => $application_status, 'application_orderby' => $application_orderby ), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Add note via ajax
	 */
	public function add_job_application_note() {
		check_ajax_referer( 'job-application-notes', 'security' );

		$application_id = absint( $_POST['application_id'] );
		$application    = get_post( $application_id );
		$note           = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );

		if ( $application_id > 0 && $this->can_edit_application( $application_id ) ) {

			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
			$comment_post_ID      = $application_id;
			$comment_author_url   = '';
			$comment_content      = $note;
			$comment_agent        = 'WP Job Manager';
			$comment_type         = 'job_application_note';
			$comment_parent       = 0;
			$comment_approved     = 1;
			$commentdata          = apply_filters( 'job_application_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), $application_id );
			$comment_id           = wp_insert_comment( $commentdata );

			echo '<li rel="' . esc_attr( $comment_id ) . '" class="job-application-note"><div class="job-application-note-content">';
			echo wpautop( wptexturize( $note ) );
			echo '</div><p class="job-application-note-meta"><a href="#" class="delete_note">' . __( 'Delete note', 'wp-job-manager-applications' ) . '</a></p>';
			echo '</li>';
		}
		die();
	}

	/**
	 * Delete note via ajax
	 */
	public function delete_job_application_note() {
		check_ajax_referer( 'job-application-notes', 'security' );

		if ( $note_id = absint( $_POST['note_id'] ) ) {
			$note           = get_comment( $note_id );
			$application_id = absint( $note->comment_post_ID );
			$application    = get_post( $application_id );
			if ( $application_id > 0 && $this->can_edit_application($application_id) ) {
				wp_delete_comment( $note_id );
			}
		}
		die();
	}

	/**
	 * Exclude application comments from queries and RSS
	 *
	 * This code should exclude comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded
	 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe.
	 *
	 * The frontend view order pages get around this filter by using remove_filter.
	 *
	 * @param array $clauses
	 * @return array
	 */
	public static function exclude_application_comments( $clauses ) {
		global $wpdb, $typenow, $pagenow;

		if ( is_admin() && $typenow == 'job_application' ) {
			return $clauses;
		}

		if ( ! $clauses['join'] ) {
			$clauses['join'] = '';
		}

		if ( ! strstr( $clauses['join'], "JOIN $wpdb->posts" ) ) {
			$clauses['join'] .= " LEFT JOIN $wpdb->posts ON comment_post_ID = $wpdb->posts.ID ";
		}

		if ( $clauses['where'] ) {
			$clauses['where'] .= ' AND ';
		}

		$clauses['where'] .= " $wpdb->posts.post_type NOT IN ('job_application') ";

		return $clauses;
	}

	/**
	 * Exclude comments from queries and RSS
	 *
	 * @param string $join
	 * @return string
	 */
	public function exclude_application_comments_from_feed_join( $join ) {
		global $wpdb;

	    if ( ! strstr( $join, $wpdb->posts ) ) {
	    	$join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";
	    }

	    return $join;
	}

	/**
	 * Exclude order comments from queries and RSS
	 *
	 * @param string $where
	 * @return string
	 */
	public function exclude_application_comments_from_feed_where( $where ) {
		global $wpdb;

	    if ( $where ) {
	    	$where .= ' AND ';
	    }

		$where .= " $wpdb->posts.post_type NOT IN ('job_application') ";

	    return $where;
	}
}
new WP_Job_Manager_Applications_Dashboard();
