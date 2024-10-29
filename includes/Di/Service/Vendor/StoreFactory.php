<?php


/**
 * StoreFactory class
 *
 * @link       https://appcheap.io
 * @since      4.0.0
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 */

namespace AppBuilder\Di\Service\Vendor;

defined( 'ABSPATH' ) || exit;

/**
 * Class StoreFactory
 *
 * @package AppBuilder\Di\Service\Vendor
 */
class StoreFactory {

	/**
	 * Create store
	 *
	 * @param string $type Store type.
	 *
	 * @return ?BaseStore Store instance.
	 */
	public function create( string $type ): ?BaseStore {
		switch ( $type ) {
			case 'wc_vendors':
				return new WCVendors();
			case 'wcmp':
				return new WCMpStore();
			case 'dokan':
				return new DokanStore();
			case 'wcfm':
				return new WCFMStore();
			default:
				return null;
		}
	}
}
