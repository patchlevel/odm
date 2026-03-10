<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use DateTimeImmutable;
use InvalidArgumentException;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Patchlevel\Rango\Collection;
use Patchlevel\Rango\Database;

use function base64_decode;
use function base64_encode;

/**
 * @phpstan-type CipherKeyData array{
 *     _id: string,
 *     subject_id: string,
 *     key: non-empty-string,
 *     method: non-empty-string,
 *     created_at: string,
 * }
 */
final readonly class RangoCipherKeyStore implements CipherKeyStore
{
    public function __construct(
        private Database $database,
        private string $collection = '_cipher_keys',
    ) {
    }

    public function get(string $id): CipherKey
    {
        $data = $this->collection()->findOne(['_id' => $id]);

        if ($data === null) {
            throw CipherKeyNotExists::forKeyId($id);
        }

        return $this->hydrate($data);
    }

    public function store(string $id, CipherKey $key): void
    {
        $this->collection()->insertOne([
            '_id' => $id,
            'subject_id' => $key->subjectId,
            'key' => base64_encode($key->key),
            'method' => $key->method,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function remove(string $id): void
    {
        $this->collection()->deleteOne(['_id' => $id]);
    }

    /** @return Collection<CipherKeyData> */
    private function collection(): Collection
    {
        return $this->database->selectCollection($this->collection);
    }

    public function currentKeyFor(string $subjectId): CipherKey
    {
        $data = $this->collection()->findOne(['subject_id' => $subjectId]);

        if ($data === null) {
            throw CipherKeyNotExists::forSubjectId($subjectId);
        }

        return $this->hydrate($data);
    }

    public function removeWithSubjectId(string $subjectId): void
    {
        $this->collection()->deleteMany(['subject_id' => $subjectId]);
    }

    /** @param CipherKeyData $data */
    private function hydrate(array $data): CipherKey
    {
        return new CipherKey(
            $data['_id'],
            $data['subject_id'],
            base64_decode($data['key']) ?: throw new InvalidArgumentException(),
            $data['method'],
            new DateTimeImmutable($data['created_at']),
        );
    }
}
