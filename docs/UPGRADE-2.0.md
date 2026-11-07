---
searchable: false
---
# Upgrade 2.0

## Hydrator

The ODM now requires `patchlevel/hydrator` 2.1.1 or newer. Most changes come from the hydrator itself, so go through
the [hydrator upgrade guide](https://github.com/patchlevel/hydrator/blob/2.0.0/UPGRADE-2.0.md) for your
normalizers, hydrator setup and encryption attributes. The sections below cover what affects the ODM directly.

### Custom normalizers

The `NormalizerWithContext` interface has been removed. Every normalizer now implements `Normalizer`, whose
`normalize()` and `denormalize()` methods always receive an `array $context`.

before:

```php
use Patchlevel\Hydrator\Normalizer\InvalidType;
use Patchlevel\Hydrator\Normalizer\NormalizerWithContext;

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements NormalizerWithContext
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context = []): mixed
    {
        if (!$value instanceof Skill) {
            throw new InvalidType();
        }

        return $value->value;
    }

    /** @param array<string, mixed> $context */
    public function denormalize(mixed $value, array $context = []): mixed
    {
        if (!is_string($value)) {
            throw new InvalidType();
        }

        return new Skill($value);
    }
}
```
after:

```php
use Patchlevel\Hydrator\Normalizer\InvalidType;
use Patchlevel\Hydrator\Normalizer\Normalizer;

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements Normalizer
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context): mixed
    {
        if (!$value instanceof Skill) {
            throw new InvalidType();
        }

        return $value->value;
    }

    /** @param array<string, mixed> $context */
    public function denormalize(mixed $value, array $context): mixed
    {
        if (!is_string($value)) {
            throw new InvalidType();
        }

        return new Skill($value);
    }
}
```
### Documents with a class level normalizer

Hydrator 2 uses a normalizer on a class, on one of its parents or on an implemented interface for the whole
object, and a guesser that matches the class has the same effect. Documents are stored field by field, so a
document class must not end up with such a normalizer. The `StackHydratorFieldMappingResolver`, which the
repository managers' `create()` sets up, now throws `Patchlevel\ODM\Metadata\DocumentHasClassNormalizer`
when it builds the metadata of such a document.

Remove the normalizer from the document class, its parents and its interfaces, and make sure no guesser
matches a document class. Put normalizers on the value objects inside the document instead.

### Custom hydrators

The repository managers and repositories now take a `Patchlevel\Hydrator\Hydrator` instead of a
`Patchlevel\Hydrator\HydratorWithContext`. The methods of `Hydrator` work on `mixed` data, so adapt the
signatures of your own hydrator. For documents, `extract()` still has to return an array, otherwise the
repository throws `Patchlevel\ODM\Hydrator\DocumentNotExtractable`.

before:

```php
use Patchlevel\Hydrator\HydratorWithContext;

final class LoggingHydrator implements HydratorWithContext
{
    public function __construct(
        private readonly HydratorWithContext $hydrator,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function hydrate(string $class, array $data, array $context = []): object
    {
        return $this->hydrator->hydrate($class, $data, $context);
    }

    /** @param array<string, mixed> $context */
    public function extract(object $object, array $context = []): array
    {
        return $this->hydrator->extract($object, $context);
    }
}
```
after:

```php
use Patchlevel\Hydrator\Hydrator;

final class LoggingHydrator implements Hydrator
{
    public function __construct(
        private readonly Hydrator $hydrator,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function hydrate(string $class, mixed $data, array $context = []): object
    {
        return $this->hydrator->hydrate($class, $data, $context);
    }

    /** @param array<string, mixed> $context */
    public function extract(object $object, array $context = []): mixed
    {
        return $this->hydrator->extract($object, $context);
    }
}
```
### DocumentHydrator

`Patchlevel\ODM\Hydrator\DocumentHydrator` is internal now and no longer implements the `Hydrator`
interface. Load and store documents through the repository instead of using it directly.

## Field Mapping

### FieldMappingResolver

`FieldMappingResolver::resolve()` now works per class instead of per property. It receives a document class
and returns the field mappings of all its properties, keyed by property name. With inheritance it is called
for the root and for every class in the discriminator map. Properties you leave out are not mapped, except
the id and version properties, which always are.

before:

```php
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\FieldMappingResolver;

final class PropertyNameFieldMappingResolver implements FieldMappingResolver
{
    public function resolve(ReflectionProperty $reflectionProperty): FieldMapping|null
    {
        return new FieldMapping($reflectionProperty->getName());
    }
}
```
after:

```php
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\FieldMappingResolver;

final class PropertyNameFieldMappingResolver implements FieldMappingResolver
{
    /**
     * @param class-string $className
     *
     * @return array<string, FieldMapping>
     */
    public function resolve(string $className): array
    {
        $fields = [];

        foreach ((new ReflectionClass($className))->getProperties() as $property) {
            $fields[$property->getName()] = new FieldMapping($property->getName());
        }

        return $fields;
    }
}
```
