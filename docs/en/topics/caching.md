# Caching

## Built-In Caches

The framework uses caches to store infrequently changing values.
By default, the storage mechanism is simply the filesystem, although
other cache backends can be configured. All caches use the `[api:SS_Cache]` API.

The most common caches are manifests of various resources: 

 * PHP class locations (`[api:SS_ClassManifest]`)
 * Template file locations and compiled templates (`[api:SS_TemplateManifest]`)
 * Configuration settings from YAML files (`[api:SS_ConfigManifest]`)
 * Language files (`[api:i18n]`)

Flushing the various manifests is performed through a GET
parameter (`flush=1`). Since this action requires more server resources than normal requests,
executing the action is limited to the following cases when performed via a web request:

 * The [environment](/topics/environment-management) is in "dev mode"
 * A user is logged in with ADMIN permissions
 * An error occurs during startup

## Custom Caches

See `[api:SS_Cache]`.