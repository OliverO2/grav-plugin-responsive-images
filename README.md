# Responsive Images Plugin

The _Responsive Images_ plugin for the [Grav CMS](http://github.com/getgrav/grav) inserts responsive images into HTML pages. The plugin auto-generates `srcset` attributes for foreground images and CSS media queries for background images. It makes the browser display images on different devices with optimal image resolution and page speed.

These Twig functions are provided:

* **Foreground image**: The function `image_element()` generates an HTML `img` tag with `src` and `srcset` attributes referring to image sources and widths.

* **Background image**: The function `background_image_class()` generates a CSS class using media queries to specify image sources for different device widths.

Both functions expect a pre-built set of image files at different sizes for each responsive image. You can create such image files using advanced encoding and compression techniques, which are not typically available on web servers or may be too CPU-intensive to use on the fly.

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
	
> **NOTE**: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

> **NOTE**: Before configuring this plugin, you should copy the `user/plugins/responsive-images/responsive-images.yaml` to `user/config/plugins/responsive-images.yaml` and only edit that copy.

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

> **NOTE**: Remember to [enable Twig processing](https://learn.getgrav.org/content/headers#process) on each page using the `image_element()` function. 

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

> **NOTE**: The numbers must reflect the image's intrinsic width in pixels. This is the image's real width, _not CSS pixels_.

> **NOTE**: MDN provides an [introduction to responsive images in HTML](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images). For an in-depth discussion about `srcset` and `sizes` see [this excellent article on ericportis.com](https://ericportis.com/posts/2014/srcset-sizes/).

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
* **`<baseWidth>`** is the width of the base image if the browser ignores `srcset` (optional, defaults to the second largest image width as determined from image file names),
* further parameters specify additional image element attributes.

If a `title` attribute is present but no `alt` attribute, an `alt` attribute with the `title` attribute's value will be generated. Supply an empty `alt` attribute to suppress it.

### Background Images

> **NOTE**: Background images have no `srcset` support in CSS. This plugin works around this limitation by generating CSS media queries for different viewport sizes and display pixel densities. For an in-depth explanation why other methods are not helpful, see [this article on ericportis.com](https://ericportis.com/posts/2014/srcset-sizes/).

The Twig function `background_image_class()` returns the name of a generated CSS class, which specifies a responsive background image. The returned class name must appear as part of a `class` list of some HTML element (such as `<body>` or `<div>`).
 
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

    > **NOTE**: To use Twig in the page frontmatter, set `pages: { frontmatter: { process_twig: true } }` in your `system.yaml`. 

    ```
    body_classes: title-center title-h1h2 {{ background_image_class('images/stars-*.jpg', sizes='(min-width: 1200px) 1200px, 100vw', position='top', size='cover', attachment='fixed') }}
    ```

Secondly, provide a corresponding set of image files for different viewport widths:

```
images/stars-0600.jpg
images/stars-0800.jpg
images/stars-1200.jpg
images/stars-2400.jpg
```

> **NOTE**: The numbers must reflect the image's intrinsic width in pixels. This is the image's real width, _not CSS pixels_.

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

* **`<baseWidth>`** is the width of the base image if the browser ignores media queries (optional, defaults to the second largest image width as determined from image file names).

* **`<sizes>`** is a `srcset/sizes`-like attribute with a set of media conditions and slot width hints. It indicates the intended image size for different viewport widths. For details, see the [MDN documentation on srcset with sizes](https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images#Resolution_switching_Different_sizes). The parameter is optional and defaults to `100vw`.

    > **NOTE**: The `sizes` parameter is a restricted variant of the [HTML `<img>` tag's `sizes` attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-sizes): It supports only `min-width` media conditions with a `px` unit, and only slot widths with `px` and `vw` units.

    Example: `sizes='(min-width: 1200px) 1200px, 100vw'` would provide

    * images suitable for a slot width of `1200px` on viewport widths `>= 1200px`, and

    * images suitable for 100% of the viewport width on viewport widths `< 1200px`.

* further parameters will be passed as CSS properties, each prefixed by `background-`.
