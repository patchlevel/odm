<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Middleware\Middleware;
use Patchlevel\Hydrator\Middleware\Stack;
use Patchlevel\ODM\Metadata\DocumentMetadata;

class ODMMiddleware implements Middleware
{
    private const ID_FIELD_NAME = '_id';

    /**
     * @param ClassMetadata<T>     $metadata
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return T
     *
     * @template T of object
     */
    public function hydrate(ClassMetadata $metadata, array $data, array $context, Stack $stack): object
    {
        $documentMetadata = $context[DocumentMetadata::class] ?? null;

        if (!$documentMetadata instanceof DocumentMetadata) {
            return $stack->next()->hydrate($metadata, $data, $context, $stack);
        }

        unset($context[DocumentMetadata::class]);

        $fieldName = $metadata->properties[$documentMetadata->idProperty]->fieldName;

        $data[$fieldName] = $data[self::ID_FIELD_NAME];
        unset($data[self::ID_FIELD_NAME]);

        return $stack->next()->hydrate($metadata, $data, $context, $stack);
    }

    /**
     * @param ClassMetadata<T>     $metadata
     * @param T                    $object
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     *
     * @template T of object
     */
    public function extract(ClassMetadata $metadata, object $object, array $context, Stack $stack): array
    {
        $documentMetadata = $context[DocumentMetadata::class] ?? null;

        if (!$documentMetadata instanceof DocumentMetadata) {
            return $stack->next()->extract($metadata, $object, $context, $stack);
        }

        unset($context[DocumentMetadata::class]);

        $data = $stack->next()->extract($metadata, $object, $context, $stack);

        $fieldName = $metadata->properties[$documentMetadata->idProperty]->fieldName;

        $data[self::ID_FIELD_NAME] = $data[$fieldName];
        unset($data[$fieldName]);

        return $data;
    }
}
