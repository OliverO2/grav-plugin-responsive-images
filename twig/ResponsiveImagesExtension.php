<?php /** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

/**
 * @package Grav\Plugin
 *
 * This Twig extension provides the Twig functions for responsive images.
 */

namespace Grav\Plugin;

use Grav\Common\Grav;


/**
 * Class ResponsiveImagesExtension is a Twig Extension providing functions for responsive images.
 *
 * @package Grav\Plugin
 */
class ResponsiveImagesExtension extends \Twig_Extension
{
    /** @var bool controls whether debug comments should appear in generated CSS code */
    public static $debug;
    /** @var int display pixel density factors relative to 1px (must be in ascending order) */
    public static $displayPixelDensities = [1, 1.5, 2, 3, 4];

    /** @var Grav */
    protected $grav;
    /** @var bool */
    private $backgroundImageCount = 0;  // count of generated background images

    public function __construct()
    {
        $this->grav = Grav::instance();
        ResponsiveImagesExtension::$debug = $this->grav['config']->get('system.images.debug', false);
    }

    /** Returns the extension name. */
    public function getName(): string
    {
        return 'ResponsiveImagesExtension';
    }

    /**
     * Returns this plugin's Twig functions.
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('image_element', [$this, 'imageElement'], ['needs_context' => true, 'is_variadic' => true, 'is_safe' => ['html']]),
            new \Twig_SimpleFunction('background_image_class', [$this, 'backgroundImageClass'], ['needs_context' => true, 'is_variadic' => true])
        ];
    }

    /**
     * Returns an HTML &lt;img> element with a srcset attribute auto-generated from available image sources.
     *
     * @param array $context
     * @param string $path image path or pattern
     * @param string|null $baseWidth width used for 'src' attribute
     * @param array $attributes img element attributes
     * @return string
     */
    public function imageElement(array $context, string $path, ?string $baseWidth = null, array $attributes = []): string
    {
        $imageVector = new ResponsiveImagesExtension\ImageVector($context["page"], $path);
        $descendingImageWidths = $imageVector->widths(false);

        $widthCount = count($descendingImageWidths);

        if ($widthCount > 0 && $baseWidth === null)
            $baseWidth = $descendingImageWidths[($widthCount > 1 ? 1 : 0)];  // use second width, if available, else first

        $imageElementStart = '<img src="' . $imageVector->url($baseWidth) . '"';

        if ($widthCount > 1) {
            // With srcset, the first matching width wins. We specify images in descending width order
            // to get the largest matching image for best quality.
            $srcsetElements = [];
            foreach ($descendingImageWidths as $width)
                $srcsetElements[] = $imageVector->url($width) . ' ' . intval($width) . 'w';

            $srcsetAttribute = ' srcset="' . implode(', ', $srcsetElements) . '"';
        } else
            $srcsetAttribute = '';

        $sizesAttribute = '';
        $otherAttributes = '';

        foreach ($attributes as $attribute_name => $attribute_value) {
            if ($attribute_name === 'sizes')
                $sizesAttribute = " $attribute_name=\"$attribute_value\"";
            else {
                $otherAttributes .= " $attribute_name=\"$attribute_value\"";

                if ($attribute_name === 'title' && !array_key_exists('alt', $attributes))
                    $otherAttributes .= " alt=\"$attribute_value\"";
            }
        }

        if ($srcsetAttribute || $sizesAttribute)
            return "$imageElementStart$srcsetAttribute$sizesAttribute$otherAttributes>";
        else
            return "$imageElementStart$otherAttributes>";
    }

