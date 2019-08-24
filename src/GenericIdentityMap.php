<?php

namespace BrightNucleus\Collection;

final class GenericIdentityMap implements IdentityMap {

	/**
	 * @var Entity[]
	 */
	private $map = [];

	/**
	 * Check whether a given ID is known to the identity map.
	 *
	 * @param int|string $id ID of the entity.
	 * @return bool Whether the identity map knows the entity.
	 */
	public function has( $id ): bool {
		return array_key_exists( $id, $this->map );
	}

	/**
	 * Get an entity from the identity map given an ID.
	 *
	 * @param int|string $id ID of the entity to retrieve.
	 * @return mixed Entity with the requested ID.
	 */
	public function get( $id ) {
		return $this->map[ $id ];
	}

	/**
	 * Put the given entity into the identity map.
	 *
	 * @param mixed $entity Entity to store in the identity map.
	 */
	public function put( $id, $entity ): void {
		$this->map[ $id ] = $entity;
	}

	/**
	 * Drop an entity from the identity map given an ID.
	 *
	 * @param int|string $id ID of the entity to drop.
	 */
	public function drop( $id ): void {
		unset( $this->map[ $id ] );
	}
}
