[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fpatchlevel%2Fodm%2F1.0.x)](https://dashboard.stryker-mutator.io/reports/github.com/patchlevel/odm/1.0.x)
[![Latest Stable Version](https://poser.pugx.org/patchlevel/odm/v)](//packagist.org/packages/patchlevel/odm)
[![License](https://poser.pugx.org/patchlevel/odm/license)](//packagist.org/packages/patchlevel/odm)

# Patchlevel ODM

Patchlevel ODM is a lightweight **Object Document Mapper (ODM)** for PHP that works with **MongoDB** and **PostgreSQL** (via [patchlevel/rango](https://github.com/patchlevel/rango/)). It is built on top of our superfast **[patchlevel/hydrator](https://github.com/patchlevel/hydrator/)**, providing a simple attribute-based mapping layer and enterprise-grade features like cryptography.

Unlike Doctrine ODM, Patchlevel ODM has **no Unit of Work**. Repositories control persistence explicitly, so every write is deliberate and easy to reason about, which makes the library a good fit for long-running worker processes.

## Features

* [MongoDB and PostgreSQL support](https://patchlevel.dev/docs/odm/latest/databases) with a single, consistent API
* [Attribute-based document mapping](https://patchlevel.dev/docs/odm/latest/documents) with `#[Document]` and `#[Id]`
* [Repositories without a Unit of Work](https://patchlevel.dev/docs/odm/latest/repository) for predictable writes
* [Querying](https://patchlevel.dev/docs/odm/latest/repository#querying) with filters, sorting and pagination
* [Indexes](https://patchlevel.dev/docs/odm/latest/documents#indexes) defined with `#[Index]`, including unique constraints
* [Optimistic locking](https://patchlevel.dev/docs/odm/latest/documents#versioning) with `#[Version]` to catch concurrent writes
* [Field mapping and normalization](https://patchlevel.dev/docs/odm/latest/field-mapping) for nested objects and custom field names
* [Encryption and crypto shredding](https://patchlevel.dev/docs/odm/latest/encryption) for sensitive data

## Installation

```bash
composer require patchlevel/odm
```

## Documentation

* Latest [Docs](https://patchlevel.dev/docs/odm/latest)
* Related [Blog](https://patchlevel.dev/blog)

## Integration

* [patchlevel/hydrator](https://github.com/patchlevel/hydrator)
* [patchlevel/rango](https://github.com/patchlevel/rango)

## Contributing

We are open to contributions as long as they are in line with
our [BC-Policy](https://patchlevel.dev/our-backward-compatibility-promise).

Also note that the `composer.lock` is always generated with the newest supported PHP version as this is the version our tools run in the CI.
