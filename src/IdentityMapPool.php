<?php

namespace BrightNucleus\Collection;

class IdentityMapPool {

	/** @var IdentityMap[] */
	static $identityMaps = [];

	/**
	 * Get a specific identity map from the pool.
	 *
	 * @param string $type           Type of the identity map to retrieve.
	 * @param string $implementation Implementation to use for the type of
	 *                               identity map.
	 * @return IdentityMap Requested identity map.
	 */
	public static function getIdentityMap( string $type, string $implementation ) {
		if ( array_key_exists( $type, static::$identityMaps ) ) {
			return static::$identityMaps[ $type ];
		}

		$identityMap                   = new $implementation();
		static::$identityMaps[ $type ] = $identityMap;

		return $identityMap;
	}
}
