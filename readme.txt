[=== Job Applications ===
Contributors: mikejolley, jakeom, panoskountanis, adamkheckler, drawmyface
Requires at least: 4.1
Tested up to: 5.0
Stable tag: 2.4.1
License: GNU General Public License v3.0

Lets candidates submit applications to jobs which are stored on the employers jobs page, rather than simply emailed.

= Documentation =

Usage instructions for this plugin can be found here: [https://wpjobmanager.com/document/applications/](https://wpjobmanager.com/document/applications/).

= Support Policy =

For support, please visit [https://wpjobmanager.com/support/](https://wpjobmanager.com/support/).

We will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please refer to the guide here: [http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Changelog ==

= 2.4.1 = 
* Change - Move to Select2 for enhanced select fields and support with WP Job Manager 1.32.0 asset management.
* Fix - In form editor, clear rules when switching to output content field.
* Fix - Properly format CSV export of job listing applications.
* Dev - Adds `job_application_meta` filter.

= 2.4.0 =
* Feature - Adds user data export using WordPress core's "Export Personal Data" tool.
* Feature - Adds optional deletion of applications when using WordPress core's "Erase Personal Data" tool.
* Change - Applications are now deleted when deleting a user without merging content.
* Change - Some HTML is now allowed in field labels and descriptions.

= 2.3.0 =
* Change - Adds compatibility with WP Job Manager 1.29.0 and requires it for future updates.
* Change - Allows multiple fields with the same name.
* Fix - Prevents escaping slash in field descriptions.  

= 2.2.6 =
* Fix - "Applied" will now only show on listings that a user has actually applied for. (#134) 

= 2.2.5 =
* Fix - In job listing shortcode, escaped HTML will be removed from title. WP Job Manager 1.26.3 release will add the "Applied" notice back. (#127) 

= 2.2.4 =
* Enhancement - Adds 'Rejected' to resume post status options. (#102)
* Enhancement - On 'Apply with Resume', include job title on resume drop-down. (#108)
* Fix - Application file attachments are now deleted when their applications are deleted. (#103)
* Fix - On the Application Form editor, prevent data handling rules from being used in multiple fields. (#96)
* Fix - Issues with encoding job names in emails. (#94)
* Fix - Issues with CSV encoding output. (#72; #109)
* Fix - Applications are moved to trash/restored when jobs are trashed/restored. (#68)
* Fix - Resume links are now displayed in employer notification emails. (#66)
* Fix - Use WordPress's default 'From' in email notifications. (#60; #65; #84)
* Fix - Use post status object when displaying status to allow for translation of application statuses. (#106)
* Fix - Applications can no longer be accepted for closed jobs. (#62)
* Dev - Adds filter (`job_application_hired_fills_job`) to allow applicants to be hired without filling the parent job. (#77)

= 2.2.3 =
* Fix - Use noreply address for 'from' to improve deliverability.
* Fix - Do not apply with pending resumes.

= 2.2.2 =
* Prevent multiple submission on click.
* Do not accept applications on closed jobs.
* Added [job_url] placeholder for emails.

= 2.2.1 =
* Fix IDs in resume field.

= 2.2.0 =
* If resume is required remove the 'choose a resume' option from the dropdown.
* If resume is required and user only has one resume, convert to hidden input.
* Fix saving quotes in subject lines.
* Make reset buttons reset subjects as well as form fields.
* Translate CSV file name.
* Hide hidden fields from CSV export.
* Allow custom content/text in application form.
* Mark job filled when 'hired' status chosen for applicant.
* Add note when status changes.
* Search all meta in backend search.
* Allow bulk editor for statuses.

= 2.1.6 =
* Added [job_post_meta key="x"] placeholder for email notifications.

= 2.1.5 =
* Fix user ID notice.
* Record source of application.

= 2.1.4 =
* Fix past applications output.
* Add pagination to past applicatons.

= 2.1.3 =
* Strip tags in job_title for emails

= 2.1.2 =
* Fix resume ID field when renamed.
* Added [user_id] placeholder.
* Fix mailto links subject and include body.

= 2.1.1 =
* Use get_fields() for validation method.

= 2.1.0 =
* Link candidate name to resume if set.
* Use resume manager avatar if used to apply.
* Fix rules notice.
* [company_name] placeholder.
* [application_id] placeholder.

= 2.0.5 =
* Only show resumes field when logged in.

= 2.0.4 =
* WPML config.
* Fix rule validation.

= 2.0.3 =
* Fix application form _resume_id field assignment.
* Fix from name.
* Improve save meta box.

= 2.0.2 =
* Fix - Notice when no validation rules are set.
* Tweak - Allow HTML email content.

= 2.0.1 =
* Tweak - Better checking to see if JM exists.

= 2.0.0 =
* Feature - Built in application form editor.
* Feature - Built in application form notification editor.
* Feature - Support HTML emails (detects HTML and switches automatically).
* Feature - Send an editable email to candidates after apply.
* Feature - Option to purge candidate data after X days.
* Tweak - Added template file for success message.
* Tweak - Added dedicated settings page.

= 1.7.3 =
* Workaround for already_applied_title when the_title doens't pass post_id

= 1.7.2 =
* Option to hide application form after applying to prevent multiple submissions.
* Inline validation for required fields.
* If error is triggered, keep form open.
* Added 'applied' label in list view.
* Remove add_breadcrumb_to_the_title when displaying applications.

= 1.7.1 =
* Fix status of new applications.

= 1.7.0 =
* Added filters to control job application statuses.
* Allow apply with 'hidden' resumes.

= 1.6.0 =
* Added [past_applications] shortcode.
* Strip invalid chars from file names.
* application_form_validate_fields hook.
* Only enable apply with X extensions for URL apply method if the application form is also enabled.
* Move handlers to wp_loaded hook.

= 1.5.2 =
* Allow job ID to be set when editing an application in the backend.
* create_job_application_notification_subject filter.

= 1.5.1 =
* Fix CSV download.

= 1.5.0 =
* Added tighter integration with Resume Manager (so applications through resume manager can be saved in the database). Requires Resume Manager 1.8.0.
* Integration with XING extension.
* Filter empty results from get_job_application_attachments.

= 1.4.7 =
* Removed "manage_applications" capabilitiy in favour of granular capabiltiies for all.

= 1.4.6 =
* Improved multiselect handling.

= 1.4.5 =
* Load translation files from the WP_LANG directory.
* Allow editing of before_message in sent email.
* Updated the updater class.

= 1.4.4 =
* Fixed textdomain.
* Uninstaller.
* Fixed resume IDs in select field.
* Fixed dashboard link.
* Only show resume link if the resume is published.

= 1.4.2 =
* Made 'online resume' field optional and provided a default 'N/A' value.

= 1.4.1 =
* Fixed multiple instances of uniqid() which made file upload urls differ.

= 1.4.0 =
* Don't send notification for linkedin applications.
* Removed integration with 'apply with ninja forms' due to API changes and lack of available hooks.
* Show online resume in the backend when set.

= 1.3.0 =
* HTML5 multiple file upload support.
* Multiple attachments enabled by default.
* Modifed attachment related functions.
* Requires Job Manager 1.14+
* Option to require registration to apply - application-form-login.php template controls the content.

= 1.2.0 =
* Refactor apply class to validate any custom defined fields.
* Filter send to address.
* Fix resume field validation.
* Export all custom fields in the CSV.

= 1.1.1 =
* Maintain field values on validation error.

= 1.1.0 =
* Fix issue with applications link when 'page' is greater than 1.
* Hide CV field error if field is unset.
* Attach user resume files to the notification email.
* Set Reply-To for notiifcation email.

= 1.0.3 =
* Fix updater conflict.
* Compatibility with Apply With Linkedin v2.0 (since the LinkedIn widget was deprecated)

= 1.0.2 =
* Only load JM classes when loaded.

= 1.0.1 =
* Added application count to job listing admin.

= 1.0.0 =
* First release.
