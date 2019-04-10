<?php

namespace Axllent\TrailingSlash\Middleware;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Config;

/**
 * Ensure that a single trailing slash is always added to the URL.
 * URLs accessed via Ajax, contain $_GET vars, or that contain
 * an extension are ignored.
 */
class TrailingSlashRedirector implements HTTPMiddleware
{
    /**
     * URLS to ignore
     * @var array
     */
    private static $ignore_paths = [
        'admin/',
        'dev/',
    ];

    /**
     * Set to true for /category/my-page/
     * Set to false for /category/my-page
     * 
     * @var bool
     */
    private static $use_trailing_slash_urls = true;

    /**
     * The http status code used for the redirect
     * You may wish to set this to 302 during an initial deployment to ensure things still work as you expect them to and later change to 301
     * Google is is apparently fairly loose with duplicate content on 'with slash' and 'without slash' so there's probably very little SEO penalty for having both
     * meaning that there's probably little reason to use a 301, since SEO is the main benefit of using a 301
     * Note: once this is set to 301, it's basically impossible to change it to the other version because 301 get cached in browsers and proxies
     * 
     * @var int
     */
    private static $status_code = 302;

    public function process(HTTPRequest $request, callable $delegate)
    {
        $ignore_paths = [];
        $ignore_config = Config::inst()->get(TrailingSlashRedirector::class, 'ignore_paths');
        foreach ($ignore_config as $iurl) {
            if ($quoted = preg_quote(ltrim($iurl, '/'), '/')) {
                array_push($ignore_paths, $quoted);
            }
        }

        if ($request && ($request->isGET() || $request->isHEAD())) {
            // skip $ignore_paths and home (`/`)
            if ($request->getURL() == '' ||
                preg_match('/^(' . implode($ignore_paths, '|') . ')/i', $request->getURL())
            ) {
                return $delegate($request);
            }

            $requested_url = $_SERVER['REQUEST_URI'];
            $urlPathInfo = pathinfo($requested_url);
            $params = $request->getVars();

            $use_trailing_slash_urls = Config::inst()->get(TrailingSlashRedirector::class, 'use_trailing_slash_urls');
            if ($use_trailing_slash_urls) {
                $expected_url = rtrim(Director::baseURL() . $request->getURL(), '/') . '/';
                $do_redirect = !preg_match('/^' . preg_quote($expected_url, '/') . '(?!\/)/i', $requested_url);
                $redirect_url = Controller::join_links($expected_url, '/');
            } else {
                $expected_url = rtrim(Director::baseURL() . $request->getURL(), '/');
                $do_redirect = preg_match('/^' . preg_quote($expected_url, '/') . '\/$/i', $requested_url);
                $redirect_url = $expected_url;
            }

            if (!Director::is_ajax() &&
                !isset($urlPathInfo['extension']) &&
                empty($params) &&
                $do_redirect
            ) {
                $response = new HTTPResponse();

                $status_code = Config::inst()->get(TrailingSlashRedirector::class, 'status_code');
                return $response->redirect($redirect_url, $status_code);
            }
        }

        $response = $delegate($request);

        return $response;
    }
}
