<?php
/*
 * Copyright 2015-present MongoDB, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace MongoDB\Exception;

use function get_debug_type;
use function sprintf;

class ResumeTokenException extends RuntimeException
{
    /**
     * Thrown when a resume token has an invalid type.
     *
     * @param mixed $value Actual value (used to derive the type)
     * @internal
     */
    public static function invalidType(mixed $value): self
    {
        return new self(sprintf('Expected resume token to have type "array or object" but found "%s"', get_debug_type($value)));
    }

    /**
     * Thrown when a resume token is not found in a change document.
     *
     * @internal
     */
    public static function notFound(): self
    {
        return new self('Resume token not found in change document');
    }
}
