<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/lib2/web.inc.php';
$login->verify();
$env = 'prod';
$debug = false;
if (isset($opt['debug']) && $opt['debug'] && $_SERVER['HTTP_HOST'] !== 'test.opencaching.de') {
    $env = 'dev';
    $debug = true;
}
$loader = require __DIR__ . '/app/autoload.php';
if ($debug) {
    Debug::enable();
}
$kernel = new AppKernel($env, $debug);
$kernel->loadClassCache();
$request = Request::createFromGlobals();

$locale = strtolower($opt['template']['locale']);
$request->setLocale($locale);
$response = $kernel->handle($request);
if ($request->isXmlHttpRequest()
    || $response->isRedirection()
    || $request->getRequestFormat() !== 'html'
    || preg_match('/\/_/', $request->getPathInfo()) === 1 // e.g. /_profiler/
    || ($response->headers->has('Content-Type') && strpos($response->headers->get('Content-Type'), 'html') === false)
) {
    $response->send();
    $kernel->terminate($request, $response);
    exit;
}

$response->sendHeaders();

if ($response->getStatusCode() === 404) {
    include __DIR__ . '/404.php';
    exit;
}

$content = $response->getContent();
$kernel->terminate($request, $response);

// the debug toolbar is appended only if there is a </body> tag. So we add one in base.html.twig and remove it here
$content = str_replace('</body>', '', $content);

$tpl->name = 'symfony';
$tpl->assign('content', $content);
$tpl->display();
