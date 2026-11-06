<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\Hydrator\Normalizer\Normalizer;
use RuntimeException;

use function sprintf;

final class DocumentHasClassNormalizer extends RuntimeException
{
    /**
     * @param class-string             $className
     * @param class-string<Normalizer> $normalizerClass
     */
    public function __construct(string $className, string $normalizerClass)
    {
        parent::__construct(sprintf(
            'The document "%s" has the class level normalizer "%s". Documents are stored field by field and can not be normalized as a whole.',
            $className,
            $normalizerClass,
        ));
    }
}
