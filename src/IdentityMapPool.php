<?php

namespace BrightNucleus\Collection;

class IdentityMapPool {

	/** @var IdentityMap[] */
	protected $identityMaps = [];

	/**
	 * Get a specific identity map from the pool.
	 *
	 * @param string $type           Type of the identity map to retrieve.
	 * @param string $implementation Optional. Implementation to use for the
	 *                               type of identity map.
	 * @return IdentityMap Requested identity map.
	 */
	public function getIdentityMap( string $type, string $implementation = GenericIdentityMap::class ): IdentityMap {
		if ( array_key_exists( $type, $this->identityMaps ) ) {
			return $this->identityMaps[ $type ];
		}

		$identityMap                 = new $implementation();
		$this->identityMaps[ $type ] = $identityMap;

		return $identityMap;
	}
}
