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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Populator service.
 */
class Populator
{
    /**
     * Populate the ObjectRepository $repo with $count objects.
     *
     * The $callback is used to initialize data before the object is saved:
     *
     *     // Generate 10 blog posts
     *     $this->populate($em->getRepository('BlogBundle:Post'), 10, function($post) {
     *         $post->setTitle(\Faker\Lorem::sentence());
     *         $post->setContent(implode("\n\n", \Faker\Lorem::paragraphs(6)));
     *         $post->setCreatedAt(new \DateTime(\Faker\DateTime::timestamp()));
     *     });
     *
     *     // Add a few comments to each
     *     foreach ($em->getRepository('BlogBundle:Post')->find() as $post) {
     *         $this->populate($em->getRepository('BlogBundle:Comment'), rand(5, 10), function($comment) {
     *             $name = \Faker\Name::name();
     *             $comment->setAuthor($name);
     *             $comment->setEmail(\Faker\Internet::email($name));
     *             $comment->setSubject(\Faker\Lorem::sentence());
     *             $comment->setContent(\Faker\Lorem::paragraph());
     *             $comment->setCreatedAt(new \DateTime(\Faker\DateTime::timestamp()));
     *             $comment->setPost($post);
     *         });
     *     }
     *
     * @access public
     * @param ObjectRepository $repo
     * @param int $count Number of objects to populate.
     * @param callable $callback Function which populates the data for each instance. It is passed a single argument,
     *     the object to be populated. If $callback returns false, the object will not be persisted.
     * @param array $options (default: array())
     *     constructorArgs: An array of args, passed directly to the document's constructor (default: null)
     *     perFlush:        Limit the number of insertions performed in a single flush (default: unlimited)
     * @return void
     */
    public function populate(ObjectRepository $repo, $count, $callback, array $options = array())
    {
        switch (true) {
            case $repo instanceof DocumentRepository:
                return $this->populateDocument($repo, $count, $callback, $options);
                break;

            case $repo instanceof EntityRepository:
                return $this->populateEntity($repo, $count, $callback, $options);
                break;

            default:
                throw new \InvalidArgumentException('Unexpected ObjectRepository class: ' . get_class($repo));
                break;
        }
    }

    /**
     * Populate the DocumentRepository $repo with $count documents.
     *
     * @see \Population\Populator::populate
     *
     * @access public
     * @param DocumentRepository $repo
     * @param int $count
     * @param callable $callback Function which populates the data for each instance. It is passed a single argument,
     *     the document to be populated. If $callback returns false, the document will not be persisted.
     * @param array $options (default: array())
     *     constructorArgs: An array of args, passed directly to the document's constructor (default: null)
     *     perFlush:        Limit the number of insertions performed in a single flush (default: unlimited)
     * @return void
     */
    public function populateDocument(DocumentRepository $repo, $count, $callback, array $options = array())
    {
        $dm        = $repo->getDocumentManager();
        $reflClass = $repo->getClassMetadata()->reflClass;

        $this->populateObject($dm, $reflClass, $count, $callback, $options);
    }

    /**
     * Populate the EntityRepository $repo with $count entities.
     *
     * @see \Population\Populator::populate
     *
     * @access public
     * @param EntityRepository $repo
     * @param int $count
     * @param callable $callback Function which populates the data for each instance. It is passed a single argument,
     *     the entity to be populated. If $callback returns false, the entity will not be persisted.
     * @param array $options (default: array())
     *     constructorArgs: An array of args, passed directly to the document's constructor (default: null)
     *     perFlush:        Limit the number of insertions performed in a single flush (default: unlimited)
     * @return void
     */
    public function populateEntity(EntityRepository $repo, $count, $callback, array $options = array())
    {
        $em        = $repo->getEntityManager();
        $reflClass = $repo->getClassMetadata()->reflClass;

        $this->populateObject($em, $reflClass, $count, $callback, $options);
    }

    protected function populateObject(ObjectManager $om, \ReflectionClass $reflClass, $count, $callback, array $options = array())
    {
        for ($i = 0; $i < $count; $i++) {
            $args = isset($options['constructorArgs']) ? $options['constructorArgs'] : array();
            $obj = $reflClass->newInstanceArgs($args);

            if (call_user_func($callback, $obj) !== false) {
                $om->persist($obj);
            }

            if (isset($options['perFlush']) && (($i + 1) % $options['perFlush'] == 0)) {
                $om->flush();
            }
        }

        $om->flush();
    }
}