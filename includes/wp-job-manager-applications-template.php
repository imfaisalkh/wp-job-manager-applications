<?php
if ( ! function_exists( 'job_application_meta' ) ) {

	/**
	 * Output job_application_meta
	 * @param  object $application
	 */
	function job_application_meta( $application ) {
		if ( 'job_application' === $application->post_type ) {
			/**
			 * Allows for filtering of job application meta.
			 *
			 * @since 2.4.1
			 *
			 * @params array    $meta        All post meta (see `get_post_custom`) in a multidimensional array.
			 * @params WP_Post  $application Application post object.
			 */
			$meta    = apply_filters( 'job_application_meta', get_post_custom( $application->ID ), $application );
			$hasmeta = false;
			if ( $meta ) {
				foreach ( $meta as $key => $value ) {
					if ( strpos( $key, '_' ) === 0 ) {
						continue;
					}
					if ( ! $hasmeta ) {
						echo '<dl class="job-application-meta">';
					}
					$hasmeta = true;
					echo '<dt>' . esc_html( $key ) . '</dt>';
					echo '<dd>' . make_clickable( esc_html( strip_tags( $value[0] ) ) ) . '</dd>';
				}
				if ( $hasmeta ) {
					echo '</dl>';
				}
			}
		}
	}
}

if ( ! function_exists( 'job_application_content' ) ) {

	/**
	 * Output job_application_content
	 * @param  object $application
	 */
	function job_application_content( $application ) {
		if ( 'job_application' === $application->post_type ) {
			echo apply_filters( 'job_application_content', wpautop( wptexturize( $application->post_content ) ), $application );
		}
	}
}

if ( ! function_exists( 'job_application_edit' ) ) {

	/**
	 * Output job_application_edit
	 * @param  object $application
	 */
	function job_application_edit( $application ) {
		get_job_manager_template( 'job-application-edit.php', array( 'application' => $application, 'job_id' => $application->post_parent ), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
	}
}

if ( ! function_exists( 'job_application_notes' ) ) {

	/**
	 * Output job_application_notes
	 * @param  object $application
	 */
	function job_application_notes( $application ) {
		if ( 'job_application' === $application->post_type ) {

			$args = array(
				'post_id' => $application->ID,
				'approve' => 'approve',
				'type'    => 'job_application_note',
				'order'   => 'asc',
			);

			remove_filter( 'comments_clauses', array( 'WP_Job_Manager_Applications_Dashboard', 'exclude_application_comments' ), 10, 1 );
			$notes = get_comments( $args );
			add_filter( 'comments_clauses', array( 'WP_Job_Manager_Applications_Dashboard', 'exclude_application_comments' ), 10, 1 );

			echo '<ul class="job-application-notes-list">';
			if ( $notes ) {
				foreach( $notes as $note ) {
					?>
					<li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="job-application-note">
						<div class="job-application-note-content">
							<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
						</div>
						<p class="job-application-note-meta">
							<abbr class="exact-date" title="<?php echo $note->comment_date_gmt; ?> GMT"><?php printf( __( 'added %s ago', 'wp-job-manager-applications' ), human_time_diff( strtotime( $note->comment_date_gmt ), current_time( 'timestamp', 1 ) ) ); ?></abbr>
							<?php printf( ' ' . __( 'by %s', 'wp-job-manager-applications' ), $note->comment_author ); ?>
							<a href="#" class="delete_note"><?php _e( 'Delete note', 'wp-job-manager-applications' ); ?></a>
						</p>
					</li>
					<?php
				}
			}
			echo '</ul>';
			?>
			<div class="job-application-note-add">
				<p><textarea type="text" name="job_application_note" class="input-text" cols="20" rows="5" placeholder="<?php esc_attr_e( 'Private note regarding this application', 'wp-job-manager-applications' ); ?>"></textarea></p>
				<p><input type="button" data-application_id="<?php echo absint( $application->ID ); ?>" class="button" value="<?php esc_attr_e( 'Add note', 'wp-job-manager-applications' ); ?>" /></p>
			</div>
			<?php
		}
	}
}

if ( ! function_exists( 'job_application_header' ) ) {

	/**
	 * Output job_application_header
	 * @param  object $application_id
	 */
	function job_application_header( $application ) {
		get_job_manager_template( 'job-application-header.php', array( 'application' => $application, 'job_id' => $application->post_parent ), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
	}
}

if ( ! function_exists( 'job_application_footer' ) ) {

	/**
	 * Output job_application_footer
	 * @param  object $application_id
	 */
	function job_application_footer( $application ) {
		get_job_manager_template( 'job-application-footer.php', array( 'application' => $application, 'job_id' => $application->post_parent ), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
	}
}

if ( ! function_exists( 'get_job_application_email' ) ) {

	/**
	 * Output get_job_application_email
	 * @param  object $application_id
	 */
	function get_job_application_email( $application_id ) {
		return get_post_meta( $application_id, '_candidate_email', true );
	}
}

if ( ! function_exists( 'get_job_application_attachments' ) ) {

	/**
	 * Output get_job_application_attachments
	 * @param  object $application_id
	 * @return array of attachments
	 */
	function get_job_application_attachments( $application_id ) {
		return array_filter( ( $attachments = get_post_meta( $application_id, '_attachment', true ) ) && is_array( $attachments ) ? $attachments : array( $attachments ) );
	}
}

if ( ! function_exists( 'get_job_application_attachment_name' ) ) {

	/**
	 * Output get_job_application_attachment_name
	 * @param  string $attachment URL of attachment
	 * @param  int $limit
	 * @return  string
	 */
	function get_job_application_attachment_name( $attachment, $limit = 0 ) {
		$attachment_name = basename( $attachment );
		if ( $limit && strlen( $attachment_name ) > $limit ) {
			$attachment_name = substr( $attachment_name, 0, $limit ) . '..' . substr( $attachment_name, -4 );
		}
		return $attachment_name;
	}
}

if ( ! function_exists( 'get_job_application_resume_id' ) ) {

	/**
	 * Output get_job_application_resume_id
	 * @param  object $application_id
	 */
	function get_job_application_resume_id( $application_id ) {
		return get_post_meta( $application_id, '_resume_id', true );
	}
}

if ( ! function_exists( 'get_job_application_avatar' ) ) {

	/**
	 * Output get_job_application_avatar
	 * @param  object $application_id
	 */
	function get_job_application_avatar( $application_id, $size = 42 ) {
		$email     = get_job_application_email( $application_id );
		$resume_id = get_job_application_resume_id( $application_id );

		if ( $resume_id && 'publish' === get_post_status( $resume_id ) && function_exists( 'get_the_candidate_photo' ) ) {
			return '<img src="' . esc_attr( get_the_candidate_photo( $resume_id ) ) . '" height="' . esc_attr( $size ) . '" />';
		}

		return $email ? get_avatar( $email, $size ) : '';
	}
}

if ( ! function_exists( 'get_job_application_rating' ) ) {

	/**
	 * Output get_job_application_avatar
	 * @param  object $application_id
	 */
	function get_job_application_rating( $application_id ) {
		$rating = get_post_meta( $application_id, '_rating', true );
		return is_numeric( $rating ) && $rating > 0 ? $rating : 0;
	}
}
