CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How it works
 * Support requests
 * Maintainers


INTRODUCTION
------------

Social Auth PBS is a PBS authentication integration for
Drupal. It is based on the Social Auth and Social API projects

It adds to the site:

* Multiple new log URLs:
 * `/user/login/pbs`
 * `/user/login/pbs-apple`
 * `/user/login/pbs-facebook`
 * `/user/login/pbs-google`
 * `/user/login/pbs-register`

* A settings form at `/admin/config/social-api/social-auth/pbs`.

* A PBS logo in the Social Auth Login block.


REQUIREMENTS
------------

This module requires the following modules:

* [Social Auth](https://drupal.org/project/social_auth)
* [Social API](https://drupal.org/project/social_api)


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See
[Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules)
for more details.


CONFIGURATION
-------------

 * Add your PBS project OAuth information in
   Configuration » User Authentication » PBS.

 * Place a Social Auth Login block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.


HOW IT WORKS
------------

User can click on the PBS logo in the Social Auth Login block. You can also add
a button or link anywhere on the site that points to /user/login/pbs, so
theming and customizing the button or link is very flexible.

When the user opens the /user/login/pbs link, it automatically takes user to
PBS for authentication. PBS then returns the user to Drupal site. If there is an
existing Drupal user with the same email address provided by PBS, that user is
logged in. Otherwise, a new Drupal user is created.

This module also supports login via the PBS provided Facebook and Google OAuth2
flows. These methods are only supported by URL access (no block buttons are
provided):

* **Facebook**: `social_auth_pbs.redirect_to_facebook`
  (/user/login/pbs-facebook)

* **Google**: `social_auth_pbs.redirect_to_google` (/user/login/pbs-google)
 
It is also possible to send users through a PBS Account creation flow by 
supplying a URL encoded version of the PBS login route to the `next` parameter 
of PBS's OAuth2 account creation form page. E.g.
`[url]?next=https%3A%2F%2Fexample.com%2Fuser%2Flogin%2Fpbs`.


SUPPORT REQUESTS
----------------

* Before posting a support request, carefully read the installation
  instructions provided in module documentation page.

* Before posting a support request, check the Recent Log entries at
  admin/reports/dblog

* Once you have done this, you can post a support request at module issue
  queue: [https://www.drupal.org/project/issues/social_auth_pbs](https://www.drupal.org/project/issues/social_auth_pbs)

* When posting a support request, please inform if you were able to see any
  errors in the Recent Log entries.


MAINTAINERS
-----------

Current maintainers:

* [Christopher C. Wells (wells)](https://www.drupal.org/u/wells)

Development sponsored by:

* [Cascade Public Media](https://www.drupal.org/cascade-public-media)
