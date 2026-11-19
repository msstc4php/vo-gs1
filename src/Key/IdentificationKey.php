<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

interface IdentificationKey
{
    /**
     * The GS1 Prefix range the key's leading digits fall in.
     */
    public function gs1Prefix(): Gs1Prefix;
}