    /**
     * Returns the name of a CSS class auto-generated to display a responsive background image.
     *
     * May be invoked with the parameters described below, or with an associative array containing named parameters.
     *
     * @param array $context
     * @param string|array $path image path or pattern
     * @param string|null $baseWidth default width
     * @param string|null $sizes srcset/sizes-like attribute ('min-width' media queries, 'px' and 'vw' slot widths only)
     * @param array $properties additional CSS background properties
     * @return string
     */
    public function backgroundImageClass(
        array $context, $path, ?string $baseWidth = null, ?string $sizes = null, array $properties = []
    ): string
    {
        if (is_array($path)) {
            // Parse parameters from associative array (used when called with a YAML mapping as its parameter)
            $parameters = $path;
            if (!array_key_exists("path", $parameters))
                throw new \InvalidArgumentException("Required parameter 'path' is missing");
            $path = $parameters["path"];
            unset($parameters["path"]);
            if (array_key_exists("baseWidth", $parameters)) {
                $baseWidth = $parameters["baseWidth"];
                unset($parameters["baseWidth"]);
            }
            if (array_key_exists("sizes", $parameters)) {
                $sizes = $parameters["sizes"];
                unset($parameters["sizes"]);
            }
            $properties = $parameters;
        } else {
            if (!$path)
                throw new \InvalidArgumentException("Required parameter 'path' is missing");
        }

        $imageVector = new ResponsiveImagesExtension\ImageVector($context["page"], $path);
        $ascendingImageSourceWidths = $imageVector->widths(true);
        $imageSourceCount = count($ascendingImageSourceWidths);

        if ($imageSourceCount > 0 && $baseWidth === null) {
            // use second largest width, if available, else first
            $baseWidth = $ascendingImageSourceWidths[($imageSourceCount > 1 ? $imageSourceCount - 2 : $imageSourceCount - 1)];
        }

        $this->backgroundImageCount += 1;
        $className = "ri-background-image-$this->backgroundImageCount";

        // Generate the basic CSS class with generic properties.
        $css = ".$className {";
        $css .= " background-image: url('" . $imageVector->url($baseWidth) . "');";
        foreach ($properties as $propertyName => $propertyValue)
            $css .= " background-$propertyName: $propertyValue;";
        $css .= " }\n";

        // Add CSS code for alternative image sources at different sizes.
        if ($imageSourceCount > 1) {
            if (ResponsiveImagesExtension::$debug)
                $css .= $sizes ? "/* sizes='$sizes' */\n" : "/* sizes not specified */\n";

            $conditionalSizeList = new ResponsiveImagesExtension\ConditionalSizeList($sizes);

            // With media queries, like everywhere else in CSS, the last matching rule wins. Code for images sources
            // is generated in ascending width order, so that the largest matching image wins.
            $isSmallestImageSource = true;
            for ($imageSourceIndex = 0; $imageSourceIndex < $imageSourceCount; $imageSourceIndex++) {
                $imageSourceWidth = $ascendingImageSourceWidths[$imageSourceIndex];
                $mediaQueryListCss = "";

                if ($isSmallestImageSource) {
                    // The smallest image acts as a fallback. It gets a media condition which is always true.
                    $mediaQueryListCss = "(min-width: 0px)";
                    if (ResponsiveImagesExtension::$debug)
                        $mediaQueryListCss .= " /* fallback */";
                } else {
                    $previousImageSourceWidth = $ascendingImageSourceWidths[$imageSourceIndex - 1];
                    $mediaQueryList = $conditionalSizeList->mediaQueryList($previousImageSourceWidth);
                    if ($mediaQueryList->containsElements())
                        $mediaQueryListCss = $mediaQueryList->css();
                }

                if ($mediaQueryListCss) {
                    $imageUrl = $imageVector->url($imageSourceWidth);
                    $css .= "@media\n";
                    $css .= " $mediaQueryListCss {\n";
                    $css .= " .$className { background-image: url('" . $imageUrl . "'); }\n";
                    $css .= "}\n";
                }

                $isSmallestImageSource = false;
            }
        }

        $this->grav['assets']->addInlineCss($css);

        return "$className";
    }
}


namespace Grav\Plugin\ResponsiveImagesExtension;

use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Utils;


/**
 * ImageVector is a specification matching a list of images.
 */
class ImageVector
{
    /** @var Grav */
    private $grav;

    /** @var string */
    private $pathPattern;
    /** @var string */
    private $filePathPattern;

    /** @var bool : true if the image's path is "absolute" (relative to the site's page directory) */
    private $isAbsolute;
    /** @var bool : true if the image's path is relative to the current page (or module) directory */
    private $isRelative;

    /** @var Page */
    private $page;


    /**
     * ImageVector constructor.
     * @param Page $page : the page using the image. Note that this can be a modular page, where
     *      Grav::instance()["page"] would return the wrong object.
     * @param string $pathPattern : image path pattern containing a single '*' as a placeholder for width designations
     */
    public function __construct(Page $page, string $pathPattern)
    {
        $this->grav = Grav::instance();
        $this->page = $page;

        $this->pathPattern = $pathPattern;
        $this->isAbsolute = false;
        $this->isRelative = false;

        if (preg_match('/^([a-zA-Z]+:\/\/)(.+)$/', $pathPattern, $pathPatternParts) === 1) {
            // a PHP stream
            $this->filePathPattern = $this->grav['locator']->findResource($pathPatternParts[1]) . '/' . $pathPatternParts[2];
        } elseif (substr($pathPattern, 0, 1) === "/") {
            // an absolute link
            $this->filePathPattern = $this->grav['locator']->findResource("page://") . $pathPattern;
            $this->isAbsolute = true;
        } else {
            // a relative link
            $this->filePathPattern = $page->media()->getPath() . '/' . $pathPattern;
            $this->isRelative = true;
        }
    }

