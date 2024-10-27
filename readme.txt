=== Argiope amoena ===
Contributors: fu94ma
Tags: admin, amazon, aws, media, mirror, s3, uploads
Requires at least: 3.5
Tested up to: 5.6
Stable tag: 0.3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically upload media files to Amazon S3. Also change the link in the post to the URL of S3. 

== Description ==

Automatically upload media files to Amazon S3. Also change the link in the post to the URL of S3. This plugin is based on the Nephila clavata. Ajax campatibility and a few other modifications are added.

== Installation ==

1. Upload the entire `argiope-amoena` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

The control panel of Argiope amoena is in 'Settings > Argiope amoena'.

**PHP libraries are using [AWS SDK for PHP 2](http://aws.amazon.com/sdkforphp2/ "AWS SDK for PHP 2"). PHP5.3 or later Required.**

== Changelog ==

= 0.3.6 = December, 31, 2020

* Fixed a bug that thumbnail images are not uploaded to S3.
* Stopped not rewriting the URL of the image to S3 while logging in.
* Added the function to output debug log.

= 0.3.5 = December, 18, 2019

* Delete one unnecessary duplicate line.

= 0.3.4 = December, 17, 2019

* Support for Advanced Custom Fields.

= 0.3.3 = November, 1, 2019

* Updated AWS region and storage class list.

= 0.3.2 = December, 1, 2017

* While logged in, do not rewrite the path to s3.

= 0.3.1 = June, 13, 2017

* Comeback s3 storage class option setting.

= 0.3.0 = August, 12, 2017

* Corresponds to srccet.

= 0.2.9 = June, 13, 2017

* Delete s3 storage class option setting.

= 0.2.8 = March, 21, 2017

* Reduced the probability of class name conflict.

= 0.2.7 = March, 17, 2017

* aws.phar -> PHP Composer

= 0.2.6 = March, 10, 2017

* First release
