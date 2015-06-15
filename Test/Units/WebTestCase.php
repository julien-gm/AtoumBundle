<?php

namespace atoum\AtoumBundle\Test\Units;

use Symfony\Bundle\FrameworkBundle\Client;
use atoum\AtoumBundle\Test\Asserters;
use mageekguy\atoum;
use Symfony\Component\CssSelector\CssSelector;

/**
 * WebTestCase
 *
 * @uses Test
 * @author Stephane PY <py.stephane1@gmail.com>
 */
abstract class WebTestCase extends Test
{
    /**
     * {@inheritdoc}
     */
    public function __construct(atoum\adapter $adapter = null, atoum\annotations\extractor $annotationExtractor = null, atoum\asserter\generator $asserterGenerator = null, atoum\test\assertion\manager $assertionManager = null, \closure $reflectionClassFactory = null)
    {
        parent::__construct($adapter, $annotationExtractor, $asserterGenerator, $assertionManager, $reflectionClassFactory);

        $generator = $this->getAsserterGenerator();
        $test = $this;
        $crawler = null;
        $client = null;

        $this->getAssertionManager()
            ->setHandler(
                'request',
                function(array $options = array(), array $server = array(), array $cookies = array()) use (& $client, $test, $generator) {
                    $client = $test->createClient($options, $server, $cookies);

                    return $test;
                }
            )
            ->setHandler('get', $get = $this->getSendRequestHandler($client, $crawler, 'GET'))
            ->setHandler('GET', $get)
            ->setHandler('head', $head = $this->getSendRequestHandler($client, $crawler, 'HEAD'))
            ->setHandler('HEAD', $head)
            ->setHandler('post', $post = $this->getSendRequestHandler($client, $crawler, 'POST'))
            ->setHandler('POST', $post)
            ->setHandler('put', $put = $this->getSendRequestHandler($client, $crawler, 'PUT'))
            ->setHandler('PUT', $put)
            ->setHandler('patch', $patch = $this->getSendRequestHandler($client, $crawler, 'PATCH'))
            ->setHandler('PATCH', $patch)
            ->setHandler('delete', $delete = $this->getSendRequestHandler($client, $crawler, 'DELETE'))
            ->setHandler('DELETE', $delete)
            ->setHandler('options', $options = $this->getSendRequestHandler($client, $crawler, 'OPTIONS'))
            ->setHandler('OPTIONS', $options)
            ->setHandler(
                'crawler',
                function ($strict = false) use (& $crawler, $generator) {
                    if ($strict) {
                        CssSelector::enableHtmlExtension();
                    } else {
                        CssSelector::disableHtmlExtension();
                    }

                    $asserter = new Asserters\Crawler($generator);

                    return $asserter->setWith($crawler);
                }
            )
        ;

    }

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Client $client
     * @param \Symfony\Component\DomCrawler\Crawler  $crawler
     * @param string                                 $method
     *
     * @return callable
     */
    protected function getSendRequestHandler(& $client, & $crawler, $method)
    {
        $generator = $this->getAsserterGenerator();

        return function($path, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true) use (& $client, & $crawler, $method, $generator) {
            /** @var $client \Symfony\Bundle\FrameworkBundle\Client */
            $crawler = $client->request($method, $path, $parameters, $files, $server, $content, $changeHistory);
            $asserter = new Asserters\Response($generator);

            return $asserter->setWith($client->getResponse());
        };
    }

    /**
     * Creates a Client.
     *
     * @param array $options An array of options to pass to the createKernel class
     * @param array $server  An array of server parameters
     * @param array $cookies An array of Symfony\Component\BrowserKit\Cookie
     *
     * @return Client A Client instance
     */
    public function createClient(array $options = array(), array $server = array(), array $cookies = array())
    {
        if (null !== $this->kernel && $this->kernelReset) {
            $this->kernel->shutdown();
            $this->kernel->boot();
        }

        if (null === $this->kernel) {
            $this->kernel = $this->createKernel($options);
            $this->kernel->boot();
        }

        $client = $this->kernel->getContainer()->get('test.client');
        $client->setServerParameters($server);

        foreach ($cookies as $cookie) {
            $client->getCookieJar()->set($cookie);
        }

        return $client;
    }
}
