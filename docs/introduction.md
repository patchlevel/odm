# Patchlevel ODM

Patchlevel ODM is a lightweight Object Document Mapper for PHP. It maps plain PHP objects to
document storage and runs on both MongoDB and PostgreSQL (through
[patchlevel/rango](https://github.com/patchlevel/rango/)), exposing the same API for both.
It is built on top of [patchlevel/hydrator](https://github.com/patchlevel/hydrator/), which gives
you fast attribute-based mapping and enterprise features like encryption out of the box.

Unlike Doctrine ODM, Patchlevel ODM has no Unit of Work. Repositories control persistence
explicitly, so every write is deliberate and easy to reason about, which makes the library a good
fit for long-running worker processes.

## Features

* [MongoDB and PostgreSQL support](databases.md) with a single, consistent API
* [Attribute-based document mapping](documents.md) with `#[Document]` and `#[Id]`
* [Repositories without a Unit of Work](repository.md) for predictable writes
* [Querying](repository.md#querying) with filters, sorting and pagination
* [Indexes](documents.md#indexes) defined with `#[Index]`, including unique constraints
* [Optimistic locking](documents.md#versioning) with `#[Version]` to catch concurrent writes
* [Field mapping and normalization](field-mapping.md) for nested objects and custom field names
* [Encryption and crypto shredding](encryption.md) for sensitive data

## Installation

Install the library with Composer. Depending on your database, you also need the matching driver
package.

For PostgreSQL via [Rango](https://github.com/patchlevel/rango/):

```bash
composer require patchlevel/odm patchlevel/rango
```
For MongoDB:

```bash
composer require patchlevel/odm mongodb/mongodb
```
## Integration

* [patchlevel/hydrator](https://github.com/patchlevel/hydrator/) powers the object mapping and normalization
* [patchlevel/rango](https://github.com/patchlevel/rango/) is the PostgreSQL document layer

:::tip
New to the library? The [getting started](getting-started.md) guide builds a complete example from
defining a document to querying it.
:::
