# Encryption

Patchlevel ODM can transparently encrypt sensitive document fields using the cryptography extension
of [patchlevel/hydrator](https://github.com/patchlevel/hydrator/). Each data subject gets its own
encryption key, stored separately from the documents. Deleting a subject's key makes their encrypted
data unrecoverable, a technique known as crypto shredding that helps with data deletion requests.

## How it works

You mark one property as the data subject id and the sensitive properties as encrypted. On write, the
sensitive values are encrypted with the subject's key. On read, they are decrypted again. The keys
live in a separate collection, managed by a key store that ships with the ODM for each backend.

## Marking sensitive data

Use the `#[DataSubjectId]` attribute to identify the subject and `#[SensitiveData]` on the properties
that should be encrypted. You can provide a fallback value that is returned when the key is gone.

```php
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('profiles')]
final class Profile
{
    public function __construct(
        #[Id]
        #[DataSubjectId]
        public readonly string $id,
        #[SensitiveData]
        public string $name,
        #[SensitiveData(fallback: 'unknown')]
        public string $email,
    ) {
    }
}
```
:::note
The fallback is used when the subject's key has been deleted, so the document still hydrates after the
encrypted data became unreadable.
:::

## Setting up the hydrator

Encryption is configured on the hydrator, which you then pass to the repository manager's `create()`
factory. Build the hydrator with the `CryptographyExtension` and the key store for your backend. Each
backend ships its own key store; the rest of the setup is the same.

For PostgreSQL via Rango:

```php
use Patchlevel\Hydrator\Extension\Cryptography\BaseCryptographer;
use Patchlevel\Hydrator\Extension\Cryptography\CryptographyExtension;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Patchlevel\ODM\Hydrator\RangoCipherKeyStore;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

$client = new Client($_ENV['POSTGRES_URI']);

$keyStore = new RangoCipherKeyStore($client->selectDatabase('public'));
$cryptographer = BaseCryptographer::createWithOpenssl($keyStore);

$hydrator = (new StackHydratorBuilder())
    ->useExtension(new CryptographyExtension($cryptographer))
    ->build();

$manager = RangoRepositoryManager::create($client, $hydrator);
```
For MongoDB:

```php
use MongoDB\Client;
use Patchlevel\Hydrator\Extension\Cryptography\BaseCryptographer;
use Patchlevel\Hydrator\Extension\Cryptography\CryptographyExtension;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Patchlevel\ODM\Hydrator\MongoDBCipherKeyStore;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;

$client = new Client($_ENV['MONGODB_URI']);

$keyStore = new MongoDBCipherKeyStore($client->selectDatabase('default'));
$cryptographer = BaseCryptographer::createWithOpenssl($keyStore);

$hydrator = (new StackHydratorBuilder())
    ->useExtension(new CryptographyExtension($cryptographer))
    ->build();

$manager = MongoDBRepositoryManager::create($client, $hydrator);
```
:::note
The cipher key store is the only backend-specific part. The `#[DataSubjectId]` and `#[SensitiveData]`
attributes and everything else work the same on both.
:::

## Storing and loading

Once configured, encryption is transparent. You store and load documents exactly as before, and the
sensitive fields are encrypted at rest.

```php
$repository = $manager->get(Profile::class);

$repository->insert(new Profile('r-1', 'Rango', 'rango@example.com'));

$profile = $repository->get('r-1');   // name and email are decrypted
```
## Crypto shredding

To erase a subject's data, delete their key from the key store. The encrypted fields can no longer be
decrypted, and the fallback value is returned instead.

```php
$keyStore->removeWithSubjectId('r-1');
```
:::danger
Removing a key is irreversible. The encrypted data stays in the document but can never be decrypted
again.
:::

## Learn more

* [How fields are mapped and normalized](field-mapping.md)
* [How to define documents](documents.md)
* [How to choose a database backend](databases.md)
