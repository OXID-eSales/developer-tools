# Developer Tools component

This component contains additional developer tools for OXID eShop.

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
bin/oe-console oe:database:reset --db-host DB-HOST --db-port DB-PORT --db-name DB-NAME --db-user DB-USER --db-password DB-PASSWORD [--force]
```

Which:

    - <db-host> is the database host
    - <db-port> is the database port
    - <db-name> is the database name
    - <db-user> is the database username
    - <db-password> is the database password
    - [<force>] (optional) if set runs the command without confirmation

Example:

```bash
bin/oe-console oe:database:reset --db-host=localhost --db-port=3306 --db-name=test --db-user=test --db-password=test 
```

ATTENTION: After running this command, all you data will be deleted from the database. Never run this command on life system.

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
