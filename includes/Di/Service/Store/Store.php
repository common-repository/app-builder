<?php
/**
 * Store class
 *
 * @link       https://appcheap.io
 * @since      4.0.0
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 * @package    AppBuilder\Di\Service\Store
 */

namespace AppBuilder\Di\Service\Store;

defined( 'ABSPATH' ) || exit;

use AppBuilder\Di\Service\Store\AbstractStore;
use AppBuilder\Di\Service\Store\WcfmStore\WcfmStore;

/**
 * Class StoreFactory
 *
 * @package AppBuilder\Di\Service\Store
 */
class Store {

	/**
	 * Create store
	 *
	 * @param string $type Store type.
	 *
	 * @return ?AbstractStore Store instance.
	 */
	public function create( string $type ) {
		switch ( $type ) {
			// case 'wc_vendors':
			// return new WCVendors();
			// case 'wcmp':
			// return new WCMpStore();
			// case 'dokan':
			// return new DokanStore();
			case 'wcfm':
				return new WcfmStore( 'app-builder/v1', '/stores' );
			default:
				return null;
		}
	}
}
