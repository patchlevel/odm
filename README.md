# Patchlevel ODM

A lightweight Object Document Mapper (ODM) for MongoDB and Postgres built on top of `patchlevel/rango` and `patchlevel/hydrator`.

**Features**
- Attribute-based mapping (`#[Document]`, `#[Id]`, `#[Index]`).
- Repository API for simple CRUD operations.
- Index management on the repository.
- Hydrator integration for (de-)normalization.

**Requirements**
- PHP 8.3+.

**Installation**
```bash
composer require patchlevel/odm
```

**Quick Start (fixtures-based example)**
```php
<?php

use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\HydratorBuilder;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;
use Patchlevel\ODM\Hydrator\ODMExtension;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

use Attribute;

#[Document('rango_documents')]
#[Index('by_status', ['status' => 'asc'])]
final readonly class Profile
{
    /** @param list<Skill> $skills */
    public function __construct(
        #[Id]
        public string $id,
        public string $name,
        public Status $status,
        public array $skills,
    ) {
    }
}

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements Normalizer
{
    public function normalize(mixed $value, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value->value;
    }

    public function denormalize(mixed $value, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        return new Skill($value);
    }
}

#[SkillNormalizer]
final readonly class Skill
{
    public function __construct(
        public string $value,
    ) {
    }
}

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

$client = new Client($_ENV['POSTGRES_URI']);
$database = $client->selectDatabase('patchlevel');

$metadataFactory = new AttributeDocumentMetadataFactory();

$hydrator = (new HydratorBuilder())
    ->useExtension(new CoreExtension())
    ->useExtension(new ODMExtension($metadataFactory))
    ->build();

$manager = new RangoRepositoryManager($database, $metadataFactory, $hydrator);
$repository = $manager->get(Profile::class);

$repository->save(new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]));
$profile = $repository->load('r-1');
```

**Defining Documents**
- `#[Document('collection_name')]` defines the collection.
- `#[Id]` marks the identifier property mapped to `_id`.
- `#[Index('name', ['field' => 'asc'|'desc'], unique: true|false)]` defines indexes.

**Repository API**
```php
$repository->save($document);
$repository->load('id');
$repository->find(['status' => 'active']);
$repository->has('id');
$repository->count();
$repository->remove('id');
```

**Index Management**
```php
$repository->createCollection();
$repository->updateIndexes();
$repository->updateIndexes(dropUnknown: true);
```

**Development & Tests**
```bash
composer install
```

```bash
docker compose up -d
```

```bash
make test
```

Other targets:
- `make cs` for coding standard and fixer.
- `make phpstan` for static analysis.
- `make dev` for `phpstan`, `cs`, and tests.

**License**
MIT. See `LICENSE`.
