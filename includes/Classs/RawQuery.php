<?php

namespace AppBuilder\Classs;

/**
 * RawQuery class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class RawQuery {
	/**
	 * Get user ids by meta.
	 *
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value.
	 *
	 * @return array User ids.
	 */
	public static function get_user_ids_by_meta( string $meta_key, string $meta_value ): array {
		global $wpdb;

		// Sanitize inputs.
		$meta_key   = sanitize_key( $meta_key );
		$meta_value = sanitize_text_field( $meta_value );

		// Generate a unique cache key based on the meta key and value.
		$cache_key = 'user_ids_' . md5( $meta_key . '_' . $meta_value );

		// Try to get the result from the cache.
		$user_ids = wp_cache_get( $cache_key, 'app_builder_user_ids' );

		if ( false === $user_ids ) {
			// Prepare the query.
			$query = $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
				array( $meta_key, $meta_value )
			);

			// If not cached, perform the database query.
			$user_ids = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( ! $user_ids ) {
				$user_ids = array();
			} else {
				$user_ids = array_column( $user_ids, 'user_id' );
			}

			// Cache the result.
			wp_cache_set( $cache_key, $user_ids, 'app_builder_user_ids', 360 ); // Cache for 1 hour.
		}

		return $user_ids;
	}

	/**
	 * Get list posts by user id and post type.
	 *
	 * @param int   $user_id  User id.
	 * @param array $post_types List post types.
	 *
	 * @return array List posts.
	 */
	public static function get_posts_by_user_id_and_post_type( int $user_id, array $post_types ): array {
		global $wpdb;

		// Sanitize inputs.
		$user_id = absint( $user_id );

		// Generate a unique cache key based on the user id and post type.
		$cache_key = 'posts_' . md5( $user_id . '_' . implode( ',', $post_types ) );

		// Try to get the result from the cache.
		$posts = wp_cache_get( $cache_key, 'app_builder_posts' );

		if ( false === $posts ) {
			$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
			$sql          = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_author = %d AND post_type IN ($placeholders)";

			// If not cached, perform the database query.
			$posts = $wpdb->get_results( $wpdb->prepare( $sql, array( $user_id, ...$post_types ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( ! $posts ) {
				$posts = array();
			}

			// Cache the result.
			wp_cache_set( $cache_key, $posts, 'app_builder_posts', 3600 ); // Cache for 1 hour.
		}

		return $posts;
	}

	/**
	 * Get links by user id.
	 *
	 * @param int $user_id User id.
	 *
	 * @return array Links.
	 */
	public static function get_links_by_user_id( int $user_id ): array {
		global $wpdb;

		// Sanitize inputs.
		$user_id = absint( $user_id );

		// Generate a unique cache key based on the user id.
		$cache_key = 'links_' . md5( $user_id );

		// Try to get the result from the cache.
		$links = wp_cache_get( $cache_key, 'app_builder_links' );

		if ( false === $links ) {
			// If not cached, perform the database query.
			$links = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT link_id, link_url, link_name FROM {$wpdb->links} WHERE link_owner = %d",
					array( $user_id )
				),
				ARRAY_A
			);

			if ( ! $links ) {
				$links = array();
			}

			// Cache the result.
			wp_cache_set( $cache_key, $links, 'app_builder_links', 3600 ); // Cache for 1 hour.
		}

		return $links;
	}

	/**
	 * Get comments by user id.
	 *
	 * @param int $user_id User id.
	 *
	 * @return array Comments.
	 */
	public static function get_comments_by_user_id( int $user_id ): array {
		global $wpdb;

		// Sanitize inputs.
		$user_id = absint( $user_id );

		// Generate a unique cache key based on the user id.
		$cache_key = 'comments_' . md5( $user_id );

		// Try to get the result from the cache.
		$comments = wp_cache_get( $cache_key, 'app_builder_comments' );

		if ( false === $comments ) {
			// If not cached, perform the database query.
			$comments = $wpdb->get_results(// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT comment_ID FROM {$wpdb->comments} WHERE user_id = %d",
					array( $user_id )
				),
				ARRAY_A
			);

			if ( ! $comments ) {
				$comments = array();
			}

			// Cache the result.
			wp_cache_set( $cache_key, $comments, 'app_builder_comments', 3600 ); // Cache for 1 hour.
		}

		return $comments;
	}
}
