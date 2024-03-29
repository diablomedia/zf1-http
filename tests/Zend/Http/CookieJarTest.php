<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Http_CookieJar
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Zend_Http_CookieJar unit tests
 *
 * @category   Zend
 * @package    Zend_Http_CookieJar
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Http
 * @group      Zend_Http_CookieJar
 */
class Zend_Http_CookieJarTest extends PHPUnit\Framework\TestCase
{
    public function loadResponse($filename)
    {
        $message = file_get_contents($filename);
        // Line endings are sometimes an issue inside the canned responses; the
        // following is a negative lookbehind assertion, and replaces any \n
        // not preceded by \r with the sequence \r\n, ensuring that the message
        // is well-formed.
        return preg_replace("#(?<!\r)\n#", "\r\n", $message);
    }

    /**
     * Test we can add cookies to the jar
     *
     */
    public function testAddCookie()
    {
        $jar = new Zend_Http_CookieJar();
        $this->assertCount(0, $jar->getAllCookies(), 'Cookie jar is expected to contain 0 cookies');

        $jar->addCookie('foo=bar; domain=example.com');
        $cookie = $jar->getCookie('http://example.com/', 'foo');
        $this->assertInstanceOf(Zend_Http_Cookie::class, $cookie, '$cookie is expected to be a Cookie object');
        $this->assertEquals('bar', $cookie->getValue(), 'Cookie value is expected to be "bar"');

        $jar->addCookie('cookie=brownie; domain=geekz.co.uk;');
        $this->assertCount(2, $jar->getAllCookies(), 'Cookie jar is expected to contain 2 cookies');
    }

    /**
     * Check we get an expection if a non-valid cookie is passed to addCookie
     *
     */
    public function testExceptAddInvalidCookie()
    {
        $jar = new Zend_Http_CookieJar();

        $this->expectException(Zend_Http_Exception::class);
        $jar->addCookie('garbage');
    }

    /**
     * Check we get an expection if a non-valid cookie is passed to addCookie
     *
     */
    public function testExceptAddInvalidCookieFromEmptyJar()
    {
        $jar = new Zend_Http_CookieJar();

        $this->expectException(Zend_Http_Exception::class);
        $jar->addCookie(new Zend_Http_CookieJar());
    }

    /**
     * Test we can read cookies from a Response object
     *
     */
    public function testAddCookiesFromResponse()
    {
        $jar     = new Zend_Http_CookieJar();
        $res_str = $this->loadResponse(
            dirname(realpath(__FILE__)) . '/_files/response_with_cookies'
        );
        $response = Zend_Http_Response::fromString($res_str);

        $jar->addCookiesFromResponse($response, 'http://www.example.com');

        $this->assertCount(3, $jar->getAllCookies());

        $cookie_str = 'foo=bar;BOFH=Feature+was+not+beta+tested;time=1164234700;';
        $this->assertEquals($cookie_str, $jar->getAllCookies(Zend_Http_CookieJar::COOKIE_STRING_CONCAT));
    }

    /**
     * Test we get an exception in case of invalid response objects
     *
     * @dataProvider invalidResponseProvider
     */
    public function testExceptAddCookiesInvalidResponse($resp)
    {
        $this->expectException(\Zend_Http_Exception::class);

        $jar = new Zend_Http_CookieJar();
        $jar->addCookiesFromResponse($resp, 'http://www.example.com');
    }

    public static function invalidResponseProvider()
    {
        return array(
            array(new stdClass),
            array(null),
            array(12),
            array('hi')
        );
    }

