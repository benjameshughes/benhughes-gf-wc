# Architecture Documentation

## Overview

This plugin has been refactored to use modern PHP 8.2+ features and design patterns for better maintainability, testability, and performance.

## Design Patterns

### 1. Dependency Injection Container (PSR-11)
All services are registered in `ServiceProvider` and resolved via the container.

```php
$container->get(CartService::class); // Automatically resolves dependencies
```

### 2. Repository Pattern
Data access is abstracted through interfaces:
- `FormRepositoryInterface` → `GravityFormsRepository`
- `ProductRepositoryInterface` → `WooCommerceProductRepository`

### 3. Decorator Pattern (Caching)
Repositories are wrapped with caching decorators:
- `CachedFormRepository` wraps `GravityFormsRepository`
- `CachedProductRepository` wraps `WooCommerceProductRepository`

### 4. Value Objects (Immutable DTOs)
- `PriceCalculation` - Calculation results
- `CalculatorConfig` - Field configuration
- `ValidationResult` - Validation outcomes
- `ValidationError` - Individual errors

### 5. Enums for Type Safety
- `MeasurementUnit` - Units with conversion methods
- `ValidationStatus` - Validation states

### 6. Event System (PSR-14 Inspired)
- `EventDispatcher` - Priority-based event handling
- `PriceCalculatedEvent` - Dispatched after calculation
- `ProductAddedToCartEvent` - Dispatched after adding to cart

## Architecture Layers

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│  (REST API, WP Hooks, Admin Pages)      │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│         Service Layer                   │
│    (CartService, ValidationService)     │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│         Business Logic Layer            │
│      (PriceCalculator, Validators)      │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│         Data Access Layer               │
│  (Repositories with Caching Decorators) │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│         Infrastructure                  │
│   (WordPress, WooCommerce, Gravity)     │
└─────────────────────────────────────────┘
```

## Key Components

### Container (`src/Container/`)
- **Container.php** - PSR-11 compatible DI container
- **ServiceProvider.php** - Service registration and bootstrapping

### Repositories (`src/Repositories/`)
- Interfaces abstract data access
- Concrete implementations use WordPress/WooCommerce APIs
- Cached decorators add performance layer

### Services (`src/Services/`)
- **CartService** - Cart operations business logic
- Coordinates between repositories and calculators

### Events (`src/Events/`)
- **EventDispatcher** - Publish/subscribe pattern
- **AbstractEvent** - Base event with propagation control
- Concrete events for extensibility

### Exceptions (`src/Exceptions/`)
- **GravityFormsWCException** - Base exception interface
- **ValidationException** - Validation failures
- **ProductNotFoundException** - Missing products
- **CalculationException** - Calculation errors

### Logging (`src/Logging/`)
- **Logger** - PSR-3 compatible logger
- Logs to WordPress debug.log when `WP_DEBUG_LOG` enabled

### Cache (`src/Cache/`)
- **CacheInterface** - Cache contract
- **WordPressCache** - WordPress object cache implementation
- Supports Redis, Memcached via WordPress

## REST API Endpoints

### POST `/wp-json/gf-wc/v1/calculate-price`
Calculate price for given dimensions.

**Request:**
```json
{
  "width": 60,
  "drop": 72,
  "unit": "in",
  "product_id": 14
}
```

**Response:**
```json
{
  "price": "450.00",
  "regular_price": "450.00",
  "sale_price": "0.00",
  "is_on_sale": false,
  "area": "2.79",
  "width_cm": "152.40",
  "drop_cm": "182.88"
}
```

### POST `/wp-json/gf-wc/v1/add-to-basket`
Add configured product to cart.

**Request:**
```json
{
  "product_id": 14,
  "width": 60,
  "drop": 72,
  "unit": "in",
  "quantity": 1,
  "custom_data": {
    "style": "Full Height",
    "color": "White"
  }
}
```

## Error Handling

All exceptions are caught and logged:

```php
try {
    if (!$productExists) {
        throw ProductNotFoundException::forProductId($id);
    }
} catch (GravityFormsWCException $e) {
    $logger->error($e->getMessage(), $e->getContext());
    return new WP_Error('error_code', $e->getMessage());
}
```

## Caching Strategy

- **TTL**: 1 hour (configurable)
- **Keys**: `product:{id}`, `form:{id}`, `field:{form_id}:{field_id}`
- **Invalidation**: Manual via `clearCache()` methods
- **Backend**: WordPress object cache (supports Redis/Memcached)

## Testing

To run static analysis:
```bash
vendor/bin/phpstan analyse
```

## Performance Optimizations

1. **Object Caching** - Reduces database queries
2. **Lazy Loading** - Services only instantiated when needed
3. **Query Optimization** - Cached repository lookups
4. **Event System** - Decoupled hooks for better performance

## Extension Points

### Adding Custom Events

```php
// Register listener
$dispatcher->addListener(
    PriceCalculatedEvent::class,
    function(PriceCalculatedEvent $event) {
        // Custom logic
    },
    10 // priority
);
```

### Adding Custom Validators

```php
$validator->addValidator(function($config) {
    // Custom validation logic
    return ValidationResult::success();
});
```

### Replacing Repositories

```php
// In ServiceProvider
$container->register(
    ProductRepositoryInterface::class,
    fn() => new CustomProductRepository()
);
```

## Code Style

- **PHP 8.2+** features (enums, readonly, named arguments)
- **Strict typing** (`declare(strict_types=1)`)
- **WordPress Coding Standards**
- **PSR-4 Autoloading**
- **Comprehensive PHPDoc**

## Dependencies

- PHP 8.2+
- WordPress 6.0+
- WooCommerce 7.0+
- Gravity Forms 2.5+