    /**
     * Returns the width designations from matching image files in the numerical order requested.
     *
     * @param bool $sortAscending : true if images should be sorted in ascending size order (otherwise descending)
     * @return string[]
     */
    public function widths(bool $sortAscending): array
    {
        $pathWidthPattern = '/^' . str_replace('*', '([[:digit:]]+)', preg_replace('/[^*]/', '.', $this->filePathPattern)) . '$/';

        $result = [];

        foreach (glob($this->filePathPattern) as $path) {
            // Extract the image width from the last sequence of digits found in path
            if (preg_match($pathWidthPattern, $path, $matches) === 1 && count($matches) === 2)
                $result[] = $matches[1];
        }

        if (empty($result))
            throw new \InvalidArgumentException("Could not find images matching path pattern '$this->filePathPattern'");

        if ($sortAscending)
            sort($result, SORT_NUMERIC);
        else
            rsort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Returns a single image's URL (for $width, if given).
     *
     * @param string|null $width width designation
     * @return string
     */
    public function url(?string $width): string
    {
        if ($width === null)
            $path = $this->pathPattern;
        else
            $path = str_replace('*', $width, $this->pathPattern);

        if ($this->isAbsolute) {
            return Utils::url("page://") . $path;
        } else {
            if ($this->isRelative)
                $path = $this->page->rawRoute() . '/' . $path;

            return Utils::url($path);
        }
    }
}


/**
 * ConditionalSizeList is an ordered list of conditional sizes. The list is defined by a string configuration
 * mimicking the 'sizes' value of an HTML &lt;img> tag.
 *
 * Example: (min-width: 1200px) 1200px, 100vw
 *
 * @package Grav\Plugin\ResponsiveImagesExtension
 */
class ConditionalSizeList
{
    /** @var ConditionalSize[] */
    public $elements;

    /**
     * @param string|null $configuration
     */
    public function __construct(?string $configuration)
    {
        if ($configuration !== null) {
            $this->elements = [];
            foreach (preg_split("/\s*,\s*/", $configuration) as $conditionalSizeConfiguration)
                $this->elements[] = new ConditionalSize($conditionalSizeConfiguration);
        } else
            $this->elements = [new ConditionalSize("100vw")];
    }

    /**
     * Returns media queries for an image according to this list's conditions and sizes.
     *
     * @param string $imageSourceWidthToExceed
     * @return MediaQueryList
     */
    public function mediaQueryList(int $imageSourceWidthToExceed): MediaQueryList
    {
        $result = new MediaQueryList();

        // Add media queries for matching conditions and sizes.
        foreach ($this->elements as $conditionalSize)
            $result->addCandidates($conditionalSize, $imageSourceWidthToExceed);

        return $result;
    }
}


/**
 * A ConditionalSize is a combination of an optional media condition and an image's target slot size.
 * It corresponds to an element of the HTML &lt;img> tag's 'sizes' value, restricted to
 *
 * * an optional media condition of `min-width` in `px`
 * * a mandatory target slot size of either
 *     * an absolute width in `px` or
 *     * a width relative to the viewport width in `vw`.
 *
 * Example 1: (min-width: 1200px) 1200px
 * Example 2: 100vw
 *
 * @package Grav\Plugin\ResponsiveImagesExtension
 */
class ConditionalSize
{
    /** @var int the minimum viewport width condition in px, or 0 (which equals unconditional) */
    private $minViewportWidthPxCondition;

    /** @var int|null an absolute slot width in px, or null */
    private $targetSlotWidthPx = null;
    /** @var float|null a relative slot width expressed as a factor of the viewport width, or null */
    private $targetSlotWidthFactor = null;

    /** @var string the conditional size's original configuration (for debug output only) */
    public $configuration;

    public function __construct(string $configuration)
    {
        $this->configuration = $configuration;

        if (!preg_match("/^(?:\(min-width:\s*(\d+)px\)\s+)?(\d+)(px|vw)$/", $configuration, $matches)) {
            throw new \InvalidArgumentException(
                "Unsupported syntax '$configuration' in parameter 'sizes',"
                . " use '(min-width: 1234px) 1234px' or '(min-width: 1234px) 12vw'"
            );
        }

        $this->minViewportWidthPxCondition = $matches[1] ? intval($matches[1]) : 0;
        $targetSlotWidth = intval($matches[2]);
        if ($matches[3] == "px")
            $this->targetSlotWidthPx = $targetSlotWidth;
        else
            $this->targetSlotWidthFactor = $targetSlotWidth / 100;
    }

