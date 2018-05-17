title: Personal Data
summary: How the SilverStripe CMS deals with data privacy

# Personal Data

SilverStripe is an application framework which can be used to process
and store data. Any data can be sensitive, particularly if it is
considered personal data. Many regulatory frameworks such as the
[EU General Data Protection Regulation (GDPR)](https://en.wikipedia.org/wiki/General_Data_Protection_Regulation)
can be interpreted to regard basic data points such as email and IP addresses
as personally identifiable data.

This document is aiding implementors and auditors in determining
the impact of using the SilverStripe Framework and CMS
to build online services. Since every website and app built with
SilverStripe will be different, it can only provide starting points.

## Storage

The SilverStripe CMS does not provide any built-in mechanisms for users to submit personal data,
or register a user account. CMS authors are created by administrators through the CMS UI.
Since even the email address required to create such an account can be considered personal data,
you’ll need to get consent from existing and new CMS authors,
or cover this through other contractual arrangements with the individuals.

The primary location where SilverStripe can be configured to store personal data is the database.
Under different regulations, individuals can have the "right to be forgotten",
and can ask website operators to remove their data. 
Most of the time, CMS administrators can action this without any technical help through
the CMS (through the “Security” section, or specialised UIs like user defined forms).

Be careful with Versioned records containing personal data:
These might require development effort to completely remove.
Note that CMS users aren’t versioned by default, so you can completely remove them through the UI.

## Transmission and Processing

SilverStripe recommends the use of encryption in transit (e.g. TLS/SSL),
and at rest (e.g. database encryption), but does not enforce these.

## Cookies

SilverStripe will default to using PHP sessions for tracking logged-in users,
which uniquely link users to their device/browser through a session cookie.
If the user chooses the "Remember me" feature on login,
this unique link will persist across sessions. 

## Login Attempts

SilverStripe can be configured to record login attempts, in order to lock out users
after a defined number of attempts, and hence limit the attack surface of the login process.
This is predicated on tracking the IP address of the attempt, which can be considered personal data.
See `SilverStripe\Security\Security::$login_recording` for details.

## Logging and Exceptions

SilverStripe provides a logging mechanism, which depending on your usage, configuration and hosting
environment might store personal data outside of the SilverStripe database.
The core system stores personal data for members, but does not log it.

As a PHP application, SilverStripe can also throw exceptions. These can include
metadata such as method arguments and session data. If your application is configured
to catch exceptions and log them (e.g. via a SaaS product), you could inadvertently store
personal data in other systems. One mitigation is to create whitelists based on 
parameter naming, see the [silverstripe/raygun](https://github.com/silverstripe/silverstripe-raygun)
module for an example implementation. 