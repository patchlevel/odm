# Field Mapping

Patchlevel ODM maps document properties to storage through
[patchlevel/hydrator](https://github.com/patchlevel/hydrator/). Scalars, enums, nested objects and
value objects are normalized into a storable shape on write and reconstructed on read. This page
shows how the stored field names are chosen and how to customize them.

## Default mapping

By default a property is stored under its own name. The only exception is the `#[Id]` property, which
is always stored under the reserved `_id` field no matter what the property is called.

```php
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('profiles')]
final class Profile
{
    public function __construct(
        #[Id]
        public readonly string $id,   // stored as _id
        public string $name,          // stored as name
        public Status $status,        // stored as status
    ) {
    }
}
```
## Renaming fields

Use the hydrator's `#[NormalizedName]` attribute to store a property under a different field name.
This is useful for keeping a stable storage schema while renaming properties in code.

```php
use Patchlevel\Hydrator\Attribute\NormalizedName;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('profiles')]
final class Profile
{
    public function __construct(
        #[Id]
        public readonly string $id,
        #[NormalizedName('_name')]
        public string $name,
    ) {
    }
}
```
:::note
You still filter and sort by the property name (`name`), not by the stored field (`_name`). The ODM
translates property paths to field paths for you, as described in [querying](repository.md#querying).
:::

## Nested objects

Nested objects are normalized recursively. Renamed fields on nested objects are respected, and you can
filter on them with dot notation.

```php
use Patchlevel\Hydrator\Attribute\NormalizedName;

final readonly class PersonalData
{
    public function __construct(
        #[NormalizedName('_name')]
        public string $name,
        #[NormalizedName('_age')]
        public int $age,
    ) {
    }
}

#[Document('profiles')]
final class Profile
{
    public function __construct(
        #[Id]
        public readonly string $id,
        #[NormalizedName('_personal_data')]
        public PersonalData $personalData,
    ) {
    }
}
```
A filter on the nested property uses the property path, which is mapped to the stored field path:

```php
$result = iterator_to_array(
    $repository->findBy(['personalData.name' => 'Rango']),
    false,
);
```
## Custom normalizers

For value objects with their own representation, write a normalizer and attach it as an attribute.
The normalizer converts the object to a storable value and back.

```php
use Patchlevel\Hydrator\Normalizer\InvalidType;
use Patchlevel\Hydrator\Normalizer\NormalizerWithContext;

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements NormalizerWithContext
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context = []): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Skill) {
            throw new InvalidType();
        }

        return $value->value;
    }

    /** @param array<string, mixed> $context */
    public function denormalize(mixed $value, array $context = []): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidType();
        }

        return new Skill($value);
    }
}
```
Attach the normalizer to the value object, then use it like any other property:

```php
#[SkillNormalizer]
final readonly class Skill
{
    public function __construct(
        public string $value,
    ) {
    }
}
```
:::tip
The hydrator ships normalizers for enums, dates and arrays out of the box. See the
[hydrator documentation](https://patchlevel.dev/docs/hydrator/latest) for the full list.
:::

## Learn more

* [How to query renamed and nested properties](repository.md#querying)
* [How to define documents](documents.md)
* [How to encrypt sensitive fields](encryption.md)