    /**
     * Test we can get all cookies as an array of Cookie objects
     *
     */
    public function testGetAllCookies()
    {
        $jar = new Zend_Http_CookieJar();

        $cookies = array(
            'name=Arthur; domain=camelot.gov.uk',
            'quest=holy+grail; domain=forest.euwing.com',
            'swallow=african; domain=bridge-of-death.net'
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $cobjects = $jar->getAllCookies();

        foreach ($cobjects as $id => $cookie) {
            $this->assertStringContainsString((string) $cookie, $cookies[$id]);
        }
    }

    /**
     * Test we can get all cookies as a concatenated string
     *
     */
    public function testGetAllCookiesAsConcat()
    {
        $jar = new Zend_Http_CookieJar();

        $cookies = array(
            'name=Arthur; domain=camelot.gov.uk',
            'quest=holy+grail; domain=forest.euwing.com',
            'swallow=african; domain=bridge-of-death.net'
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $expected = 'name=Arthur;quest=holy+grail;swallow=african;';
        $real     = $jar->getAllCookies(Zend_Http_CookieJar::COOKIE_STRING_CONCAT);

        $this->assertEquals($expected, $real, 'Concatenated string is not as expected');
    }

    /**
     * Test we can get all cookies as a concatenated string
     * @group ZF-11726
     */
    public function testGetAllCookiesAsConcatStrictMode()
    {
        $jar = new Zend_Http_CookieJar();

        $cookies = array(
            'name=Arthur; domain=camelot.gov.uk',
            'quest=holy+grail; domain=forest.euwing.com',
            'swallow=african; domain=bridge-of-death.net'
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $expected = 'name=Arthur; quest=holy+grail; swallow=african';
        $real     = $jar->getAllCookies(Zend_Http_CookieJar::COOKIE_STRING_CONCAT_STRICT);

        $this->assertEquals($expected, $real, 'Concatenated string is not as expected');
    }

    /**
     * Test we can get a single cookie as an object
     *
     */
    public function testGetCookieAsObject()
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar    = new Zend_Http_CookieJar();
        $jar->addCookie($cookie->__toString(), 'http://www.example.com/tests/');

        $cobj = $jar->getCookie('http://www.example.com/tests/', 'foo');

        $this->assertInstanceOf(Zend_Http_Cookie::class, $cobj, '$cobj is not a Cookie object');
        $this->assertEquals($cookie->getName(), $cobj->getName(), 'Cookie name is not as expected');
        $this->assertEquals($cookie->getValue(), $cobj->getValue(), 'Cookie value is not as expected');
        $this->assertEquals($cookie->getDomain(), $cobj->getDomain(), 'Cookie domain is not as expected');
        $this->assertEquals($cookie->getPath(), $cobj->getPath(), 'Cookie path is not as expected');
    }

    /**
     * Check we can get a cookie as a string
     */
    public function testGetCookieAsString()
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar    = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $cstr = $jar->getCookie('http://www.example.com/tests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
        $this->assertEquals($cookie->__toString(), $cstr, 'Cookie string is not the expected string');

        $cstr = $jar->getCookie('http://www.example.com/tests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertEquals($cookie->__toString(), $cstr, 'Cookie string is not the expected string');
    }

    /**
     * Check we can get false when trying to get a non-existant cookie
     */
    public function testGetCookieReturnFalse()
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar    = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $cstr = $jar->getCookie('http://www.example.com/tests/', 'otherfoo', Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
        $this->assertFalse($cstr, 'getCookie was expected to return false, no such cookie');

        $cstr = $jar->getCookie('http://www.otherexample.com/tests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertFalse($cstr, 'getCookie was expected to return false, no such domain');

        $cstr = $jar->getCookie('http://www.example.com/othertests/', 'foo', Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertFalse($cstr, 'getCookie was expected to return false, no such path');
    }

    /**
     * Test we get a proper exception when an invalid URI is passed
     */
    public function testExceptGetCookieInvalidUri()
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar    = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $this->expectException(Zend_Exception::class);
        $jar->getCookie('foo.com', 'foo');
    }

    /**
     * Test we get a proper exception when an invalid URI is passed
     */
    public function testExceptGetCookieInvalidUri2()
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=www.example.com; path=/tests');
        $jar    = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $this->expectException(Zend_Exception::class);
        $jar->getCookie(Zend_Uri::factory('mailto:nobody@dev.null.com'), 'foo');
    }

    /**
     * Test we get a proper exception when an invalid return constant is passed
     *
     */
    public function testExceptGetCookieInvalidReturnType()
    {
        $cookie = Zend_Http_Cookie::fromString('foo=bar; domain=example.com;');
        $jar    = new Zend_Http_CookieJar();
        $jar->addCookie($cookie);

        $this->expectException(Zend_Http_Exception::class);
        $jar->getCookie('http://example.com/', 'foo', 5);
    }

    /**
     * Test we can get all matching cookies for a request, with session cookies
     *
     * @dataProvider cookieMatchTestProvider
     */
    public function testGetMatchingCookies($url, $expected)
    {
        $jar     = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=newwww.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }
        $cookies = $jar->getMatchingCookies($url);
        $this->assertCount($expected, $cookies, $jar->getMatchingCookies($url, true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT));
    }

    public static function cookieMatchTestProvider()
    {
        return array(
            array('http://www.foo.com/path/file.txt', 4),
            array('http://foo.com/path/file.txt', 3),
            array('https://www.foo.com/path/file.txt', 5),
            array('http://subdomain.foo.com/path', 4),
            array('http://subdomain.foo.com/otherpath', 3),
            array('http://blog.foo.com/news', 2)
        );
    }

    /**
     * Test we can get all matching cookies for a request, without session cookies
     */
    public function testGetMatchingCookiesNoSession()
    {
        $jar     = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $this->assertCount(8, $jar->getAllCookies(), 'Cookie count is expected to be 8');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', false);
        $this->assertCount(3, $cookies, 'Cookie count is expected to be 3');

        $cookies = $jar->getMatchingCookies('https://www.foo.com/path/file.txt', false);
        $this->assertCount(4, $cookies, 'Cookie count is expected to be 4');
    }

    /**
     * Test we can get all matching cookies for a request, when we set a different time for now
     */
    public function testGetMatchingCookiesWithTime()
    {
        $jar     = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 7200)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() - 7200)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $this->assertCount(8, $jar->getAllCookies(), 'Cookie count is expected to be 8');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_OBJECT, time() + 3700);
        $this->assertCount(2, $cookies, 'Cookie count is expected to be 2');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_OBJECT, time() - 3700);
        $this->assertCount(5, $cookies, 'Cookie count is expected to be 5');
    }

    /**
     * Test we can get all matching cookies for a request, and return as strings array / concat
     */
    public function testGetMatchingCookiesAsStrings()
    {
        $jar     = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo3=bar3; domain=.foo.com; path=/; expires=' . date(DATE_COOKIE, time() - 3600)),
            Zend_Http_Cookie::fromString('foo4=bar4; domain=.foo.com; path=/;'),
            Zend_Http_Cookie::fromString('foo5=bar5; domain=.foo.com; path=/; secure; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo6=bar6; domain=.foo.com; path=/otherpath; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo7=bar7; domain=www.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
            Zend_Http_Cookie::fromString('foo8=bar8; domain=subdomain.foo.com; path=/path; expires=' . date(DATE_COOKIE, time() + 3600)),
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }

        $this->assertCount(8, $jar->getAllCookies(), 'Cookie count is expected to be 8');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
        $this->assertIsArray($cookies, '$cookies is expected to be an array, but it is not');
        $this->assertIsString($cookies[0], '$cookies[0] is expected to be a string');
        ;

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT);
        $this->assertIsString($cookies, '$cookies is expected to be a string');
        $expected = 'foo1=bar1;foo2=bar2;foo4=bar4;foo7=bar7;';
        $this->assertEquals($expected, $cookies, 'Concatenated string is not as expected');

        $cookies = $jar->getMatchingCookies('http://www.foo.com/path/file.txt', true, Zend_Http_CookieJar::COOKIE_STRING_CONCAT_STRICT);
        $this->assertIsString($cookies, '$cookies is expected to be a string');
        $expected = 'foo1=bar1; foo2=bar2; foo4=bar4; foo7=bar7';
        $this->assertEquals($expected, $cookies, 'Concatenated string is not as expected');
    }

    /**
     * Test we get a proper exception when an invalid URI is passed
     */
    public function testExceptGetMatchingCookiesInvalidUri()
    {
        $jar = new Zend_Http_CookieJar();

        $this->expectException(Zend_Exception::class);
        $cookies = $jar->getMatchingCookies('invalid.com', true, Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
    }

    /**
     * Test we get a proper exception when an invalid URI is passed
     */
    public function testExceptGetMatchingCookiesInvalidUri2()
    {
        $jar = new Zend_Http_CookieJar();

        $this->expectException(Zend_Exception::class);
        $cookies = $jar->getMatchingCookies(new stdClass(), true, Zend_Http_CookieJar::COOKIE_STRING_ARRAY);
    }

    /**
     * Test we can build a new object from a response object (single cookie header)
     */
    public function testFromResponse()
    {
        $res_str = $this->loadResponse(
            dirname(realpath(__FILE__)) . '/_files/response_with_single_cookie'
        );
        $response = Zend_Http_Response::fromString($res_str);

        $jar = Zend_Http_CookieJar::fromResponse($response, 'http://www.example.com');

        $this->assertInstanceOf(Zend_Http_CookieJar::class, $jar, '$jar is not an instance of CookieJar as expected');
        $this->assertCount(1, $jar->getAllCookies(), 'CookieJar expected to contain 1 cookie');
    }

    /**
     * Test we can build a new object from a response object (multiple cookie headers)
     */
    public function testFromResponseMultiHeader()
    {
        $res_str = $this->loadResponse(
            dirname(realpath(__FILE__)) . '/_files/response_with_cookies'
        );
        $response = Zend_Http_Response::fromString($res_str);

        $jar = Zend_Http_CookieJar::fromResponse($response, 'http://www.example.com');

        $this->assertInstanceOf(Zend_Http_CookieJar::class, $jar, '$jar is not an instance of CookieJar as expected');
        $this->assertCount(3, $jar->getAllCookies(), 'CookieJar expected to contain 3 cookies');
    }

    /**
     * Make sure that paths with trailing slashes are matched as well as paths with no trailing slashes
     */
    public function testMatchPathWithTrailingSlash()
    {
        $jar     = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.example.com; path=/a/b'),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.example.com; path=/a/b/')
        );

        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }
        $cookies = $jar->getMatchingCookies('http://www.example.com/a/b/file.txt');

        $this->assertIsArray($cookies);
        $this->assertCount(2, $cookies);
    }

    public function testIteratorAndCountable()
    {
        $jar     = new Zend_Http_CookieJar();
        $cookies = array(
            Zend_Http_Cookie::fromString('foo1=bar1; domain=.example.com; path=/a/b'),
            Zend_Http_Cookie::fromString('foo2=bar2; domain=.example.com; path=/a/b/')
        );
        foreach ($cookies as $cookie) {
            $jar->addCookie($cookie);
        }
        foreach ($jar as $cookie) {
            $this->assertInstanceOf(Zend_Http_Cookie::class, $cookie);
        }
        $this->assertCount(2, $jar);
        $this->assertFalse($jar->isEmpty());
        $jar->reset();
        $this->assertTrue($jar->isEmpty());
    }
}
