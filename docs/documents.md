# Documents

A document is a plain PHP class that Patchlevel ODM maps to a collection in your database. You mark
the class with the `#[Document]` attribute and one property with `#[Id]`. There is no base class to
extend and no interface to implement, so your documents stay free of framework coupling.

## Defining a document

The `#[Document]` attribute takes the collection name. Exactly one property must carry the `#[Id]`
attribute, which becomes the document identifier and is stored as the `_id` field.

```php
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('profiles')]
final class Profile
{
    /** @param list<Skill> $skills */
    public function __construct(
        #[Id]
        public readonly string $id,
        public string $name,
        public Status $status,
        public array $skills,
    ) {
    }
}
```
:::warning
A document needs exactly one `#[Id]` property. The library throws `NoIdPropertyFound` when none is
present and `MultipleIdPropertiesFound` when more than one property is marked.
:::

:::note
The `#[Document]` attribute also takes an optional second argument to store the document in a specific
[database](databases.md), for example `#[Document('profiles', database: 'analytics')]`. Otherwise the
document lives in the manager's default database.
:::

## Identifiers

The identifier is a string. The ODM always stores it under the reserved `_id` field, regardless of
the property name. If you rename the id property with a [field mapping](field-mapping.md) attribute,
the ODM still maps it to and from `_id` transparently.

```php
$repository->insert(new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]));

$profile = $repository->get('r-1');
```
## Property values

Properties are mapped by the [hydrator](https://github.com/patchlevel/hydrator/). Scalars, enums,
nested objects, arrays and value objects are all supported. Complex values are normalized into a
storable representation and reconstructed on load.

```php
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
```
:::note
How nested objects, enums and custom field names are stored is described on the
[field mapping](field-mapping.md) page.
:::

## Indexes

Indexes speed up queries and can enforce uniqueness. You declare them on the document with the
`#[Index]` attribute, and the repository synchronizes them with the database on demand. The attribute
is repeatable, so a document can carry as many indexes as it needs.

### Declaring an index

`#[Index]` takes a name and a map of property names to a sort direction (`asc` or `desc`). The
property names are mapped to their stored [field names](field-mapping.md) automatically.

```php
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('profiles')]
#[Index('by_status', ['status' => 'asc'])]
#[Index('by_name', ['name' => 'asc'])]
final class Profile
{
    public function __construct(
        #[Id]
        public readonly string $id,
        public string $name,
        public Status $status,
    ) {
    }
}
```
### Unique indexes

Set `unique: true` to enforce that no two documents share the same value for the indexed properties.

```php
#[Document('profiles')]
#[Index('by_email', ['email' => 'asc'], unique: true)]
final class Profile
{
    public function __construct(
        #[Id]
        public readonly string $id,
        public string $email,
    ) {
    }
}
```
:::warning
Inserting a document that violates a unique index fails with an `InsertionFailed` exception. Catch it
to detect duplicates.
:::

### Synchronizing indexes

Indexes are not created automatically when you insert documents. Call `updateIndexes()` to create the
declared indexes, or `createCollection()`, which creates the collection together with its indexes.

```php
$repository->updateIndexes();

$repository->createCollection();
```
### Removing stale indexes

By default `updateIndexes()` only adds missing indexes. Pass `true` to also drop indexes that are no
longer declared on the document. The primary key index is always preserved.

```php
$repository->updateIndexes(dropUnknown: true);
```
:::tip
Run index synchronization as part of a deployment or migration step rather than on every request, so
your collections stay in sync with the document definitions.
:::

## Versioning

Mark an integer property with `#[Version]` to enable optimistic locking. The repository then guards
every `update()` against concurrent writes: it only touches the document while the stored version
still matches the one you loaded, and raises `OptimisticLockFailed` when another process changed or
removed the document in the meantime.

```php
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Version;

#[Document('profiles')]
final class Profile
{
    public function __construct(
        #[Id]
        public readonly string $id,
        public string $name,
        #[Version]
        public int $version = 0,
    ) {
    }
}
```
A new document starts at whatever value the object carries, so give the property a default of `0`.
On every successful `update()` the repository increments the stored version and writes the new value
back onto the object, so you can keep updating the same instance in a long-running worker.

:::warning
The version property must not be `readonly`, because the repository writes the incremented version
back after each update. A `readonly` version property throws `VersionPropertyIsReadonly` when the
document metadata is built. Only one property may carry `#[Version]`; a second one throws
`MultipleVersionPropertiesFound`.
:::

:::note
The version field goes through the [field mapping](field-mapping.md) like any other property, so you
can rename it with a `#[NormalizedName]` attribute.
:::

:::tip
Adding `#[Version]` to a document that already has stored data? Backfill the version field on every
existing document first, otherwise the first `update()` fails because the filter never matches a
document without the field.
:::

## Learn more

* [How to store and load documents](repository.md)
* [How to control field names and normalization](field-mapping.md)
* [How to query documents efficiently](repository.md#querying)
* [How to choose a database backend](databases.md)
