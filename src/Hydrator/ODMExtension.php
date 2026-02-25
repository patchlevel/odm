<?php

namespace Patchlevel\ODM\Hydrator;

use Patchlevel\Hydrator\Extension;
use Patchlevel\Hydrator\HydratorBuilder;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;

final class ODMExtension implements Extension
{
    public function __construct(
        private readonly DocumentMetadataFactory $factory = new AttributeDocumentMetadataFactory()
    ) {
    }

    public function configure(HydratorBuilder $builder): void
    {
        $builder->addMetadataEnricher(new ODMMappingMetadataEnricher($this->factory));
    }
}