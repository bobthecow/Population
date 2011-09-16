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
    const DEFAULT_PER_FLUSH = 100;

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
     *     perFlush:        Limit the number of insertions performed in a single flush (default: 100)
     *     clearAfterFlush: Clear the ObjectManager after each flush (default:true)
     *     factory:         Optionally, specify a factory callback for populated objects
     *     constructorArgs: An array of args, passed directly to the document's constructor (default: null)
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
     * @param callable $factory
     * @param array $args
     * @param int $count
     * @param callable $callback Function which populates the data for each instance. It is passed a single argument,
     *     the document to be populated. If $callback returns false, the document will not be persisted.
     * @param array $options (default: array())
     *     perFlush:        Limit the number of insertions performed in a single flush (default: 100)
     *     clearAfterFlush: Clear the DocumentManager after each flush (default:true)
     *     factory:         Optionally, specify a factory callback for populated objects
     *     constructorArgs: An array of args, passed directly to the document's constructor (default: null)
     * @return void
     */
    public function populateDocument(DocumentRepository $repo, $count, $callback, array $options = array())
    {
        $dm = $repo->getDocumentManager();

        if (isset($options['factory'])) {
            $factory   = $options['factory'];
        } else {
            $reflClass = $repo->getClassMetadata()->reflClass;
            $factory   = array($reflClass, 'newInstanceArgs');
        }

        $this->populateObject($dm, $factory, $count, $callback, $options);
    }

    /**
     * Populate the EntityRepository $repo with $count entities.
     *
     * @see \Population\Populator::populate
     *
     * @access public
     * @param EntityRepository $repo
     * @param callable $factory
     * @param array $args
     * @param int $count
     * @param callable $callback Function which populates the data for each instance. It is passed a single argument,
     *     the entity to be populated. If $callback returns false, the entity will not be persisted.
     * @param array $options (default: array())
     *     perFlush:        Limit the number of insertions performed in a single flush (default: 100)
     *     clearAfterFlush: Clear the EntityManager after each flush (default:true)
     *     factory:         Optionally, specify a factory callback for populated objects
     *     constructorArgs: An array of args, passed directly to the document's constructor (default: null)
     * @return void
     */
    public function populateEntity(EntityRepository $repo, $count, $callback, array $options = array())
    {
        $em = $repo->getEntityManager();

        if (isset($options['factory'])) {
            $factory   = $options['factory'];
        } else {
            $reflClass = $repo->getClassMetadata()->reflClass;
            $factory   = array($reflClass, 'newInstanceArgs');
        }

        $this->populateObject($em, $factory, $count, $callback, $options);
    }

    protected function populateObject(ObjectManager $om, $factory, $count, $callback, array $options = array())
    {
        $perFlush        = isset($options['perFlush']) ? $options['perFlush'] : self::DEFAULT_PER_FLUSH;
        $clearAfterFlush = isset($options['clearAfterFlush']) ? $options['clearAfterFlush'] : true;
        $constructorArgs = isset($options['constructorArgs']) ? $options['constructorArgs'] : array();

        for ($i = 0; $i < $count; $i++) {
            $obj = call_user_func_array($factory, $constructorArgs);

            if (call_user_func($callback, $obj) !== false) {
                $om->persist($obj);
            }

            if ($perFlush && (($i + 1) % $perFlush == 0)) {
                $om->flush();
                if ($clearAfterFlush) {
                    $om->clear();
                }
            }
        }

        $om->flush();
        if ($clearAfterFlush) {
            $om->clear();
        }
    }
}