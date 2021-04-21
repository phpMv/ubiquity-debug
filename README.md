![img](https://github.com/phpMv/ubiquity-debug/blob/main/.github/images/debugger.png?raw=true)

[![Latest Stable Version](https://poser.pugx.org/phpmv/ubiquity-debug/v/stable)](https://packagist.org/packages/phpmv/ubiquity-debug)

Debugger for Ubiquity framework

# Integration
## For an existing project

In `.ubiquity/_index.php` file:

```php
try {
	\Ubiquity\controllers\Startup::run($config);
}catch(\Error|\Exception $e){
	\Ubiquity\debug\Debugger::showException($e);
}
```

In `app/config/services.php`:
```php
\Ubiquity\debug\Debugger::start();
```

## For a new project (since Ubiquity 2.4.4)
Nothing to do: The debugger is active by default with the php built-in server.

```bash
Ubiquity serve
```
