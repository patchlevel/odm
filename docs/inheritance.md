# Inheritance

Sometimes several document types are variations of the same thing: an `Image` and a `Video` are both
`Media`. Patchlevel ODM can store a whole class hierarchy in a single collection and reconstruct the
right concrete class on load. This is single-collection inheritance, driven by a discriminator field.

## Defining a hierarchy

Put `#[Document]` and `#[DiscriminatorMap]` on the root class and let the concrete classes extend it.
The map assigns a short, stable string to every concrete class. The subclasses inherit the collection
and the `#[Id]` property from the root, so they do not repeat the `#[Document]` attribute.

```php
use Patchlevel\ODM\Attribute\DiscriminatorMap;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('media')]
#[DiscriminatorMap([
    'image' => Image::class,
    'video' => Video::class,
])]
abstract class Media
{
    public function __construct(
        #[Id]
        public readonly string $id,
        public string $title,
    ) {
    }
}

final class Image extends Media
{
    public function __construct(string $id, string $title, public int $width)
    {
        parent::__construct($id, $title);
    }
}

final class Video extends Media
{
    public function __construct(string $id, string $title, public int $duration)
    {
        parent::__construct($id, $title);
    }
}
```

Every stored document gets an extra field, `_type`, holding the discriminator value (`image` or
`video`). Change the field name with the second argument if `_type` clashes with a property:
`#[DiscriminatorMap([...], field: '_kind')]`.

## Working with the root repository

The repository for the root class is polymorphic. It accepts any subclass on write and returns the
concrete class on read.

```php
$repository = $manager->get(Media::class);

$repository->insert(
    new Image('m-1', 'Landscape', 1920),
    new Video('m-2', 'Trailer', 90),
);

$repository->find('m-1'); // Image
$repository->find('m-2'); // Video

foreach ($repository->findAll() as $media) {
    // Image and Video mixed together
}
```

You can filter by properties declared on the root and by properties that only exist on a subclass.
Documents that do not have the field simply do not match.

```php
$repository->findBy(['title' => 'Landscape']); // inherited field
$repository->findOneBy(['width' => 1920]);      // Image-only field
```

## Working with a subclass repository

The repository for a concrete class is scoped to that type. Every query, count and delete is
restricted to its discriminator value, and writes reject documents of a sibling type.

```php
$images = $manager->get(Image::class);

$images->count();        // only images
$images->findAll();      // only images
$images->find('m-2');    // null, m-2 is a video

$images->insert(new Video('m-3', 'Clip', 30)); // throws WrongClass
```

## Constraints

* All classes in the hierarchy live in one collection and share a single `_id` space.
* A property that appears on more than one subclass must map to the same stored field name in each of
  them. Otherwise the metadata factory throws `DiscriminatorFieldConflict`.
* `#[Index]` attributes are read from the root class, because the index belongs to the shared
  collection. Declare hierarchy-wide indexes there.
* Discriminator values are stored in every document, so keep them short and never change one once data
  exists.

:::warning
The discriminator map is validated when the metadata is built. Mapping a value to a class that does
not extend the root, or leaving the map empty, throws `InvalidDiscriminatorMap`. Loading a document
whose `_type` is missing or not in the map throws `UnknownDiscriminatorValue`, and persisting a
subclass that was left out of the map throws `ClassNotInDiscriminatorMap`.
:::

## Learn more

* [How to store and load documents](repository.md)
* [How to control field names and normalization](field-mapping.md)
* [How indexes are declared and synchronized](documents.md#indexes)
