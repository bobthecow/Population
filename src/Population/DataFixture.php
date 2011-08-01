<?php

/*
 * This file is part of the Population package.
 *
 * (c) 2011 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Population;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ORM\EntityRepository;

/**
 * Abstract Population DataFixture class.
 * 
 * @abstract
 * @implements FixtureInterface
 */
abstract class DataFixture implements FixtureInterface
{
    /**
     * Population convenience method.
     *
     * @see \Population\Populator::populate
     *
     * @access public
     * @param ObjectRepository $repo
     * @param int $count Number of objects to populate.
     * @param callable $callback
     * @param array $constructorArgs Args, passed directly to the object's constructor (default: array())
     * @return void
     */
    public function populate(ObjectRepository $repo, $count, $callback, array $constructorArgs = array())
    {
        return $this->getPopulator()->populate($repo, $count, $callback, $constructorArgs);
    }

    /**
     * @access public
     * @param Populator $populator
     * @return void
     */
    public function setPopulator(Populator $populator)
    {
        $this->populator = $populator;
    }

    /**
     * @access public
     * @return Populator
     */
    public function getPopulator()
    {
        if (!isset($this->populator)) {
            $this->populator = new Populator();
        }

        return $this->populator;
    }
}
