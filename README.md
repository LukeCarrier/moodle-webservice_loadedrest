# Loaded REST

A Moodle webservice protocol implementation that allows requests with bodies. It's not true REST as it's not resourceful.

---

## Installation

1. Place this repository in `/webservice/loadedrest`.
2. Execute the Moodle upgrades via the CLI or web UI.

## Configuration

1. Ensure _Enable web services_ is enabled under _Site administration_ > _Advanced features_.
2. Navigate to _Site administration_ > _Plugins_ > _Web services_ > _Manage protocols_ and enable _Loaded REST protocol_.
3. Assign the _Use Loaded REST protocol_ capability to the desired roles under _Site administration_ > _Users_ > _Permissions_ > _Define roles_.

## Sample client usage

```php
use webservice_loadedrest\client;

$serverurl = '/webservice/loadedrest/server.php';
$token = '000000000000000000000000000000000000';
$httpmethod = 'PUT';
$wsfunction = 'component_function';
$params = [
    'myargs' => [
        [
            'param' => 'look, nesting!',
        ],
    ],
];

$client = new client($serverurl, $token);
try {
    var_dump($client->call($httpmethod, $wsfunction, $params));
} catch (Exception $e) {
    var_dump($e);
}
```
