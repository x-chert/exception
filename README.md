# xchert/exception

A Symfony bundle for structured exception handling, error mapping, and JSON-serializable error responses.

## Installation

### 1. Install via Composer

Run the following command in your terminal:

```bash
composer require xchert/exception
```

### 2. Register the Bundle

If you are not using Symfony Flex, you need to manually register the bundle in your `config/bundles.php` file:

```php
// config/bundles.php

return [
    // ...
    Xchert\Exception\ExceptionBundle::class => ['all' => true],
];
```

## Usage

### 1. Creating Custom Exceptions

To create structured exceptions, extend the `ErrorException` class. This allows you to define a custom error code, HTTP status code, and parameters for message placeholders.

```php
namespace App\Exception;

use Xchert\Exception\ErrorException;
use Symfony\Component\HttpFoundation\Response;

class UserNotFoundException extends ErrorException
{
    public function __construct(string $userId)
    {
        parent::__construct(
            'User with ID "{{ userId }}" was not found.',
            ['userId' => $userId]
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

### 2. Implementing Error Mappers

Error Mappers transform any `\Throwable` into one or more `Error` objects. This is useful for mapping third-party exceptions or domain-specific exceptions.

#### Example Mapper

```php
namespace App\ErrorMapper;

use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\ErrorMapperInterface;
use Symfony\Component\HttpFoundation\Response;

class CustomDomainExceptionMapper implements ErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof \DomainException;
    }

    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        return [
            new Error(
                bin2hex(random_bytes(16)),
                'DOMAIN_ERROR',
                $exception->getMessage(),
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $debug ? $exception->getTrace() : null,
                'DomainException'
            )
        ];
    }
}
```

#### Registering the Mapper

Register your mapper as a service and tag it with `xch.exception.error_mapper`.

```yaml
# config/services.yaml
services:
    App\ErrorMapper\CustomDomainExceptionMapper:
        tags:
            - { name: 'xch.exception.error_mapper', id: 'custom_domain_mapper', priority: 100 }
```

### 3. Using the ErrorFactory

The `ErrorFactory` processes exceptions and returns an array of `Error` objects.

```php
use Xchert\Exception\ErrorFactory\ErrorFactory;

public function someControllerMethod(ErrorFactory $errorFactory)
{
    try {
        // ... some logic
    } catch (\Throwable $e) {
        $errors = $errorFactory->getErrors($e, $this->getParameter('kernel.debug'));
        
        return $this->json(['errors' => $errors], $errors[0]->getStatusCode());
    }
}
```

## Additional Features

### Validation Exceptions

Handle Symfony validation errors using `ConstraintViolationException`.

```php
use Xchert\Exception\Validation\ConstraintViolationException;

$violations = $validator->validate($data);

if (count($violations) > 0) {
    throw new ConstraintViolationException($violations, $data);
}
```

### Exception Stack

Collect multiple exceptions and throw them together as a `StackException`.

```php
use Xchert\Exception\ExceptionStack;

$stack = new ExceptionStack('Multiple errors occurred.');

if ($error1) {
    $stack->add(new \Exception('First error'));
}

if ($error2) {
    $stack->add(new \Exception('Second error'));
}

$stack->throw(); // Throws StackException if errors were added
```
