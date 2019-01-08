<?php

namespace BrightNucleus\Collection;

interface IdentityMap {

	/**
	 * Check whether a given ID is known to the identity map.
	 *
	 * @param int|string $id ID of the entity.
	 * @return bool Whether the identity map knows the entity.
	 */
	public function has( $id ): bool;

	/**
	 * Get an entity from the identity map given an ID.
	 *
	 * @param int|string $id ID of the entity to retrieve.
	 * @return mixed Entity with the requested ID.
	 */
	public function get( $id );

	/**
	 * Put the given entity into the identity map.
	 *
	 * @param int|string $id     ID to store the entity under.
	 * @param mixed      $entity Entity to store in the identity map.
	 */
	public function put( $id, $entity ): void;
}
