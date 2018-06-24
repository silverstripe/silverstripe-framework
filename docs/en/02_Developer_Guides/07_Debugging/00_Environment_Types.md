title: Environment Types
summary: Configure your SilverStripe environment to define how your web application behaves.

# Environment Types

SilverStripe knows three different environment types (or "modes"). Each of the modes gives you different tools
and behaviors. The environment is managed by the `SS_ENVIRONMENT_TYPE` variable through an 
[environment configuration file](../../getting_started/environment_management).
The three environment types you can set are `dev`, `test` and `live`.

### Dev

When developing your websites, adding page types or installing modules you should run your site in `dev`. In this mode
you will see full error back traces and view the development tools without having to be logged in as an administrator 
user.

<div class="alert" markdown="1">
**dev mode should not be enabled long term on live sites for security reasons**. In dev mode by outputting back traces 
of function calls a hacker can gain information about your environment (including passwords) so you should use dev mode 
on a public server very carefully.
</div>

### Test Mode

Test mode is designed for staging environments or other private collaboration sites before deploying a site live.

In this mode error messages are hidden from the user and SilverStripe includes [BasicAuth](api:SilverStripe\Security\BasicAuth) integration if you 
want to password protect the site. You can enable that by adding this to your `app/_config/app.yml` file:


```yml
---
Only:
  environment: 'test'
---
SilverStripe\Security\BasicAuth:
  entire_site_protected: true
```

### Live Mode

All error messages are suppressed from the user and the application is in it's most *secure* state.

<div class="alert">
Live sites should always run in live mode. You should not run production websites in dev mode.
</div>


## Checking Environment Type

You can check for the current environment type in [config files](../configuration) through the `environment` variant.

**app/_config/app.yml**

```yml
---
Only:
  environment: 'live'
---
MyClass:
  myvar: live_value
---
Only:
  environment: 'test'
---
MyClass:
  myvar: test_value
```
Checking for what environment you're running in can also be done in PHP. Your application code may disable or enable 
certain functionality depending on the environment type.

```php
use SilverStripe\Control\Director;

if (Director::isLive()) {
    // is in live
} elseif (Director::isTest()) {
    // is in test mode
} elseif (Director::isDev()) {
    // is in dev mode
}
```

## Related Lessons
* [Advanced environment configuration](https://www.silverstripe.org/learn/lessons/v4/advanced-environment-configuration-1)
