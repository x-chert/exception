<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\Dummy;

use Xchert\Exception\Attribute\AsErrorMapper;

#[AsErrorMapper(id: 'attributed', priority: 5000)]
class AttributedErrorMapper extends FixedErrorMapper
{
    public function __construct()
    {
        parent::__construct('ATTRIBUTED', AttributedException::class, 409, ['X-Mapper' => 'attributed']);
    }
}
