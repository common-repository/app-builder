<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Traits\Permission;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AppBuilder\Classs\RawQuery;
use WpFluent\QueryBuilder\Raw;

/**
 * The DeleteAuth class for handling delete user.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class DeleteAuth implements AuthInterface {
	use Permission;
	use AuthTrails;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * DeleteAuth constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Register rest route.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			'app-builder/v1',
			'/delete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => array( $this, 'logged_permissions_check' ),
			)
		);

		register_rest_route(
			'app-builder/v1',
			'send-otp-delete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send_otp_delete' ),
				'permission_callback' => array( $this, 'logged_permissions_check' ),
			)
		);
	}

	/**
	 * Sent OTP delete account
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function send_otp_delete( WP_REST_Request $request ) {
		$otp  = wp_rand( 100000, 999999 );
		$user = wp_get_current_user();

		/**
		 * Not allow to delete account if user is admin
		 */
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return new WP_Error(
				'app_builder_delete_admin',
				__( 'The delete option is not visible to Administrators.', 'mobile-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		/**
		 * Filter hook get the OTP before sent
		 */
		$otp = apply_filters( 'app_builder_delete_user_otp', $otp );

		/**
		 * Do action before send OTP
		 */
		do_action( 'app_builder_delete_user_before_send_otp', $user, $otp );

		/**
		 * Filter hook sent OTP via email?
		 */
		$sent = apply_filters( 'app_builder_delete_user_sent_email', true );

		if ( $sent ) {
			$email       = array();
			$email['to'] = $user->user_email;

			// Translators: %s is the site name.
			$email['subject'] = sprintf( _x( '[%s] OTP', '%s = site name', 'app-builder' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );

			// Translators: %d is the OTP.
			$email['message'] = sprintf( _x( 'Your OTP for account deletion is %d. The OTP is valid in 30 minutes.', 'Email OTP content', 'app-builder' ), $otp, ENT_QUOTES );

			$email_data = apply_filters( 'app_builder_delete_user_otp_email', $email );

			wp_mail( $email_data['to'], $email_data['subject'], $email_data['message'] );

			/**
			 * Update OTP to user meta
			 */
			update_user_meta( $user->ID, 'app_builder_delete_user_otp', $otp );
			update_user_meta( $user->ID, 'app_builder_delete_user_otp_sent_time', time() + 1800 );
		}

		return rest_ensure_response( array( 'otp' => 'OTP sent' ) );
	}

	/**
	 * Delete user
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function delete( $request ) {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return new WP_Error(
				'app_builder_delete_user_not_logged_in',
				__( 'You are not logged in.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$user_id = $user->ID;

		/**
		 * Not allow to delete account if user is admin
		 */
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return new WP_Error(
				'app_builder_delete_admin',
				__( 'The delete option is not visible to Administrators.', 'mobile-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$reason = $request->get_param( 'reason' );
		// $password = $request->get_param( 'password' );
		$otp = (int) $request->get_param( 'otp' );

		$verify = false;

		$user_otp       = (int) get_user_meta( $user_id, 'app_builder_delete_user_otp' );
		$user_sent_time = (int) get_user_meta( $user_id, 'app_builder_delete_user_otp_sent_time' );

		$user_otp       = (int) get_user_meta( $user_id, 'app_builder_delete_user_otp', true );
		$user_sent_time = (int) get_user_meta( $user_id, 'app_builder_delete_user_otp_sent_time', true );

		if ( $otp === $user_otp && $user_sent_time >= time() ) {
			$verify = true;
		}

		$verify = apply_filters( 'app_builder_delete_user_verify_otp', $verify, $otp, $user );

		if ( ! $verify ) {
			return new WP_Error(
				'app_builder_delete_user_otp',
				__( 'The OTP not validate.', 'mobile-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		/**
		 * Include required WordPress function files
		 */
		include_once ABSPATH . WPINC . '/post.php'; // wp_delete_post.
		include_once ABSPATH . 'wp-admin/includes/bookmark.php'; // wp_delete_link.
		include_once ABSPATH . 'wp-admin/includes/comment.php'; // wp_delete_comment.
		include_once ABSPATH . 'wp-admin/includes/user.php'; // wp_delete_user, get_blogs_of_user.

		/**
		 * Delete Posts
		 */
		$post_types_to_delete = array();
		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			if ( $post_type->delete_with_user ) {
				$post_types_to_delete[] = $post_type->name;
			} elseif ( null === $post_type->delete_with_user && post_type_supports( $post_type->name, 'author' ) ) {
				$post_types_to_delete[] = $post_type->name;
			}
		}

		$post_types_to_delete = apply_filters( 'app_builder_post_types_to_delete_with_user', $post_types_to_delete, $user_id );

		/**
		 * Get post list
		 */
		$posts_list = array();
		$posts      = RawQuery::get_posts_by_user_id_and_post_type( $user_id, $post_types_to_delete );
		foreach ( $posts as $post ) {
			$posts_list[] = wp_specialchars_decode( $post['post_title'], ENT_QUOTES ) . "\n" . ucwords( $post['post_type'] ) . ' ' . get_permalink( $post['ID'] );
		}

		/**
		 * Delete Links
		 */
		$links_list = array();
		$links      = RawQuery::get_links_by_user_id( $user_id );
		foreach ( $links as $link ) {
			$links_list[] = wp_specialchars_decode( $link['link_name'], ENT_QUOTES ) . "\n" . $link['link_url'];
		}

		/**
		 * Delete Comments
		 */
		$comments_list = array();

		$comments = RawQuery::get_comments_by_user_id( $user_id );

		foreach ( $comments as $comment ) {

			$comments_list[] = $comment['comment_ID'];

			// Delete comments if option set.
			wp_delete_comment( $comment['comment_ID'] );

		}

		/**
		 * Send email deleted
		 */
		$email       = array();
		$email['to'] = get_option( 'admin_email' );

		// Translators: %s is the site name.
		$email['subject'] = sprintf( _x( '[%s] Deleted User Notification', '%s = site name', 'app-builder' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );

		// Translators: %s is the site name.
		$email['message'] = sprintf( _x( 'Deleted user on your site %s', '%s = site name', 'app-builder' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) . ':' . "\n\n" .
			__( 'Username', 'app-builder' ) . ': ' . $user->user_login . "\n\n" .
			__( 'E-mail', 'app-builder' ) . ': ' . $user->user_email . "\n\n" .
			__( 'Role', 'app-builder' ) . ': ' . implode( ',', $user->roles ) . "\n\n" .
			__( 'First Name', 'app-builder' ) . ': ' . ( empty( $user->first_name ) ? __( '(empty)', 'app-builder' ) : $user->first_name ) . "\n\n" .
			__( 'Last Name', 'app-builder' ) . ': ' . ( empty( $user->last_name ) ? __( '(empty)', 'app-builder' ) : $user->last_name ) . "\n\n" .
			// Translators: %d ís post count to delete.
			sprintf( __( '%d Post(s)', 'app-builder' ), count( $posts_list ) ) . "\n" .
			'----------------------------------------------------------------------' . "\n" .
			implode( "\n\n", $posts_list ) . "\n\n" .
			// Translators: %d is link count to delete.
			sprintf( __( '%d Link(s)', 'app-builder' ), count( $links_list ) ) . "\n" .
			'----------------------------------------------------------------------' . "\n" .
			implode( "\n\n", $links_list ) . "\n\n" .
			// Translators: %d is comment count to delete.
			sprintf( __( '%d Comment(s)', 'app-builder' ), count( $comments_list ) );

		// Resson for delete account.
		if ( ! empty( $reason ) ) {
			$email['message'] .= "\n\n" . __( 'Reason', 'app-builder' ) . ': ' . $reason;
		}

		$email_data = apply_filters( 'app_builder_delete_user_email', $email );

		wp_mail( $email_data['to'], $email_data['subject'], $email_data['message'] );

		/**
		 * Delete user
		 */
		$status = wp_delete_user( $user_id );

		if ( $status ) {
			// Delete cache posts.
			wp_cache_delete( 'posts_' . md5( $user_id . '_' . implode( ',', $post_types_to_delete ) ), 'app_builder_posts' );

			// Delete cache links.
			wp_cache_delete( 'links_' . md5( $user_id ), 'app_builder_links' );

			// Delete cache comments.
			wp_cache_delete( 'comments_' . md5( $user_id ), 'app_builder_comments' );
		}

		/**
		 * Do action after delete account
		 */
		do_action( 'app_builder_end_delete_account', $user, $request, $status );

		return rest_ensure_response( array( 'delete' => $status ) );
	}

	/**
	 * Get args.
	 *
	 * @return array
	 */
	public function schema() {
		return array(
			'otp'    => array(
				'description' => __( 'The OTP for confirm deleted.', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
			'reason' => array(
				'description' => __( 'The reason for delete account.', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
		);
	}
}
