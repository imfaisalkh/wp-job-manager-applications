<?php

/**
 * WP_Job_Manager_Applications_Form_Editor class.
 */
class WP_Job_Manager_Applications_Form_Editor {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );
	}

	/**
	 * Add form editor menu item
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=job_application', __( 'Application Form', 'wp-job-manager-applications' ),  __( 'Application Form', 'wp-job-manager-applications' ) , 'manage_options', 'job-applications-form-editor', array( $this, 'output' ) );
	}

	/**
	 * Register scripts
	 */
	public function admin_enqueue_scripts() {
		$current_screen = get_current_screen();
		if ( 'job_application_page_job-applications-form-editor' !== $current_screen->id ) {
			return;
		}

		$form_editor_deps = array( 'jquery', 'jquery-ui-sortable' );
		if ( wp_script_is( 'select2', 'registered' ) ) {
			$form_editor_deps[] = 'select2';
			wp_enqueue_style( 'select2' );
		} elseif (
			file_exists( JOB_MANAGER_PLUGIN_DIR . '/assets/js/jquery-chosen/chosen.jquery.min.js' )
			&& file_exists( JOB_MANAGER_PLUGIN_DIR . '/assets/css/chosen.css' )
		) {
			wp_register_script( 'chosen', JOB_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', array( 'jquery' ), '1.1.0', true );
			wp_enqueue_style( 'chosen', JOB_MANAGER_PLUGIN_URL . '/assets/css/chosen.css' );
			$form_editor_deps[] = 'chosen';
		}

		wp_register_script( 'wp-job-manager-applications-form-editor', plugins_url( '/assets/js/form-editor.min.js', JOB_MANAGER_APPLICATIONS_FILE ), $form_editor_deps, JOB_MANAGER_APPLICATIONS_VERSION, true );
		wp_localize_script( 'wp-job-manager-applications-form-editor', 'wp_job_manager_applications_form_editor', array(
			'cofirm_delete_i18n' => __( 'Are you sure you want to delete this row?', 'wp-job-manager-applications' ),
			'cofirm_reset_i18n'  => __( 'Are you sure you want to reset your changes? This cannot be undone.', 'wp-job-manager-applications' ),
			'is_rtl'             => is_rtl() ? 1 : 0,
		) );
	}

	/**
	 * Output the screen
	 */
	public function output() {
		wp_enqueue_script( 'wp-job-manager-applications-form-editor' );

		$tabs = array(
			'fields'                 => __( 'Form Fields', 'wp-job-manager-applications' ),
			'employer-notification'  => 'Employer Notification',
			'candidate-notification' => 'Candidate Notification'
		);

		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'fields';

		?>
		<div class="wrap wp-job-manager-applications-form-editor">
			<h2 class="nav-tab-wrapper">
				<?php
				foreach( $tabs as $key => $value ) {
					$active = ( $key == $tab ) ? 'nav-tab-active' : '';
					echo '<a class="nav-tab ' . $active . '" href="' . admin_url( 'edit.php?post_type=job_application&page=job-applications-form-editor&tab=' . esc_attr( $key ) ) . '">' . esc_html( $value ) . '</a>';
				}
				?>
			</h2>
			<form method="post" id="mainform" action="edit.php?post_type=job_application&amp;page=job-applications-form-editor&amp;tab=<?php echo esc_attr( $tab ); ?>">
				<?php
				switch ( $tab ) {
					case 'employer-notification' :
						$this->employer_notification_editor();
					break;
					case 'candidate-notification' :
						$this->candidate_notification_editor();
					break;
					default :
						$this->form_editor();
					break;
				}
				?>
				<?php wp_nonce_field( 'save-' . $tab ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output the form editor
	 */
	private function form_editor() {
		if ( ! empty( $_GET['reset-fields'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
			delete_option( 'job_application_form_fields' );
			echo '<div class="updated"><p>' . __( 'The fields were successfully reset.', 'wp-job-manager-applications' ) . '</p></div>';
		}

		if ( ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-fields' )  ) {
			echo $this->form_editor_save();
		}

		$fields      = get_job_application_form_fields();

		/**
		 * Filters rules that can only be used once per form.
		 *
		 * @since 2.2.4
		 *
		 * @param array $unique_rules Rule key values that should be unique.
		 */
		$unique_rules = apply_filters( 'job_application_form_unique_field_rules', array(
			'from_name',
			'from_email',
			'message',
			'attachment',
		) );

		/**
		 * Returns the rules that can be used on application form fields.
		 *
		 * @since 2.0.1
		 *
		 * @param array $rules
		 */
		$field_rules = apply_filters( 'job_application_form_field_rules', array(
			__( 'Validation', 'wp-job-manager-applications' ) => array(
				'required' => __( 'Required', 'wp-job-manager-applications' ),
				'email'    => __( 'Email', 'wp-job-manager-applications' ),
				'numeric'  => __( 'Numeric', 'wp-job-manager-applications' )
			),
			__( 'Data Handling', 'wp-job-manager-applications' ) => array(
				'from_name'  => __( 'From Name', 'wp-job-manager-applications' ),
				'from_email' => __( 'From Email', 'wp-job-manager-applications' ),
				'message'    => __( 'Message', 'wp-job-manager-applications' ),
				'attachment' => __( 'Attachment', 'wp-job-manager-applications' )
			)
		) );

		/**
		 * Returns the field types that can be used on application forms.
		 *
		 * @sicne 2.0.1
		 *
		 * @param array $field_types
		 */
		$field_types = apply_filters( 'job_application_form_field_types', array(
			'text'           => __( 'Text', 'wp-job-manager-applications' ),
			'textarea'       => __( 'Textarea', 'wp-job-manager-applications' ),
			'file'           => __( 'File', 'wp-job-manager-applications' ),
			'select'         => __( 'Select', 'wp-job-manager-applications' ),
			'multiselect'    => __( 'Multiselect', 'wp-job-manager-applications' ),
			'checkbox'       => __( 'Checkbox', 'wp-job-manager-applications' ),
			'resumes'        => __( 'Resume', 'wp-job-manager-applications' ),
			'output-content' => __( 'Output content', 'wp-job-manager-applications' ),
		) );

		if ( ! function_exists( 'get_resume_share_link' ) ) {
			unset( $field_types['resumes'] );
		}
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th width="1%">&nbsp;</th>
					<th><?php _e( 'Field Label', 'wp-job-manager-applications' ); ?></th>
					<th width="1%"><?php _e( 'Type', 'wp-job-manager-applications' ); ?></th>
					<th><?php _e( 'Description', 'wp-job-manager-applications' ); ?></th>
					<th><?php _e( 'Placeholder / Options', 'wp-job-manager-applications' ); ?></th>
					<th width="1%"><?php _e( 'Validation / Rules', 'wp-job-manager-applications' ); ?></th>
					<th width="1%" class="field-actions">&nbsp;</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th colspan="4">
						<a class="button add-field" href="#"><?php _e( 'Add field', 'wp-job-manager-applications' ); ?></a>
					</th>
					<th colspan="4" class="save-actions">
						<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-fields', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-job-manager-applications' ); ?></a>
						<input type="submit" class="save-fields button-primary" value="<?php _e( 'Save Changes', 'wp-job-manager-applications' ); ?>" />
					</th>
				</tr>
			</tfoot>
			<tbody id="form-fields" data-field="<?php
				ob_start();
				$index     = -1;
				$field_key = '';
				$field     = array(
					'type'        => 'text',
					'label'       => '',
					'placeholder' => ''
				);
				include( 'views/html-form-field-editor-row.php' );
				echo esc_attr( ob_get_clean() );
			?>"><?php
				foreach ( $fields as $field_key => $field ) {
					$index ++;
					include( 'views/html-form-field-editor-row.php' );
				}
			?></tbody>
		</table>
		<?php
	}

	/**
	 * Save the form fields
	 */
	private function form_editor_save() {
		$field_types          = ! empty( $_POST['field_type'] ) ? array_map( 'sanitize_text_field', $_POST['field_type'] ) : array();
		$field_labels         = ! empty( $_POST['field_label'] ) ? array_map( 'wp_kses_post', $_POST['field_label'] ) : array();
		$field_descriptions   = ! empty( $_POST['field_description'] ) ? array_map( 'wp_kses_post', wp_unslash( $_POST['field_description'] ) ) : array();
		$field_placeholder    = ! empty( $_POST['field_placeholder'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['field_placeholder'] ) ) : array();
		$field_options        = ! empty( $_POST['field_options'] ) ? array_map( 'sanitize_text_field', $_POST['field_options'] ) : array();
		$field_multiple_files = ! empty( $_POST['field_multiple_files'] ) ? array_map( 'sanitize_text_field', $_POST['field_multiple_files'] ) : array();
		$field_rules          = ! empty( $_POST['field_rules'] ) ? $this->sanitize_array( $_POST['field_rules'] ) : array();
		$new_fields           = array();
		$index                = 0;

		foreach ( $field_labels as $key => $field ) {
			if ( empty( $field_labels[ $key ] ) ) {
				continue;
			}
			$field_name                = sanitize_title( $field_labels[ $key ] );
			$options                   = ! empty( $field_options[ $key ] ) ? array_map( 'sanitize_text_field', explode( '|', $field_options[ $key ] ) ) : array();

			$new_field                       = array();
			$new_field['label']              = $field_labels[ $key ];
			$new_field['type']               = $field_types[ $key ];
			$new_field['required']           = ! empty( $field_rules[ $key ] ) ? in_array( 'required', $field_rules[ $key ] ) : false;
			$new_field['options']            = $options ? array_combine( $options, $options ) : array();
			$new_field['placeholder']        = $field_placeholder[ $key ];
			$new_field['description']        = $field_descriptions[ $key ];
			$new_field['priority']           = $index ++;
			$new_field['multiple']           = isset( $field_multiple_files[ $key ] );
			$new_field['rules']              = ! empty( $field_rules[ $key ] ) ? $field_rules[ $key ] : array();
			if ( isset( $new_fields[ $field_name ] ) ) {
				// Generate a unique field name by appending a number to the existing field name.
				// Assumes no more than 100 fields with the same name would be needed? Otherwise it will override the field.
				$counter = 1;
				while ( $counter <= 100 ) {
					$candidate = $field_name . '-' . $counter;
					if ( ! isset( $new_fields[ $candidate ] ) ) {
						$field_name = $candidate;
						break;
					}
					$counter++;
				}
			}
			$new_fields[ $field_name ]       = $new_field;
		}

		$result = update_option( 'job_application_form_fields', $new_fields );

		if ( true === $result ) {
			echo '<div class="updated"><p>' . __( 'The fields were successfully saved.', 'wp-job-manager-applications' ) . '</p></div>';
		}
	}

	/**
	 * Sanitize a 2d array
	 * @param  array $array
	 * @return array
	 */
	private function sanitize_array( $input ) {
		if ( is_array( $input ) ) {
			foreach ( $input as $k => $v ) {
				$input[ $k ] = $this->sanitize_array( $v );
			}
			return $input;
		} else {
			return sanitize_text_field( $input );
		}
	}

	/**
	 * Email editor
	 */
	private function employer_notification_editor() {
		if ( ! empty( $_GET['reset-email'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
			delete_option( 'job_application_email_content' );
			delete_option( 'job_application_email_subject' );
			echo '<div class="updated"><p>' . __( 'The email was successfully reset.', 'wp-job-manager-applications' ) . '</p></div>';
		}

		if ( ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-employer-notification' )  ) {
			echo $this->employer_notification_editor_save();
		}
		?>
		<p><?php _e( 'Below you will find the email that is sent to an employer after a candidate submits an application.', 'wp-job-manager-applications' ); ?></p>
		<div class="wp-job-applications-email-content-wrapper">
			<div class="wp-job-applications-email-content">
				<p>
					<input type="text" name="email-subject" value="<?php echo esc_attr( get_job_application_email_subject() ); ?>" placeholder="<?php echo esc_attr( __( 'Subject', 'wp-job-manager-applications' ) ); ?>" />
				</p>
				<p>
					<textarea name="email-content" cols="71" rows="10"><?php echo esc_textarea( get_job_application_email_content() ); ?></textarea>
				</p>
			</div>
			<div class="wp-job-applications-email-content-tags">
				<p><?php _e( 'The following tags can be used to add content dynamically:', 'wp-job-manager-applications' ); ?></p>
				<ul>
					<?php foreach ( get_job_application_email_tags() as $tag => $name ) : ?>
						<li><code>[<?php echo esc_html( $tag ); ?>]</code> - <?php echo wp_kses_post( $name ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php _e( 'All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[job_title prefix="Job Title: " suffix="."]</code>', 'wp-job-manager-applications' ); ?></p>
			</div>
		</div>
		<p class="submit-email save-actions">
			<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-email', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-job-manager-applications' ); ?></a>
			<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-job-manager-applications' ); ?>" />
		</p>
		<?php
	}

	/**
	 * Save the email
	 */
	private function employer_notification_editor_save() {
		$email_content = wp_unslash( $_POST['email-content'] );
		$email_subject = sanitize_text_field( wp_unslash( $_POST['email-subject'] ) );
		$result        = update_option( 'job_application_email_content', $email_content );
		$result2       = update_option( 'job_application_email_subject', $email_subject );

		if ( true === $result || true === $result2 ) {
			echo '<div class="updated"><p>' . __( 'The email was successfully saved.', 'wp-job-manager-applications' ) . '</p></div>';
		}
	}

	/**
	 * Email editor
	 */
	private function candidate_notification_editor() {
		if ( ! empty( $_GET['reset-email'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
			delete_option( 'job_application_candidate_email_content' );
			delete_option( 'job_application_candidate_email_subject' );
			echo '<div class="updated"><p>' . __( 'The email was successfully reset.', 'wp-job-manager-applications' ) . '</p></div>';
		}

		if ( ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-candidate-notification' )  ) {
			echo $this->candidate_notification_editor_save();
		}
		?>
		<p><?php _e( 'Below you will find the email that is sent to a candidate after submitting an application. Leave blank to disable.', 'wp-job-manager-applications' ); ?></p>
		<div class="wp-job-applications-email-content-wrapper">
			<div class="wp-job-applications-email-content">
				<p>
					<input type="text" name="email-subject" value="<?php echo esc_attr( get_job_application_candidate_email_subject() ); ?>" placeholder="<?php echo esc_attr( __( 'Subject', 'wp-job-manager-applications' ) ); ?>" />
				</p>
				<p>
					<textarea name="email-content" cols="71" rows="10" placeholder="<?php _e( 'N/A', 'wp-job-manager-applications' ); ?>"><?php echo esc_textarea( get_job_application_candidate_email_content() ); ?></textarea>
				</p>
			</div>
			<div class="wp-job-applications-email-content-tags">
				<p><?php _e( 'The following tags can be used to add content dynamically:', 'wp-job-manager-applications' ); ?></p>
				<ul>
					<?php foreach ( get_job_application_email_tags() as $tag => $name ) : ?>
						<li><code>[<?php echo esc_html( $tag ); ?>]</code> - <?php echo wp_kses_post( $name ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php _e( 'All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[job_title prefix="Job Title: " suffix="."]</code>', 'wp-job-manager-applications' ); ?></p>
			</div>
		</div>
		<p class="submit-email save-actions">
			<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-email', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-job-manager-applications' ); ?></a>
			<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-job-manager-applications' ); ?>" />
		</p>
		<?php
	}

	/**
	 * Save the email
	 */
	private function candidate_notification_editor_save() {
		$email_content = wp_unslash( $_POST['email-content'] );
		$email_subject = sanitize_text_field( wp_unslash( $_POST['email-subject'] ) );
		$result        = update_option( 'job_application_candidate_email_content', $email_content );
		$result2       = update_option( 'job_application_candidate_email_subject', $email_subject );

		if ( true === $result || true === $result2 ) {
			echo '<div class="updated"><p>' . __( 'The email was successfully saved.', 'wp-job-manager-applications' ) . '</p></div>';
		}
	}
}

new WP_Job_Manager_Applications_Form_Editor();
