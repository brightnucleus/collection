<?php
/**
 * Bright Nucleus Collection Post Collection
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

use BrightNucleus\Exception\RuntimeException;
use Doctrine\Common\Collections\Criteria;

class Status implements Scope
{

    /**
     * @var string[] 
     */
    protected $selected = [];

    public const PUBLISH    = 'publish';
    public const FUTURE     = 'future';
    public const DRAFT      = 'draft';
    public const PENDING    = 'pending';
    public const PRIVATE    = 'private';
    public const TRASH      = 'trash';
    public const AUTO_DRAFT = 'auto-draft';
    public const INHERIT    = 'inherit';

    protected const KNOWN_VALUES = [
    self::PUBLISH,
    self::FUTURE,
    self::DRAFT,
    self::PENDING,
    self::PRIVATE,
    self::TRASH,
    self::AUTO_DRAFT,
    self::INHERIT,
    ];

    /**
     * Instantiate a Status object.
     *
     * @param mixed ...$scope Value(s) to use for the Scope.
     */
    public function __construct( ...$scope )
    {
        $this->addValues(...$scope);
    }

    /**
     * Add one or more values to the Scope.
     *
     * @param  mixed ...$scope Value(s) to add.
     * @return Scope Modified Scope.
     */
    public function add( ...$scope ): Scope
    {
        $newScope = clone $this;

        $newScope->addValues(...$scope);

        return $newScope;
    }

    /**
     * Add values to the scope.
     *
     * @param mixed ...$values Values to add.
     */
    protected function addValues( ...$values ): void
    {
        foreach ( $values as $value ) {
            if (! is_string($value) ) {
                throw new RuntimeException(
                    sprintf(
                        'Invalid value type "%1$s" for Scope of type "%2$s"',
                        is_object($value) ? get_class($value) : gettype($value),
                        get_class($this)
                    )
                );
            }

            if (! in_array($value, static::KNOWN_VALUES, true) ) {
                throw new RuntimeException(
                    sprintf(
                        'Invalid value "%1$s" for Scope of type "%2$s"',
                        $value,
                        get_class($this)
                    )
                );
            }

            $this->selected[] = $value;
        }

        $this->selected = array_unique($this->selected);
    }

    /**
     * Merge the Scope with another Scope and return the merged result.
     *
     * @param  Scope $scope Scope to merge with.
     * @return Scope Merged Scope.
     */
    public function mergeWith( Scope $scope ): Scope
    {
        if (! $scope instanceof static ) {
            throw new RuntimeException(
                sprintf(
                    'Cannot merge Scope of type "%1$s" with Scope of type "%2$s"',
                    get_class($scope),
                    get_class($this)
                )
            );
        }

        return $this->add($scope->selected);
    }

    /**
     * Get the Criteria for the current Scope.
     *
     * @return Criteria Criteria representing the Scope.
     */
    public function getCriteria(): Criteria
    {
        if (empty($this->selected) ) {
            return new NullCriteria();
        }

        if (count($this->selected) === 1 ) {
            return Criteria::create()
                ->where(
                    Criteria::expr()->eq(
                        new Column(Table::POSTS, 'post_status'),
                        $this->selected[0]
                    ) 
                );
        }

        return Criteria::create()
            ->where(
                Criteria::expr()->in(
                    new Column(Table::POSTS, 'post_status'),
                    $this->selected
                ) 
            );
    }
}