    /**
     * Returns media queries matching this condition, according to target slot size and image size.
     *
     * @param int $imageSourceWidthToExceed
     * @return MediaQuery[]
     */
    public function mediaQueries(int $imageSourceWidthToExceed): array
    {
        /** @var MediaQuery[] $results */
        $results = [];

        if ($this->targetSlotWidthPx !== null) {  // absolute target slot width
            $imageDensity = floor(($imageSourceWidthToExceed / $this->targetSlotWidthPx) * 100) / 100;
            if ($imageDensity >= 1)
                $results[] = new MediaQuery($imageDensity, $this->minViewportWidthPxCondition, $this);
        } else {  // target slot width relative to the viewport width
            foreach (\Grav\Plugin\ResponsiveImagesExtension::$displayPixelDensities as $displayPixelDensity) {
                $imageWidthPx = $imageSourceWidthToExceed / $displayPixelDensity;
                $viewportWidthToExceed = floor($imageWidthPx / $this->targetSlotWidthFactor);
                if ($viewportWidthToExceed >= $this->minViewportWidthPxCondition)
                    $results[] = new MediaQuery($displayPixelDensity, $viewportWidthToExceed, $this);
            }
        }

        return $results;
    }
}


/**
 * MediaQueryList is a list of media queries for one responsive image.
 *
 * @package Grav\Plugin\ResponsiveImagesExtension
 */
class MediaQueryList
{
    /** @var MediaQuery[] */
    private $_elements = [];
    /** @var MediaQuery[] */
    private $elementsByDensity = [];

    /**
     * Adds media queries for a conditional size and image size, filtering out those candidates, which
     * are matched by other media queries already included.
     *
     * @param ConditionalSize $conditionalSize
     * @param int $imageSourceWidthToExceed
     */
    public function addCandidates(ConditionalSize $conditionalSize, int $imageSourceWidthToExceed): void
    {
        $candidatesToAdd = $conditionalSize->mediaQueries($imageSourceWidthToExceed);
        $elementsToAdd = [];

        foreach ($candidatesToAdd as $candidate) {
            $displayPixelDensityKey = $candidate->displayPixelDensityKey();
            if (isset($this->elementsByDensity[$displayPixelDensityKey])) {
                $this->elementsByDensity[$displayPixelDensityKey]->integrateAlternative($candidate);
            } else {
                $this->elementsByDensity[$displayPixelDensityKey] = $candidate;
                $elementsToAdd[] = $candidate;
            }
        }

        if ($elementsToAdd)
            $this->_elements = array_merge($elementsToAdd, $this->_elements);
    }

    public function containsElements(): bool
    {
        return !empty($this->_elements);
    }

    public function css(): string
    {
        $resultElements = [];

        foreach ($this->_elements as $mediaQuery)
            $resultElements[] = $mediaQuery->css();

        return implode(",\n ", $resultElements);
    }
}


/**
 * MediaQuery is a CSS media query.
 *
 * @package Grav\Plugin\ResponsiveImagesExtension
 */
class MediaQuery
{
    /** @var float */
    private $displayPixelDensity;
    /** @var int */
    private $minWidthPx;
    /** @var ConditionalSize[] origins of this media query, the first one of which is the actual generator */
    private $origins;

    public function __construct(float $displayPixelDensity, int $minWidthPxToExceed, ConditionalSize $origin)
    {
        $this->displayPixelDensity = $displayPixelDensity;
        $this->minWidthPx = $minWidthPxToExceed + 1;
        $this->origins = [$origin];
    }

    /** Returns the media query's display density as an array key, avoiding an implicit float->int conversion. */
    public function displayPixelDensityKey(): string
    {
        return strval($this->displayPixelDensity);
    }

    /** Integrates an alternative with an identical pixel density.
     * @param MediaQuery $alternative
     */
    public function integrateAlternative(MediaQuery $alternative): void
    {
        if ($alternative->minWidthPx < $this->minWidthPx) {
            // If an alternative has a less restrictive media condition, use it.
            $this->minWidthPx = $alternative->minWidthPx;
            // Indicate its prioritization by adding its origin at the beginning.
            array_unshift($this->origins, $alternative->origins[0]);
        } else
            $this->origins[] = $alternative->origins[0];
    }

    public function css(): string
    {
        // Support modern browsers according to https://caniuse.com/#feat=css-media-resolution

        $displayResolutionDPI = $this->displayPixelDensity * 96;
        $minWidthCondition = "(min-width: {$this->minWidthPx}px)";
        $displayPixelDensity = floatToString($this->displayPixelDensity);

        $css = "(-webkit-min-device-pixel-ratio: $displayPixelDensity) and $minWidthCondition,";
        $css .= " (min-resolution: ${displayResolutionDPI}dpi) and $minWidthCondition";

        if (\Grav\Plugin\ResponsiveImagesExtension::$debug)
            $css .= $this->originsComment();

        return $css;
    }

    private function originsComment(): string
    {
        $originConfigurations = [];
        foreach ($this->origins as $origin)
            $originConfigurations[] = $origin->configuration;

        return " /* " . implode(", ", $originConfigurations) . " */";
    }
}


/** returns a float's locale-independent string value */
function floatToString(float $value): string
{
    return rtrim(rtrim(sprintf("%F", $value), "0"), ".");
}
