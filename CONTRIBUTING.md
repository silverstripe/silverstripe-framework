# Contributing

Any open source product is only as good as the community behind it. You can participate by sharing code, ideas, or simply helping others. No matter what your skill level is, every contribution counts.

See our [high level overview](http://silverstripe.org/contributing-to-silverstripe) on silverstripe.org on how you can help out.

## Contributing to the correct version

SilverStripe core and module releases (since the 3.1.8 release) follow the [Semantic Versioning](http://semver.org) 
(SemVar) specification for releases. Using this specification declares to the entire development community the severity 
and intention of each release. It gives developers the ability to safely declare their dependencies and understand the
scope involved in each upgrade.

Each release is labeled in the format `$MAJOR`.`$MINOR`.`$PATCH`. For example, 3.1.8 or 3.2.0.

* `$MAJOR` version is incremented if any backwards incompatible changes are introduced to the public API. 
* `$MINOR` version is incremented if new, backwards compatible **functionality** is introduced to the public API or 
	improvements are introduced within the private code. 
* `$PATCH` version is incremented if only backwards compatible **bug fixes** are introduced. A bug fix is defined as 
	an internal change that fixes incorrect behavior.

Git Branches are setup for each `$MINOR` version (i.e 3.1, 3.2). Each `$PATCH` release is a git tag off the `$MINOR` 
branch. For example, 3.1.8 will be a git tag of 3.1.8.

When contributing code, be aware of the scope of your changes. If your change is backwards incompatible, raise your 
change against the `master` branch. The master branch contains the next `$MAJOR` release. If the change is backwards 
compatible raise it against the correct `$MINOR` branch.
