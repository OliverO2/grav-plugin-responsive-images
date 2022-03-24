# v3.3.1
## 24-03-2022

1. [](#bugfix)
    * Fix generated <source> elements within <picture>, removing 'src' tag

# v3.3.0
## 24-03-2022

1. [](#improved)
    * Add Twig function html_image_support_classes().

# v3.2.0
## 24-03-2022

1. [](#improved)
    * Support alternative image formats (e.g. WebP).

# v3.1.0
## 22-03-2022

1. [](#improved)
    * background_image_class(): Prefer higher image resolution.

# v3.0.3
## 05-06-2021

1. [](#improved)
    * Relax required PHP version to >=7.3.

# v3.0.2
## 08-03-2021

1. [](#improved)
    * Documented Twig caching problems on modular pages (Grav issue #1934).

# v3.0.1
## 07-03-2021

1. [](#bugfix)
    * Fixed page-relative links for modular pages.
1. [](#improved)
    * Upgraded to PHP 7.4.

# v3.0.0
## 01-03-2021

1. [](#new)
    * Added support for page-relative image links ([#1](https://github.com/OliverO2/grav-plugin-responsive-images/issues/1)). **UPGRADE NOTE:** Formerly relative image paths must now be changed to absolute ones. Paths will be interpreted in a way consistent with Grav's documentation on [Image Linking](https://learn.getgrav.org/16/content/image-linking).
1. [](#improved)
    * Documented using background images in page content ([#2](https://github.com/OliverO2/grav-plugin-responsive-images/issues/2)).
1. [](#improved)
    * Added support for Grav 1.7 with Twig auto-escaping enabled by default. This plugin's Twig functions can be used with auto-escape enabled without requiring the `...|raw` filter.

# v2.0.3
## 30-05-2020

1. [](#new)
    * Added `background_image_class()` for responsive background images with auto-generated CSS media queries.
1. [](#improved)
    * Removed Microsoft Edge `srcset` workaround as the corresponding bug had been fixed in Edge 16 released in October 2017.

# v1.0.0
## 13-05-2017

1. [](#new)
    * ChangeLog started...
