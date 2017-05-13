# Responsive Images Plugin

The **Responsive Images** plugin is for [Grav CMS](http://github.com/getgrav/grav). It provides a Twig function to generate a responsive HTML image element (using `srcset`) for a set of pre-rendered image files.

The plugin also includes a special workaround for the Microsoft Edge browser, which intermittently displays distorted images as soon it encounters a `srcset` attribute (see [here for details](http://caniuse.com/#search=srcset)). 

## Installation

Installing the Responsive Images plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install responsive-images

This will install the Responsive Images plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/responsive-images`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `responsive-images`. You can find these files on [GitHub](https://github.com/OliverO2/grav-plugin-responsive-images) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/responsive-images
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

The plugin can be enabled or disabled. Regarding configuration, that's it.

Before configuring this plugin, you should copy the `user/plugins/responsive-images/responsive-images.yaml` to `user/config/plugins/responsive-images.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

## Usage

### Motivation

Grav provides extensive image display and manipulation functions, including support for [responsive images](https://learn.getgrav.org/content/media#responsive-images).  Grav's image capabilities are geared towards the user providing a base (high resolution) version of an image and then let Grav generate [different resolution versions](https://learn.getgrav.org/content/media#sizes-with-media-queries-using-derivatives) on the fly automatically.

This plugin takes a different approach: It assumes that high quality images have been prepared in advance at different resolutions.  This allows the site designer to choose compression and optimization functions, which might not be available on a production site or too CPU-intensive to use on the fly.

### Example

Suppose you have a set of pre-rendered versions of an image at different resolutions:

```
images/chasing_stars-0600.jpg
images/chasing_stars-0800.jpg
images/chasing_stars-1200.jpg
images/chasing_stars-2400.jpg
```

To display the image in a Grav page or Twig template, use: 
```
{{ image_element("images/chasing_stars-*.jpg", sizes="(min-width: 1200px) 1200px, 100vw", title="Chasing Stars") }}
```

This will generate the following HTML code:
```html
<img src="/images/chasing_stars-1200.jpg" srcset="/images/chasing_stars-2400.jpg 2400w, /images/chasing_stars-1200.jpg 1200w, /images/chasing_stars-0800.jpg 800w, /images/chasing_stars-0600.jpg 600w" sizes="(min-width: 1200px) 1200px, 100vw" title="Chasing Stars" alt="Chasing Stars">
```

The `image_element` function has generated the appropriate `srcset` attribute, automatically discovering image widths from file names where the `*` wildcard was placed. It has chosen the second largest image as a fallback for browsers which ignore `srcset`. Finally, it has passed on the title attribute and generated an appropriate alt attribute.

### Details

```
{{ image_element(<path> [, baseWidth=<baseWidth>] [, attribute="value" [, ...]]) }}
```

generates an HTML `img` element with attributes `src`, `srcset`, and optional extra attributes (such as `sizes`, `title`) as required and only if supported by the browser at page delivery time.

Where

* **`<path>`** is the path to the image with a single `*` placeholder for the image width, or no placeholder. `<path>` may contain prefixes such as `theme://`,
* **`<baseWidth>`** is the width of the base image if `srcset` is ignored by the browser (optional, defaults to the second largest image width as determined from image file names),
* further parameters specify additional image element attributes.

If a `title` attribute is present but no `alt` attribute, an `alt` attribute with the `title` attribute's value will be generated. Supply an empty `alt` attribute to suppress it.

### Special workaround for the Microsoft Edge browser

Microsoft Edge intermittently displays distorted images as soon it encounters a `srcset` attribute (see [here for details](http://caniuse.com/#search=srcset)). Since Edge versions 13, 14 and 15 cannot reliably handle `srcset` at all, this plugin filters `srcset` and `sizes` attributes from HTML delivered to Edge browsers.

> NOTE: `srcset` and `sizes` filtering applies only to image elements generated by the `image_element` function.

### Limitations

Do not use this plugin's internal markers `[[SRCSET]]` and `[[/SRCSET]]` on any page or template.