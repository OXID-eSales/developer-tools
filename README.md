# Developer Tools component

This component contains additional developer tools for OXID eShop.

__ATTENTION:__ this component was designed for development purposes and its usage may lead to data loss in your system.
__Never execute developer tool commands in any live environment!__

## Installation

Run the following command to install the component:

```bash
composer require oxid-esales/developer-tools
```

## Usage

### Resetting project configuration
To reset project configuration to its initial state execute:

```bash
bin/oe-console oe:module:reset-configurations 
```

### Resetting/installing shop database
To reset the database to its initial state run the following command:

```bash
bin/oe-console oe:database:reset [--force]
```

_When executed, this command will use the actual database settings, active in your current system environment._


## How to install component for development?

Checkout component besides OXID eShop `source` directory:

```bash
git clone https://github.com/OXID-eSales/developer-tools.git
```

Run composer install command:

```bash
cd developer-tools
composer install
```

Add dependency to OXID eShop `composer.json` file:

```bash
composer config repositories.oxid-esales/developer-tools path developer-tools
composer require --dev oxid-esales/developer-tools:*
```

## How to run tests?

To run tests for the component please define OXID eShop bootstrap file:

```bash
vendor/bin/phpunit --bootstrap=../source/bootstrap.php tests/
```

## License

See [LICENSE](LICENSE) file for license details.
