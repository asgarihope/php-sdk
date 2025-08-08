# Token Management in Rade Services

## Introduction
You need to use an authentication token to access all Rade API services.
This functionality is automatically integrated into this package, and the token is stored as a file.
Usually, in large and sensitive projects, developers prefer to manage token storage and even encrypt tokens. Therefore, in this package, you can implement the `TokenManagerInterface` to manage this capability if desired.

Important note:
To prevent polymorphism issues and ensure the package continues to function properly, please pay careful attention to generating the correct DTO for return values.

## Token Management Structure
The token management system in this package is designed in a modular way and consists of two main parts:

1. `saveToken` which is responsible for storing the token according to your needs, and must return the token as a DTO in the output.
2. `loadToken` which is responsible for loading the token from your system, and must also return the related DTO in the output.

## Usage

### Custom TokenManager Implementation

If you want to store the token somewhere other than a file (e.g., database):

```php
<?php

use Radeir\DTOs\RadeTokenDTO;
use Radeir\Services\RadeServices;
use Radeir\Services\TokenManager\TokenManagerInterface;

class DatabaseTokenManager implements TokenManagerInterface
{
    // Implementation for storing token in database
    public function saveToken(string $access_token, string $expire_at): RadeTokenDTO
    {
        // Code to save token in database
        
        $tokenDTO = new RadeTokenDTO();
        $tokenDTO->setAccessToken($access_token);
        $tokenDTO->setExpireAt($expire_at);
        return $tokenDTO;
    }
    
    public function loadToken(): ?RadeTokenDTO
    {
        // Code to retrieve token from database
        $access_token = ....;
        $expire_at = ....;
        // Generate DTO for code uniformity
        if ($token && $expire_at) {
            $tokenDTO = new RadeTokenDTO();
            $tokenDTO->setAccessToken($access_token);
            $tokenDTO->setExpireAt($expire_at);
            return $tokenDTO;
        } else {
            // If token doesn't exist or is expired, return null to trigger server token acquisition
            return null;
        }
    }
}

// Using custom token manager
$config = [...]; // Configuration
$tokenManager = new DatabaseTokenManager();
$radeServices = new RadeServices($config, $tokenManager);
```

### Using Custom TokenManager in Laravel:

```php
<?php

// In a ServiceProvider
public function register()
{
    $this->app->bind(\Radeir\Services\TokenManager\TokenManagerInterface::class, function ($app) {
        return new DatabaseTokenManager();
    });
}
```

## Error Management

This package manages various errors in a structured way:

- **RadeException**: Base exception for all package errors
- **RadeClientException**: Client-side errors (such as invalid parameters)
- **RadeServiceException**: API server-side errors
