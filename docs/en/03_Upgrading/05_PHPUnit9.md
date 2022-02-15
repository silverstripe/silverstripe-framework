---
title: Upgrading to PHPUnit 9.5 for PHP8 support
summary: Guidance on upgrading your project or module to use PHPUnit 9
---

# Upgrading to PHPUnit 9.5 for PHP8 support

Unit and integration testing in Silverstripe CMS is done with the `SapphireTest` class which is a wrapper on top of the PHPUnit `TestCase` class. Silverstripe CMS 4.9 and earlier used a forked version of PHPUnit 5.7 which was not compatible with PHP 8.

Silverstripe CMS 4.10 onward supports PHPUnit 9.5 allowing projects to use PHP 8.

## Dual support for PHPUnit 5.7 and PHPUnit 9.5

`SapphireTest` has dual support for both PHPUnit 5.7 and PHPUnit 9.5. This allows existing projects to upgrade to Silverstripe CMS 4.10 without breaking their test suite; however they will be limited to PHP 7.3 and PHP 7.4.

Support for PHPUnit 5.7 will continue until approximately January 2023 when PHPUnit 5.7 support will be dropped.

PHPUnit versions 6.x, 7.x and 8.x are NOT supported.

## How to enable support for PHPUnit 9.5

Replace references to `"sminnee/phphunit": "5.7" `with `"phpunit/phpunit": "^9.5"` in the `"require-dev"` section of composer.json and run `composer update`.

If you are upgrading a module rather than a website, ensure there's a specific requirement for `silverstripe/framework`: `^4.10` as Silverstripe CMS 4.9 and earlier are not compatible with PHPUnit 9.

If the `"require"` block in composer.json does not have a requirement for `"silverstripe/framework"`, you can put the requirement in `"require-dev"` so that it's only required when running CI or running unit tests locally. This will allow older versions of Silverstripe CMS to use the latest version of your module.

## Common changes to make to your unit-test suites

These are some common adjustments that need to be made to unit tests so they're compatible with the PHPUnit 9.5 API:

- `setUp()` and `tearDown()` now require the `:void` return type e.g. `setUp(): void`
- `assertContains()` and `assertNotContains()` no longer accept strings so update to  `assertStringContainsString()` and `assertStringNotContainsString()`
- `assertInternalType('%Type%')` needs to be changed to `assertIs%Type%()` e.g. `assertIsInt()` - [full list](https://github.com/sebastianbergmann/PHPUnit/issues/3368)
- `@expectedException` style annotations are changed to [php functions](https://phpunit.readthedocs.io/en/latest/writing-tests-for-phpunit.html#testing-exceptions)
- Wrapping &lt;testsuite&gt; elements with a &lt;testsuites&gt; element in phpunit.xml / phpunit.xml.dist

You see the full list of PHPUnit changes in the [announcements](https://PHPUnit.de/announcements/) section of the PHPUnit.de website.
