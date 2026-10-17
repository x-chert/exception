# xchert/exception

A Symfony bundle for structured exception handling. It maps any exception to error objects and turns them into JSON
responses following [RFC 9457 (Problem Details for HTTP APIs)](https://www.rfc-editor.org/rfc/rfc9457).

## Requirements

- PHP 8.4 or higher
- Symfony 8.1 or higher

## Installation

### 1. Install via Composer

```bash
composer require xchert/exception
```

### 2. Register the Bundle

If you are not using Symfony Flex, register the bundle manually in `config/bundles.php`:

```php
// config/bundles.php

return [
    // ...
    Xchert\Exception\ExceptionBundle::class => ['all' => true],
];
```

### 3. Configuration (optional)

RFC 9457 identifies a problem type by a URI in the `type` member. If an absolute base URI is configured, the bundle
appends the error code to it. Without configuration, `type` is always `about:blank`.

```yaml
# config/packages/exception.yaml
exception:
    problem_type_base_uri: 'https://example.com/problems/'
```

An exception with the error code `USER_NOT_FOUND` then gets the type `https://example.com/problems/USER_NOT_FOUND`.
Errors without a known error code (`UNKNOWN_ERROR`) still use `about:blank`. Ideally, the URI resolves to a
human-readable description of the problem.

The base URI must be absolute (e.g. `https://…` or `urn:…`) and should end with a separator such as `/`, because the
error code is appended directly.

## Usage

### 1. Custom Exceptions

Custom exceptions extend `ErrorException`. They define the error code and optionally the HTTP status code (default:
`500`). Placeholders like `{{ name }}` in the message are replaced with the given parameters.

```php
namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\ErrorException;

class UserNotFoundException extends ErrorException
{
    public function __construct(string $userId, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: 'User with ID "{{ userId }}" was not found.',
            title: 'User not found',
            parameters: ['userId' => $userId],
            previous: $previous,
        );
    }

    public function getErrorCode(): string
    {
        return 'USER_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
```

The constructor of `ErrorException` takes:

| Parameter     | Description                                                |
|---------------|------------------------------------------------------------|
| `$message`    | Message, optionally with placeholders `{{ name }}`         |
| `$title`      | Short, human-readable summary of the problem               |
| `$parameters` | Values for the placeholders, also included in the response |
| `$headers`    | Additional HTTP headers for the response                   |
| `$previous`   | The exception that caused this one                         |

Every `ErrorException` gets a unique ID (UUID v7) and a timestamp when it is created. The ID appears in the response as
`instance` (`urn:uuid:<id>`) and helps to find the error in your logs, for example.

### 2. The ErrorFactory

The `ErrorFactory` is available as a service and turns an exception into error objects or a response:

| Method                               | Returns                                                                 |
|--------------------------------------|-------------------------------------------------------------------------|
| `createResponse($exception, $debug)` | `JsonResponse` with `application/problem+json`, status code and headers |
| `createError($exception, $debug)`    | A single `Error` object for the response                                |
| `createErrors($exception, $debug)`   | Flat list of all `Error` objects created by the mappers                 |
| `getStatusCode($exception)`          | HTTP status code                                                        |
| `getErrorCode($exception)`           | Error code                                                              |
| `getHeaders($exception)`             | HTTP headers                                                            |

`createError()` returns the error as is if `createErrors()` yields exactly one. Otherwise, it creates an error for the
thrown exception that contains the list in its `errors` member.

With `$debug = true`, the stack trace is included under `meta.trace`. Enable this in development only.

#### In a Controller

```php
use Xchert\Exception\ErrorFactory\ErrorFactory;

public function show(string $id, ErrorFactory $errorFactory): Response
{
    try {
        // ...
    } catch (\Throwable $e) {
        return $errorFactory->createResponse($e, $this->getParameter('kernel.debug'));
    }
}
```

#### For All Exceptions of the Application

The bundle does not register an exception listener itself. To return every exception as problem details, add your own
listener:

```php
namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Xchert\Exception\ErrorFactory\ErrorFactory;

#[AsEventListener]
final readonly class ProblemDetailsListener
{
    public function __construct(
        private ErrorFactory $errorFactory,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $event->setResponse($this->errorFactory->createResponse($event->getThrowable(), $this->debug));
    }
}
```

### 3. Response Format

The members of an error are filled as follows:

| Member            | Content                                                          |
|-------------------|------------------------------------------------------------------|
| `instance`        | `urn:uuid:<id>`, unique ID of the error                          |
| `type`            | Base URI + error code, or `about:blank`                          |
| `title`           | Title of the exception; the HTTP status text for HTTP exceptions |
| `detail`          | Message of the exception                                         |
| `status`          | HTTP status code                                                 |
| `code`            | Error code (extension)                                           |
| `meta.occurredAt` | Time the error occurred (extension)                              |
| `meta.trace`      | Stack trace, debug mode only (extension)                         |
| `additional`      | Additional data, e.g. `parameters` or `pointer` (extension)      |
| `errors`          | Nested errors (extension)                                        |

Empty members (`additional`, `errors`, `meta.trace`) are omitted.

Example for the `UserNotFoundException` shown above:

```json
{
	"instance": "urn:uuid:01a14b4e-a3ec-7198-92a5-2becc4710e9b",
	"type": "https://example.com/problems/USER_NOT_FOUND",
	"title": "User not found",
	"detail": "User with ID \"42\" was not found.",
	"status": 404,
	"code": "USER_NOT_FOUND",
	"meta": {
		"occurredAt": "2026-10-17T19:20:07.404+00:00"
	},
	"additional": {
		"parameters": {
			"userId": "42"
		}
	}
}
```

## Built-in Mappers

Mappers translate an exception into `Error` objects. For each exception, the first supporting mapper with the highest
priority is used. Exceptions without a supporting mapper become an error with the code `UNKNOWN_ERROR` and the status
`500`.

| Mapper                               | Priority | Handles                        |
|--------------------------------------|----------|--------------------------------|
| `StackExceptionMapper`               | 1100     | `StackException`               |
| `ConstraintViolationExceptionMapper` | 1100     | `ConstraintViolationException` |
| `ErrorExceptionMapper`               | 1000     | `ErrorExceptionInterface`      |
| `HttpExceptionMapper`                | 0        | Symfony's `HttpException`      |

For HTTP exceptions and `ErrorException`s, a `previous` exception is mapped as well and nested under `errors`.

### Validation Errors

Throw Symfony validation errors with the `ConstraintViolationException`:

```php
use Xchert\Exception\Validation\ConstraintViolationException;

$violations = $validator->validate($data);

if (\count($violations) > 0) {
    throw new ConstraintViolationException($violations, $data);
}
```

Each violation becomes its own error with the status `400`. The error code is the error name of the constraint (e.g.
`IS_BLANK_ERROR`), or `CONSTRAINT_VIOLATION` if none can be determined. The property path is included as a
[JSON Pointer (RFC 6901)](https://www.rfc-editor.org/rfc/rfc6901) under `additional.pointer`, e.g. `address.street`
becomes `/address/street`.

```json
{
	"instance": "urn:uuid:01a14b4e-a3eb-7a72-9bdc-f4558c7a08a9",
	"type": "https://example.com/problems/CONSTRAINT_VIOLATION",
	"title": "Constraint validation failed.",
	"detail": "Constraint validation failed. Caught 2 errors:\n • [username]: This value should not be blank.\n • [email]: This value is not a valid email address.\n",
	"status": 400,
	"code": "CONSTRAINT_VIOLATION",
	"meta": {
		"occurredAt": "2026-10-17T19:20:07.403+00:00"
	},
	"additional": {
		"parameters": {
			"errorCount": 2,
			"violationsMessage": " • [username]: This value should not be blank.\n • [email]: This value is not a valid email address.\n"
		}
	},
	"errors": [
		{
			"instance": "urn:uuid:01a14b4e-a3eb-7ba6-9bdc-f4558d5176a4",
			"type": "https://example.com/problems/IS_BLANK_ERROR",
			"title": "IS_BLANK_ERROR",
			"detail": "This value should not be blank.",
			"status": 400,
			"code": "IS_BLANK_ERROR",
			"meta": {
				"occurredAt": "2026-10-17T19:20:07.403+00:00"
			},
			"additional": {
				"parameters": {
					"{{ value }}": "\"\""
				},
				"pointer": "/username"
			}
		},
		{
			"instance": "urn:uuid:01a14b4e-a3eb-7c0a-9bdc-f4558dc8e612",
			"type": "https://example.com/problems/INVALID_FORMAT_ERROR",
			"title": "INVALID_FORMAT_ERROR",
			"detail": "This value is not a valid email address.",
			"status": 400,
			"code": "INVALID_FORMAT_ERROR",
			"meta": {
				"occurredAt": "2026-10-17T19:20:07.403+00:00"
			},
			"additional": {
				"parameters": {
					"{{ value }}": "\"foo\""
				},
				"pointer": "/email"
			}
		}
	]
}
```

### Collecting Multiple Errors

The `ExceptionStack` collects multiple exceptions and throws them together as a `StackException`. Each collected
exception is mapped by its supporting mapper and included under `errors`.

```php
use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\ExceptionStack;

$stack = new ExceptionStack(
    errorCode: 'IMPORT_FAILED',
    title: 'Import failed',
    statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
);

foreach ($rows as $row) {
    try {
        $importer->import($row);
    } catch (\Throwable $e) {
        $stack->add($e);
    }
}

$stack->throw(); // Throws only if errors were collected
```

Without a custom message, the message is composed of the messages of the collected exceptions. The title defaults to
`Multiple errors occurred.`, the status code to `500`.

## Custom Mappers

Write a custom mapper for exceptions from third-party libraries or your own domain. It implements the
`ErrorMapperInterface`:

```php
namespace App\ErrorMapper;

use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\ErrorMapperInterface;
use Xchert\Exception\Util;

#[AsErrorMapper(id: 'domain_exception', priority: 500)]
class DomainExceptionMapper implements ErrorMapperInterface
{
    private const string CODE = 'DOMAIN_ERROR';

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof \DomainException;
    }

    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        return [$this->createError($exception, $debug, [], $factory)];
    }

    public function createError(\Throwable $exception, bool $debug, array $errors, ErrorFactory $factory): Error
    {
        return new Error(
            instance: Util::createInstance(),
            type: $factory->createType(self::CODE),
            title: $this->getTitle($exception),
            detail: $exception->getMessage(),
            status: $this->getStatusCode($exception),
            code: self::CODE,
            occurredAt: new \DateTimeImmutable(),
            trace: $debug ? $exception->getTrace() : null,
            errors: $errors,
        );
    }

    public function getStatusCode(\Throwable $exception): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function getHeaders(\Throwable $exception): array
    {
        return [];
    }

    public function getErrorCode(\Throwable $exception): string
    {
        return self::CODE;
    }

    public function getTitle(\Throwable $exception): string
    {
        return 'Domain rule violated';
    }
}
```

| Method                                                            | Purpose                                                                                                   |
|-------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| `supports()`                                                      | Checks whether the mapper handles the exception                                                           |
| `getErrors()`                                                     | Returns the errors for the exception. Multiple entries are wrapped in an error created by `createError()` |
| `createError()`                                                   | Creates the error for the exception, with `$errors` as nested errors                                      |
| `getStatusCode()`, `getHeaders()`, `getErrorCode()`, `getTitle()` | Return the respective values for the response                                                             |

If the behavior is similar, a mapper can also extend one of the built-in mappers, e.g. the `HttpExceptionMapper`.

### Registering a Mapper

With autoconfigure enabled (the default in Symfony applications), the `#[AsErrorMapper]` attribute is all you need. The
`id` must be unique; a mapper with the same `id` replaces one that is already registered. The `priority` defines the
order in which the mappers are checked. To override a built-in mapper, choose a higher priority.

Without autoconfigure, tag the mapper manually with `exception.error_mapper`:

```yaml
# config/services.yaml
services:
    App\ErrorMapper\DomainExceptionMapper:
        tags:
            - { name: 'exception.error_mapper', id: 'domain_exception', priority: 500 }
```