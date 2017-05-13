<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

/**
 * @package Grav\Plugin
 *
 * This Twig extension provides the Twig function 'image_element'.
 */
class ResponsiveImagesExtension extends \Twig_Extension
{
    protected $grav;

    public function __construct()
    {
        $this->grav = Grav::instance();
    }

    /**
     * Returns the extension name.
     * @return string
     */
    public function getName()
    {
        return 'ResponsiveImagesExtension';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('image_element', [$this, 'imageElement'], ['is_variadic' => true])
        ];
    }

    /**
     * Returns code generating an html image element with the proper set of attributes for the browser at page delivery
     * time.
     *
     * @param string $path          image path or pattern
     * @param string $baseWidth     width used for 'src' attribute
     * @param array  $attributes    img element attributes
     * @return string
     */
    public function imageElement($path, $baseWidth = null, array $attributes = [])
    {
        $widths = $this->widthsFromImagePathPattern($path);

        $widthCount = count($widths);

        if ($widthCount > 0) {
            if ($baseWidth === null)
                $baseWidth = $widths[($widthCount > 1 ? 1 : 0)];  // use second width, if available, else first
        }

        $imageElementStart = '<img src="' . $this->imageURL($path, $baseWidth) . '"';

        $srcsetAttribute = '';
        if ($widthCount > 1) {
            $srcsetAttribute .= ' srcset="';
            $separator = '';

            foreach ($widths as $width) {
                $srcsetAttribute .= $separator . $this->imageURL($path, $width) . ' ' . intval($width) .'w';
                $separator = ', ';
            }

            $srcsetAttribute .= '"';
        }

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
            $result = "${imageElementStart}[[SRCSET]]$srcsetAttribute${sizesAttribute}[[/SRCSET]]$otherAttributes>";
        else
            $result = "$imageElementStart$otherAttributes>";

        return $result;
    }

    /**
     * Returns the width designations from files matching $pathPattern in descending numerical order.
     * @param string $pathPattern image path pattern containing a single '*' as a placeholder for width designations
     * @return string[]
     */
    private function widthsFromImagePathPattern($pathPattern)
    {
        $locator = $this->grav['locator'];

        if (preg_match('/^([a-zA-Z]+:\/\/)(.+)$/', $pathPattern, $pathPatternParts) === 1)
            $pathPattern = $locator->findResource($pathPatternParts[1]) . '/' . $pathPatternParts[2];
        else
            $pathPattern = $locator->getBase() . '/user/pages/' . $pathPattern;

        $pathWidthPattern = '/^' . str_replace('*', '([[:digit:]]+)', preg_replace('/[^*]/', '.', $pathPattern)) . '$/';

        $result = [];

        foreach (glob($pathPattern) as $path) {
            // Extract the image width from the last sequence of digits found in path
            if (preg_match($pathWidthPattern, $path, $matches) === 1 && count($matches) === 2)
                $result[] = $matches[1];
        }

        if (!empty($result))
            rsort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Returns the image URL from $pathPattern with '*' replaced by $width, if not null.
     * @param string $pathPattern image path pattern containing a single '*' as a placeholder for width designations
     * @param string $width width designation
     * @return string
     */
    private function imageURL($pathPattern, $width)
    {
        if ($width === null)
            $path = $pathPattern;
        else
            $path = str_replace('*', $width, $pathPattern);

        return $this->grav['twig']->processString("{{ url(\"$path\") }}");
    }
}