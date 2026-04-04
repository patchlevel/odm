# Patchlevel ODM

Patchlevel ODM is a lightweight **Object Document Mapper (ODM)** for PHP that works with **MongoDB** and **PostgreSQL (via [patchlevel/rango](https://github.com/patchlevel/rango/))**.
It is built on top of our superfast **[patchlevel/hydrator](https://github.com/patchlevel/hydrator/)**, providing a simple attribute-based mapping layer and
enterprise-grade features like cyptography.

## 🚀 Why Patchlevel ODM?

* **Postgres and MongoDB support** – Use the same ODM for both Postgres and MongoDB, with a consistent API.
* **Attribute-based Mapping** – Define documents and indexes using modern PHP attributes.
* **No Unit of Work** – Patchlevel ODM does not use a Unit of Work, giving you more control over when changes are persisted.
* **Built on Patchlevel Hydrator** – Benefit from the performance and extensibility (like [crypto shredding](https://github.com/patchlevel/hydrator/#cryptography)) of our powerful [hydrator library](https://github.com/patchlevel/hydrator/).

## 📦 Installation

You can install Patchlevel ODM using Composer. 
Depending on your database choice, you will need to require the appropriate packages.

### PostgreSQL (via [Rango](https://github.com/patchlevel/rango/))

```bash
composer require patchlevel/odm patchlevel/rango
```

### MongoDB

```bash
composer require patchlevel/odm mongodb/mongodb
```

## 🛠 How it Works

Patchlevel ODM maps PHP objects to document storage.

* **Documents** are defined using the `#[Document]` attribute.
* **Identifiers** are declared with `#[Id]`.
* **Indexes** can be defined using `#[Index]`.
* **Repositories** provide a simple API for loading and storing documents.

Internally the ODM uses:

* **[patchlevel/rango](https://github.com/patchlevel/rango/)** as the database abstraction layer for PostgreSQL
* **[mongodb/mongodb](https://github.com/mongodb/mongo-php-library)** as the database abstraction layer for MongoDB
* **[patchlevel/hydrator](https://github.com/patchlevel/hydrator/)** for object mapping and normalization

## 🚦 Quick Start

Define your documents and indexes using PHP attributes.

```php
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('profiles')]
#[Index('by_status', ['status' => 'asc'])]
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
```

### Setup PostgreSQL (via [Rango](https://github.com/patchlevel/rango/))

```php
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

$client = new Client($_ENV['POSTGRES_URI']);

$manager = RangoRepositoryManager::create($client);
```

### Setup MongoDB

```php
use MongoDB\Client;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;

$client = new Client($_ENV['MONGODB_URI']);

$manager = MongoDBRepositoryManager::create($client);
```

### Usage

Now you can use the repository manager to access your documents.

```php
$repository = $manager->get(Profile::class);

$repository->insert(
    new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]),
    new Profile('r-2', 'Foo', Status::ACTIVE, [new Skill('node'), new Skill('js')]),
    new Profile('r-3', 'Bar', Status::INACTIVE, [new Skill('mongodb')]),
);

$profiles = $repository->findBy(
    filter: ['status' => Status::ACTIVE->value],
    sort: ['name' => 'asc'],
    limit: 10,
    offset: 0
);

$profile = $repository->get('r-2');
$profile->name = 'New Foo';
$repository->update($profile);

$repository->remove('r-3');
```

## 🏗️ Design differences compared to Doctrine ODM

Doctrine ODM has a feature that we don't want: a Unit of Work (UOW).
A UOW tracks new objects and changes to existing objects.
To commit changes to the database, you need to call `flush()`.
In this step, the Unit of Work calculates the changes and applies them to the database.

This approach has several side effects:

- **Growing memory usage in long-running workers**  
  Since the Unit of Work keeps references to managed documents, memory usage can continuously grow in worker processes
  unless documents are manually detached or the UOW is cleared.

- **Risk of unintentionally persisting changes**  
  Because documents are tracked automatically, changes to a document may be persisted later by a `flush()` call in a
  completely different part of the codebase.

- **Risk of stale data**  
  Managed documents may become outdated if they remain in the UOW for too long, especially in long-running processes.

- **Complex lifecycle management**  
  To avoid these issues, developers often need to call `clear()` or `detach()`, which adds complexity and can easily
  introduce subtle bugs.

Because of these trade-offs, we intentionally avoid using a Unit of Work. Instead, repositories explicitly control
persistence operations. This means every insert or update must be triggered deliberately, making database writes
predictable and easier to reason about.
