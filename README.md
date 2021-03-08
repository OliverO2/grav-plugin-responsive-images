# Responsive Images Plugin

The _Responsive Images_ plugin for the [Grav CMS](http://github.com/getgrav/grav) inserts responsive images into HTML pages.  It makes the browser display images on different devices with optimal image resolution and page speed.

> :information_source: This plugin generates a `srcset` attribute for foreground images and media queries for background images. See [this overview article](https://web.dev/serve-responsive-images/) and [this in-depth explanation](https://ericportis.com/posts/2014/srcset-sizes/) why other methods are not helpful. 

## Why Use Responsive Images?

Serving desktop-sized images to mobile devices can use 2–4x more data than needed, slowing down sites. Speed matters:

* [Low-performing sites may lose users](https://web.dev/why-speed-matters/).
* Google uses speed as a [ranking factor on mobile devices](https://developers.google.com/web/updates/2018/07/search-ads-speed). 

## Overview

1. For each image on your website, create a pre-built set of properly sized image files, for example with _ImageMagick_.

    > :information_source: You can use advanced encoding and compression techniques in this step. An example using _ImageMagick_ and _jpegoptim_:
    ```bash
    convert jeep.jpg -quality 100 -resize 800x534 jeep-0800.jpg
    jpegoptim --quiet --all-progressive --strip-all --max=85 jeep-0800.jpg
    ```

1. Embed the image into your page or template using the provided Twig functions.

    * **Foreground image**: The function `image_element()` generates an HTML `img` tag with `src` and `srcset` attributes referring to image sources and widths.
    
    * **Background image**: The function `background_image_class()` generates a CSS class using media queries to specify image sources for different device widths.

## Installation

Installing the _Responsive Images_ plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav installation directory type:

    bin/gpm install responsive-images

This will install the _Responsive Images_ plugin into your `user/plugins` directory within Grav. Its files can be found under `user/plugins/responsive-images`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `user/plugins`. Then, rename the folder to `responsive-images`. You can find these files on [GitHub](https://github.com/OliverO2/grav-plugin-responsive-images) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    user/plugins/responsive-images
	
> :information_source: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) plugins to operate.

## Configuration

> :information_source: Before configuring this plugin, you should copy the `user/plugins/responsive-images/responsive-images.yaml` to `user/config/plugins/responsive-images.yaml` and only edit that copy.

### Plugin Configuration

The plugin can be enabled or disabled. The default configuration is:

```yaml
enabled: true
```

### System Configuration

If `images.debug` is set to `true` in `system.yaml`, the plugin's generated CSS code will contain comments explaining the use of the `sizes` parameter: 

```yaml
images:
  debug: true
```

## Usage

### Foreground Images

> :information_source: Remember to [enable Twig processing](https://learn.getgrav.org/content/headers#process) on each page using the `image_element()` function. 

To display a responsive foreground image in a Grav page or Twig template, firstly use: 

```
{{ image_element("images/astronaut-*.jpg", sizes="(min-width: 1200px) 1200px, 100vw", title="Chasing Stars") }}
```

Secondly, provide a corresponding set of image files at different widths:

```
images/astronaut-0600.jpg
images/astronaut-0800.jpg
images/astronaut-1200.jpg
images/astronaut-2400.jpg
```

> :information_source: The numbers must reflect the image's intrinsic width in pixels. This is the image's real width, _not CSS pixels_.

> :information_source: MDN provides an [introduction to responsive images in HTML](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images). For an in-depth discussion about `srcset` and `sizes`, see [this excellent article on ericportis.com](https://ericportis.com/posts/2014/srcset-sizes/).

#### What Happens

The `image_element()` function
* generates an `<img>` tag,
* discovers image widths from file names where the `*` wildcard was placed,
* generates the appropriate `srcset` attribute,
* generates a `src` attribute, choosing the second largest image as a fallback for browsers which ignore `srcset`,
* passes on the `sizes` and `title` attributes,
* generates an appropriate `alt` attribute.

#### Code Generated

The above example will generate the following HTML code:

```html
<img src="/images/astronaut-1200.jpg" srcset="/images/astronaut-2400.jpg 2400w, /images/astronaut-1200.jpg 1200w, /images/astronaut-0800.jpg 800w, /images/astronaut-0600.jpg 600w" sizes="(min-width: 1200px) 1200px, 100vw" title="Chasing Stars" alt="Chasing Stars">
```

#### Details

```
{{ image_element(<path> [, baseWidth=<baseWidth>] [, attribute="value" [, ...]]) }}
```

generates an HTML `img` tag with `src` and `srcset` attributes referring to image sources and widths, and optional extra attributes (such as `sizes`, `title`) as given.

Where

* **`<path>`** is the path to the image with a single `*` placeholder for the image width, or no placeholder. `<path>` may contain prefixes such as `theme://`,
* **`<baseWidth>`** is a string specifying the width of the base image if the browser ignores `srcset` (optional, defaults to the second largest image width as determined from image file names),
    > :information_source: If baseWidth is used, it must match the width as it appears in the file name, e.g. "0800" in the above example, not "800".
* further parameters specify additional image element attributes.

If a `title` attribute is present but no `alt` attribute, an `alt` attribute with the `title` attribute's value will be generated. Supply an empty `alt` attribute to suppress it.

#### About The `sizes` Attribute

You should supply a `sizes` attribute for each responsive image. It tells the browser how large the image will render in different layouts. Only then can the browser choose the appropriate image file for the intended size and given display density.

The [`sizes`](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-sizes) value can be a single size, such as `100vw` for an image displaying at full-width on all displays. It can also be a combination of [media conditions](https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries/Using_media_queries#syntax) (e.g. screen widths) and image widths for responsive layouts with images appearing differently depending on the device's screen size.
 
##### Examples:

|Intended image width|`sizes` parameter|
|:---|----|
|Full viewport width| `100vw`|
|50% of the viewport width| `50vw`|
|Fixed width of 630px| `630px`|
|1200px wide on viewport widths >= 1200px, full viewport width on all others|`(min-width: 1200px) 1200px, 100vw`|
|100% of the viewport width on viewports up to 480px wide, 50% of the viewport width on screens 481–1024px wide, and 800px on screens wider than 1024px|`(max-width: 480px) 100vw, (max-width: 1024px) 50vw, 800px`|

### Background Images

> :information_source: Background images have no `srcset` support in CSS. This plugin works around this limitation by generating CSS media queries for different viewport sizes and display pixel densities. For an in-depth explanation why other methods are not helpful, see [this article on ericportis.com](https://ericportis.com/posts/2014/srcset-sizes/).

The Twig function `background_image_class()` returns the name of a generated CSS class, which specifies a responsive background image. The returned class name must appear as part of a `class` list of the HTML element containing the background (such as `<body>` or `<div>`).

> :information_source: For responsive images to work on modular pages, Twig caching must be disabled due to [Grav issue #1934](https://github.com/getgrav/grav/issues/1934). Please set `pages: { never_cache_twig: true }` in your `system.yaml` or set `never_cache_twig: true` in each modular page's frontmatter. Otherwise images will disappear on the second page load.

To display a responsive background image, firstly use a method from one of the following alternatives.

* Completely specify the image in a Twig template:
    ```
    <div class="{{ background_image_class('images/stars-*.jpg', sizes='(min-width: 1200px) 1200px, 100vw', position='top', size='cover', attachment='fixed') }}">
    [...]
    </div>
    ```

* Use a parameterized specification in a Twig template:
    ```
    <div {% if page.header.background_image %}class="{{ background_image_class(page.header.background_image) }}"{% endif %}>
    [...]
    </div>
    ```
    joined by corresponding parameters in the page's frontmatter:
    ```yaml
    background_image:
        path: "images/stars-*.jpg"
        sizes: "(min-width: 1200px) 1200px, 100vw" 
        position: top
        size: cover
        attachment: fixed
    ```

* Use a Twig function in a page's frontmatter:

    > :information_source: To use Twig in a page's frontmatter, [enable Twig processing](https://learn.getgrav.org/content/headers#process) and set `pages: { frontmatter: { process_twig: true } }` in your `system.yaml`.

    ```
    body_classes: title-center title-h1h2 {{ background_image_class('images/stars-*.jpg', sizes='(min-width: 1200px) 1200px, 100vw', position='top', size='cover', attachment='fixed') }}
    ```

* Use a background image in the page's content:
    ```
    <div class="{{ background_image_class('images/stars-*.jpg', sizes='(min-width: 1200px) 1200px, 100vw', position='top', size='cover', attachment='fixed') }}">
    [...]
    </div>
    ```

  > :information_source: Remember to [enable Twig processing](https://learn.getgrav.org/content/headers#process).

Secondly, provide a corresponding set of image files for different viewport widths:

```
images/stars-0600.jpg
images/stars-0800.jpg
images/stars-1200.jpg
images/stars-2400.jpg
```

> :information_source: The numbers must reflect the image's intrinsic width in pixels. This is the image's real width, _not CSS pixels_.

#### What Happens

The `background_image_class()` function
* generates a CSS class `ri-background-image-1`,
* discovers image widths from file names where the `*` wildcard was placed,
* generates appropriate CSS code using media queries,
* chooses the second largest image as a fallback for browsers which ignore media queries,
* passes on the additional parameters as CSS properties, each prefixed by `background-`,
* returns the name of the generated background class (`ri-background-image-1`).

#### Code Generated

For each of the above examples, `background_image_class()` will generate the following HTML code in the head section:

> NOTE: If `images.debug` is set to `true` in `system-yaml`, the generated CSS code will contain comments explaining the use of the `sizes` parameter, as shown here. 

```html
<style>
.ri-background-image-1 { background-image: url('/images/stars-1200.jpg'); background-position: top; background-size: cover; background-attachment: fixed; }
/* sizes='(min-width: 1200px) 1200px, 100vw' */
@media
 (min-width: 0px) /* fallback */ {
 .ri-background-image-1 { background-image: url('/images/stars-0600.jpg'); }
}
@media
 (-webkit-min-device-pixel-ratio: 1) and (min-width: 800px), (min-resolution: 96dpi) and (min-width: 800px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 1.5) and (min-width: 533px), (min-resolution: 144dpi) and (min-width: 533px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 2) and (min-width: 400px), (min-resolution: 192dpi) and (min-width: 400px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 3) and (min-width: 266px), (min-resolution: 288dpi) and (min-width: 266px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 4) and (min-width: 200px), (min-resolution: 384dpi) and (min-width: 200px) /* 100vw */ {
 .ri-background-image-1 { background-image: url('/images/stars-0800.jpg'); }
}
@media
 (-webkit-min-device-pixel-ratio: 1.5) and (min-width: 800px), (min-resolution: 144dpi) and (min-width: 800px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 2) and (min-width: 600px), (min-resolution: 192dpi) and (min-width: 600px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 3) and (min-width: 400px), (min-resolution: 288dpi) and (min-width: 400px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 4) and (min-width: 300px), (min-resolution: 384dpi) and (min-width: 300px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 1) and (min-width: 1200px), (min-resolution: 96dpi) and (min-width: 1200px) /* (min-width: 1200px) 1200px, 100vw */ {
 .ri-background-image-1 { background-image: url('/images/stars-1200.jpg'); }
}
@media
 (-webkit-min-device-pixel-ratio: 1) and (min-width: 2400px), (min-resolution: 96dpi) and (min-width: 2400px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 1.5) and (min-width: 1600px), (min-resolution: 144dpi) and (min-width: 1600px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 3) and (min-width: 800px), (min-resolution: 288dpi) and (min-width: 800px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 4) and (min-width: 600px), (min-resolution: 384dpi) and (min-width: 600px) /* 100vw */,
 (-webkit-min-device-pixel-ratio: 2) and (min-width: 1200px), (min-resolution: 192dpi) and (min-width: 1200px) /* (min-width: 1200px) 1200px, 100vw */ {
 .ri-background-image-1 { background-image: url('/images/stars-2400.jpg'); }
}
</style>
```

Variants 1 and 2 will insert the following HTML code at the place of invocation:

```html
<div class="ri-background-image-1">
[...]
</div>
```

Variant 3, when used with Grav's default page, will insert `ri-background-image-1` into the list of class names for the `<body>` tag:

```html
<body id="top" class="title-center title-h1h2 ri-background-image-1 header-fixed header-animated sticky-footer">
```

#### Details

```
{{ background_image_class(<path> [, baseWidth=<baseWidth>] [, sizes=<sizes>] [, property='value' [, ...]]) }}
```

generates a CSS class using media queries to specify image sources for different device widths. The function returns the class name.

Parameters:

* **`<path>`** is the path to the image with a single `*` placeholder for the image width, or no placeholder. `<path>` may contain prefixes such as `theme://`.

* **`<baseWidth>`** is a string specifying the width of the base image if the browser ignores media queries (optional, defaults to the second largest image width as determined from image file names).
  > :information_source: If baseWidth is used, it must match the width as it appears in the file name, e.g. "0800" in the above example, not "800".

* **`<sizes>`** is a `srcset/sizes`-like attribute with a set of media conditions and slot width hints. It indicates the intended image size for different viewport widths. For details, see the [MDN documentation on srcset with sizes](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images#Resolution_switching_Different_sizes). The parameter is optional and defaults to `100vw`.

    > :information_source: The `sizes` parameter is a restricted variant of the [HTML `<img>` tag's `sizes` attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-sizes): It supports only `min-width` media conditions with a `px` unit, and only slot widths with `px` and `vw` units.

    Example: `sizes='(min-width: 1200px) 1200px, 100vw'` would provide

    * images suitable for a slot width of `1200px` on viewport widths `>= 1200px`, and

    * images suitable for 100% of the viewport width on viewport widths `< 1200px`.

* further parameters will be passed as CSS properties, each prefixed by `background-`.
