<?php
namespace AppBuilder\Classs;

defined( 'ABSPATH' ) || exit;

use Firebase\JWT\JWT;

/**
 * Class PublicKey
 *
 * @author ngocdt@rnlab.io
 * @since 1.0.0
 */
class PublicKey {

	/**
	 * @param $kid
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getPublicKey( $kid ) {
		$args       = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);
		$publicKeys = wp_remote_get( 'https://appleid.apple.com/auth/keys', $args );

		if ( is_wp_error( $publicKeys ) ) {
			throw new \Exception( esc_html__( 'Get public keys error.', 'app-builder' ) );
		}

		$decodedPublicKeys = json_decode( $publicKeys['body'], true );

		if ( ! isset( $decodedPublicKeys['keys'] ) || count( $decodedPublicKeys['keys'] ) < 1 ) {
			throw new \Exception( esc_html__( 'Invalid key format.', 'app-builder' ) );
		}

		$key = array_search( $kid, array_column( $decodedPublicKeys['keys'], 'kid' ) );

		if ( $key === false ) {
			throw new \UnexpectedValueException( esc_html__( '"kid" empty, unable to lookup correct key', 'app-builder' ) );
		}

		$parsedKeyData    = $decodedPublicKeys['keys'][ $key ];
		$parsedPublicKey  = self::parseKey( $parsedKeyData );
		$publicKeyDetails = openssl_pkey_get_details( $parsedPublicKey );

		if ( ! isset( $publicKeyDetails['key'] ) ) {
			throw new \Exception( esc_html__( 'Invalid public key details.', 'app-builder' ) );
		}

		return array(
			'publicKey' => $publicKeyDetails['key'],
			'alg'       => $parsedKeyData['alg'],
		);
	}

	/**
	 * Parse a JWK key
	 *
	 * @param array $jwk An individual JWK
	 *
	 * @return resource|array An associative array that represents the key
	 *
	 * @throws \InvalidArgumentException     Provided JWK is empty
	 * @throws \UnexpectedValueException     Provided JWK was invalid
	 * @throws \DomainException              OpenSSL failure
	 *
	 * @uses createPemFromModulusAndExponent
	 */
	private static function parseKey( array $jwk ) {
		if ( empty( $jwk ) ) {
			throw new \InvalidArgumentException( esc_html__( 'JWK must not be empty', 'app-builder' ) );
		}
		if ( ! isset( $jwk['kty'] ) ) {
			throw new \UnexpectedValueException( esc_html__( 'JWK must contain a "kty" parameter', 'app-builder' ) );
		}

		switch ( $jwk['kty'] ) {
			case 'RSA':
				if ( \array_key_exists( 'd', $jwk ) ) {
					throw new \UnexpectedValueException( esc_html__( 'RSA private keys are not supported', 'app-builder' ) );
				}
				if ( ! isset( $jwk['n'] ) || ! isset( $jwk['e'] ) ) {
					throw new \UnexpectedValueException( esc_html__( 'RSA keys must contain values for both "n" and "e"', 'app-builder' ) );
				}

				$pem       = self::createPemFromModulusAndExponent( $jwk['n'], $jwk['e'] );
				$publicKey = \openssl_pkey_get_public( $pem );
				if ( false === $publicKey ) {
					throw new \DomainException( esc_html__( 'OpenSSL error: ', 'app-builder' ) );
				}

				return $publicKey;
			default:
				// Currently only RSA is supported
				break;
		}
	}

	/**
	 * Create a public key represented in PEM format from RSA modulus and exponent information
	 *
	 * @param string $n The RSA modulus encoded in Base64
	 * @param string $e The RSA exponent encoded in Base64
	 *
	 * @return string The RSA public key represented in PEM format
	 *
	 * @uses encodeLength
	 */
	private static function createPemFromModulusAndExponent( $n, $e ) {
		$modulus        = JWT::urlsafeB64Decode( $n );
		$publicExponent = JWT::urlsafeB64Decode( $e );

		$components = array(
			'modulus'        => \pack( 'Ca*a*', 2, self::encodeLength( \strlen( $modulus ) ), $modulus ),
			'publicExponent' => \pack( 'Ca*a*', 2, self::encodeLength( \strlen( $publicExponent ) ), $publicExponent ),
		);

		$rsaPublicKey = \pack(
			'Ca*a*a*',
			48,
			self::encodeLength( \strlen( $components['modulus'] ) + \strlen( $components['publicExponent'] ) ),
			$components['modulus'],
			$components['publicExponent']
		);

		// sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
		$rsaOID       = \pack( 'H*', '300d06092a864886f70d0101010500' ); // hex version of MA0GCSqGSIb3DQEBAQUA
		$rsaPublicKey = \chr( 0 ) . $rsaPublicKey;
		$rsaPublicKey = \chr( 3 ) . self::encodeLength( \strlen( $rsaPublicKey ) ) . $rsaPublicKey;

		$rsaPublicKey = \pack(
			'Ca*a*',
			48,
			self::encodeLength( \strlen( $rsaOID . $rsaPublicKey ) ),
			$rsaOID . $rsaPublicKey
		);

		$rsaPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" .
						\chunk_split( \base64_encode( $rsaPublicKey ), 64 ) .
						'-----END PUBLIC KEY-----';

		return $rsaPublicKey;
	}

	/**
	 * DER-encode the length
	 *
	 * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
	 * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	private static function encodeLength( $length ) {
		if ( $length <= 0x7F ) {
			return \chr( $length );
		}

		$temp = \ltrim( \pack( 'N', $length ), \chr( 0 ) );

		return \pack( 'Ca*', 0x80 | \strlen( $temp ), $temp );
	}
}
