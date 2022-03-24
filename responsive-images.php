<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace Grav\Plugin;

use Grav\Common\Plugin;

/**
 * @package Grav\Plugin
 *
 * This plugin provides the Twig function 'image_element' (see twig folder)
 */
class ResponsiveImagesPlugin extends Plugin
{
    /**
     * Returns a list of events and associated subscribers.
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTwigExtensions' => ['onTwigExtensions', 0],
        ];
    }

    /**
     * Register twig extensions.
     */
    public function onTwigExtensions()
    {
        $alternativeFormats = $this->config->get('plugins.responsive-images.alternativeFormats', []);
        require_once(__DIR__ . '/twig/ResponsiveImagesExtension.php');
        $this->grav['twig']->twig->addExtension(new ResponsiveImagesExtension($alternativeFormats));
    }
}
