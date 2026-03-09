# Patchlevel ODM

Patchlevel ODM is a lightweight **Object Document Mapper (ODM)** for PHP that works with **MongoDB and PostgreSQL (via patchlevel/rango)**.
It is built on top of **`patchlevel/hydrator`**, providing a simple attribute-based mapping layer and repository abstraction for working with document data.

The goal of Patchlevel ODM is to offer a **clean domain model** while keeping the flexibility of document databases.

## 🚀 Why Patchlevel ODM?

* **Attribute-based Mapping** – Define documents and indexes using modern PHP attributes.
* **Simple Repository API** – A small, focused API for CRUD operations.
* **Database Flexibility** – Works with both MongoDB and PostgreSQL via Rango.
* **Superfast Hydrator** – Using `patchlevel/hydrator`.
* **Domain-first Design** – Keep persistence logic out of your domain objects.

## 📦 Installation

```bash
composer require patchlevel/odm
```

## 🛠 How it Works

Patchlevel ODM maps PHP objects to document storage.

* **Documents** are defined using the `#[Document]` attribute.
* **Identifiers** are declared with `#[Id]`.
* **Indexes** can be defined using `#[Index]`.
* **Repositories** provide a simple API for loading and storing documents.
* **Hydrators** handle conversion between PHP objects and stored document data.

Internally the ODM uses:

* **`patchlevel/rango`** as the database abstraction layer
* **`patchlevel/hydrator`** for object mapping and normalization

## 🚦 Quick Start

```php
use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\HydratorBuilder;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;
use Patchlevel\ODM\Hydrator\ODMExtension;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

use Attribute;

#[Document('profiles')]
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

## ✨ Supported Features

### Document Mapping

| Attribute     | Purpose                                       |
| ------------- | --------------------------------------------- |
| `#[Document]` | Defines the collection name                   |
| `#[Id]`       | Marks the identifier property mapped to `_id` |
| `#[Index]`    | Defines database indexes                      |

Example:

```php
#[Document('users')]
#[Index('by_email', ['email' => 'asc'], unique: true)]
class User
{
    #[Id]
    public string $id;
}
```

---

### Repository API

| Operation  | Method            |
| ---------- | ----------------- |
| **Save**   | `save($document)` |
| **Load**   | `load($id)`       |
| **Find**   | `find($criteria)` |
| **Exists** | `has($id)`        |
| **Count**  | `count()`         |
| **Delete** | `remove($id)`     |

Example:

```php
$repository->save($document);

$user = $repository->load('user-1');

$activeUsers = $repository->find([
    'status' => 'active'
]);
```

---

### Index Management

Repositories also provide helpers to manage database indexes.

| Method                             | Description                                 |
| ---------------------------------- | ------------------------------------------- |
| `createCollection()`               | Creates the collection if it does not exist |
| `updateIndexes()`                  | Creates or updates defined indexes          |
| `updateIndexes(dropUnknown: true)` | Drops indexes not defined in metadata       |

Example:

```php
$repository->createCollection();
$repository->updateIndexes();
```
