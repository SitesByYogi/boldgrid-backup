<?php
/**
 * Archive Details class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Archive Details Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Details {

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Render the details page of an archive.
	 *
	 * @since 1.5.1
	 */
	public function render_archive() {
		wp_enqueue_style(
			'boldgrid-backup-admin-zip-browser',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-zip-browser.css',
			array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		wp_register_script(
			'boldgrid-backup-admin-archive-details',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-archive-details.js',
			array( 'jquery', ),
			BOLDGRID_BACKUP_VERSION
		);
		$translations = array(
			'uploading' => __( 'Uploading', 'boldgrid-backup' ),
			'uploaded' => __( 'Uploaded', 'boldgrid-backup' ),
			'failUpload' => __( 'Unable to upload backup file.', 'boldgrid-backup' ),
		);
		wp_localize_script( 'boldgrid-backup-admin-archive-details', 'boldgrid_backup_archive_details', $translations );
		wp_enqueue_script( 'boldgrid-backup-admin-archive-details' );

		wp_register_script(
			'boldgrid-backup-admin-zip-browser',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-zip-browser.js',
			array( 'jquery', ),
			BOLDGRID_BACKUP_VERSION
		);
		$unknown_error = __( 'An unknown error has occurred.', 'boldgrid-backup' );
		$translations = array(
			'loading' => __( 'Loading', 'boldgrid-backup' ),
			'home' => __( 'Home', 'boldgrid-backup' ),
			'restoring' => __( 'Restoring', 'boldgrid-backup' ),
			'confirmDbRestore' => __( 'Are you sure you want to restore this database backup?', 'boldgrid-backup' ),
			'unknownBrowseError' => __( 'An unknown error has occurred when trying to get a listing of the files in this archive.', 'boldgrid-backup' ),
			'unknownError' => $unknown_error,
			'unknownErrorNotice' => sprintf( '<div class="%1$s"><p>%2$s</p></div>', $this->core->notice->lang['dis_error'], $unknown_error ),
		);
		wp_localize_script( 'boldgrid-backup-admin-zip-browser', 'boldgrid_backup_zip_browser', $translations );
		wp_enqueue_script( 'boldgrid-backup-admin-zip-browser' );

		/**
		 * Allow other plugins to enqueue scripts on this page.
		 *
		 * @since 1.5.3
		 */
		do_action( 'boldgrid_backup_enqueue_archive_details' );

		$md5 = ! empty( $_GET['md5'] ) ? $_GET['md5'] : false;
		$archive_found = false;

		if( ! $md5 ) {
			echo __( 'No archive specified.', 'boldgrid-backup' );
			return;
		}

		$archives = $this->core->get_archive_list();
		if( empty( $archives ) ) {
			echo __( 'No archives available. Is your backup directory configured correctly?', 'boldgrid-backup' );
			return;
		}

		foreach( $archives as $archive ) {
			if( $md5 === md5( $archive['filepath'] ) ) {
				$log = $this->core->archive_log->get_by_zip( $archive['filepath'] );
				$archive = array_merge( $log, $archive );
				$archive_found = true;
				break;
			}
		}

		if( ! $archive_found ) {
			echo __( 'Archive not found.', 'boldgrid-backup' );
			return;
		}

		$dump_file = $this->core->get_dump_file( $archive['filepath'] );

		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-archive-details.php';
	}
}
