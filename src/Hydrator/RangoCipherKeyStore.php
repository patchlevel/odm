<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Patchlevel\Rango\Collection;
use Patchlevel\Rango\Database;

use function base64_decode;
use function base64_encode;

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
            throw new CipherKeyNotExists($id);
        }

        return new CipherKey(
            base64_decode($data['key']) ?: throw new CipherKeyNotExists($id),
            $data['method'],
            base64_decode($data['iv']) ?: throw new CipherKeyNotExists($id),
        );
    }

    public function store(string $id, CipherKey $key): void
    {
        $this->collection()->insertOne([
            '_id' => $id,
            'key' => base64_encode($key->key),
            'method' => $key->method,
            'iv' => base64_encode($key->iv),
        ]);
    }

    public function remove(string $id): void
    {
        $this->collection()->deleteOne(['_id' => $id]);
    }

    /**
     * @return Collection<array{
     *     _id: string,
     *     key: non-empty-string,
     *     method: non-empty-string,
     *     iv: non-empty-string,
     * }>
     */
    private function collection(): Collection
    {
        return $this->database->selectCollection($this->collection);
    }
}
