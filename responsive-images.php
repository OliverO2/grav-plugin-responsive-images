<?php

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
    public static function getSubscribedEvents()
    {
        return [
            'onTwigExtensions' => ['onTwigExtensions', 0],
            'onOutputGenerated' => ['onOutputGenerated', 0]
        ];
    }

    /**
     * Register twig extensions.
     */
    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/ResponsiveImagesExtension.php');
        $this->grav['twig']->twig->addExtension(new ResponsiveImagesExtension());
    }

    /**
     * Replaces srcset code surrounded by markers "[[SRCSET]]...[[/SRCSET]]" in Grav output by either
     * a) stripping markers and srcset code if the current browser cannot handle their presence, or
     * b) stripping markers only.
     */
    public function onOutputGenerated()
    {
        if (preg_match(';Edge/;', $_SERVER['HTTP_USER_AGENT']) === 1) {
            // Kill srcset and sizes attributes for Microsoft Edge. Versions up to at least 15.15063 distort images
            // spuriously when srcset is present. See http://caniuse.com/#search=srcset
            $this->grav->output = preg_replace(';\\[\\[SRCSET\\]\\](?U).*\\[\\[/SRCSET\\]\\];', '', $this->grav->output);
        } else {
            // Just remove the markers for everyone else.
            $this->grav->output = str_replace('[[/SRCSET]]', '', str_replace('[[SRCSET]]', '', $this->grav->output));
        }
    }

}