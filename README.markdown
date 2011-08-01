Population
==========

A simpler way to populate your Doctrine 2 databases.

Inspired by [Populator](https://github.com/ryanb/populator) for ActiveRecord.


Usage
-----

You really should use this library with [Faker](https://github.com/bobthecow/Faker).
You'll see why in a second.

The easiest way to use Population is to extend `\Population\DataFixture`. It's a
valid base class for both ORM and ODM data fixtures:

```php
<?php

namespace Application\BlogBundle\DataFixtures\ORM;

use Doctrine\ORM\EntityManager;
use MyApplication\BlogBundle\Document\Post;
use MyApplication\BlogBundle\Document\Comment;

class BlogPostsFixture extends \Population\DataFixture
{
    public function load(EntityManager $em)
    {
        // Generate 10 blog posts
        $this->populate($em->getRepository('MainBundle:Post'), 10, function(Post $post) {
            $post->setTitle(\Faker\Lorem::sentence());
            $post->setContent(implode("\n\n", \Faker\Lorem::paragraphs(6)));
            $post->setCreatedAt(new \DateTime(\Faker\DateTime::timestamp()));
        });

        // Add a few comments to each
        foreach ($em->getRepository('BlogBundle:Post')->find() as $post) {
            $this->populate($em->getRepository('BlogBundle:Comment'), rand(5, 10), function(Comment $comment) {
                $name = \Faker\Name::name();
                $comment->setAuthor($name);
                $comment->setEmail(\Faker\Internet::email($name));
                $comment->setSubject(\Faker\Lorem::sentence());
                $comment->setContent(\Faker\Lorem::paragraph());
                $comment->setCreatedAt(new \DateTime(\Faker\DateTime::timestamp()));
                $comment->setPost($post);
            });
        }
    }
}
```


If you need Population elsewhere, or if you don't particularly like using base
classes, you can strike out on your own with the Populator service:

```php
<?php

$populator = new Populator();
$populator->populate($em->getRepository('BlogBundle:Category'), 5, function($category) {
    $name = \Faker\Lorem::word();
    $category->setName($name);
    $category->setSlug(strtolower($name));
});

$categories = $em->getRepository('BlogBundle:Category')->find()->toArray();
foreach ($em->getRepository('BlogBundle:Post') as $post) {
    $post->setCategory($categories[array_rand($categories)]);
}

$em->flush();
```


Advanced usage
--------------

`populate` accepts a couple of additional options:

```php
<?php

$populator = new Populator();
$populator->populate($em->getRepository('BlogBundle:Tag'), 1000, function($tag) {
    $tag->setName(\Faker\Lorem::word());
}, array(
    'perFlush'        => 10,
    'constructorArgs' => array('foo'),
));
```

* `perFlush` will limit the number of objects flushed in a single query.

* `constructorArgs` is an array of args, passed directly to the object's constructor.
