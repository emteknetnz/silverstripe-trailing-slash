<?php

namespace Axllent\TrailingSlash\Extensions;

use SilverStripe\ORM\DataExtension;

class SiteTreeLinkExtension extends DataExtension
{
    
    public function updateLink(&$link)
    {
        $use_trailing_slash_urls = Config::inst()->get(TrailingSlashRedirector::class, 'use_trailing_slash_urls');
        if ($use_trailing_slash_urls) {
            return;
        }
        if (strpos($link, '/admin') === 0 || strpos($link, '/dev') === 0) {
            return;
        }
        $link = rtrim($link, '/');
    }
}
