# plugins/

Third-party (plug-and-play) plugins for Papastro.

Each plugin lives in its own directory under `plugins/` and is auto-discovered
when it contains a `plugin.json` manifest:

```
plugins/
└── AcmeWidget/
    ├── plugin.json
    ├── autoload.php        (optional)
    ├── AcmeWidgetServiceProvider.php
    └── Routes/
        └── web.php
```

Example `plugin.json`:

```json
{
    "name": "AcmeWidget",
    "version": "1.0.0",
    "enabled": true,
    "requires": []
}
```

Plugins are loaded by `Papastro\Plugin\PluginLoader` (registered via
`App\Providers\PluginServiceProvider`) and can register routes, service providers
and their own autoloaders, keeping the core framework and first-party modules
untouched.

This directory is only active when the project was installed in `full` mode
(`PAPASTRO_MODE=full` and `PLUGINS_ENABLED=true` in `.env`). Projects installed
in `modules` mode keep this folder untouched but ignore it. Switch modes with:

```bash
php papastro install --mode=full     # enable plugins
php papastro install --mode=modules  # modules only
```
