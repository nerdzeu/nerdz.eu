<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NERDZ\Core;

/**
 * File: Browser.php
 * Author: Chris Schuld (http://chrisschuld.com/)
 * Last Modified: August 20th, 2010.
 *
 * @version 1.9
 */
final class Browser
{
    private $_agent = '';
    private $_browser_name = '';
    private $_version = '';
    private $_platform = '';
    private $_platver = '';
    private $_is_aol = false;
    private $_is_mobile = false;
    private $_is_robot = false;
    private $_aol_version = '';

    const BROWSER_UNKNOWN = 'unknown';
    const VERSION_UNKNOWN = 'unknown';

    const BROWSER_OPERA = 'Opera';                            // http://www.opera.com/ version >12
    const BROWSER_CLASSIC_OPERA = 'Classic Opera';            // http://www.opera.com/ version <=12
    const BROWSER_OPERA_MINI = 'Opera Mini';                  // http://www.opera.com/mini/
    const BROWSER_WEBTV = 'WebTV';                            // http://www.webtv.net/pc/
    const BROWSER_IE = 'Internet Explorer';                   // http://www.microsoft.com/ie/
    const BROWSER_POCKET_IE = 'Pocket Internet Explorer';     // http://en.wikipedia.org/wiki/Internet_Explorer_Mobile
    const BROWSER_KONQUEROR = 'Konqueror';                    // http://www.konqueror.org/
    const BROWSER_ICAB = 'iCab';                              // http://www.icab.de/
    const BROWSER_OMNIWEB = 'OmniWeb';                        // http://www.omnigroup.com/applications/omniweb/
    const BROWSER_FIREBIRD = 'Firebird';                      // http://www.ibphoenix.com/
    const BROWSER_FIREFOX = 'Firefox';                        // http://www.mozilla.com/en-US/firefox/firefox.html
    const BROWSER_ICEWEASEL = 'Iceweasel';                    // http://www.geticeweasel.org/
    const BROWSER_SHIRETOKO = 'Shiretoko';                    // http://wiki.mozilla.org/Projects/shiretoko
    const BROWSER_MOZILLA = 'Mozilla';                        // http://www.mozilla.com/en-US/
    const BROWSER_SEAMONKEY = 'SeaMonkey';                    // http://www.seamonkey-project.org/
    const BROWSER_AMAYA = 'Amaya';                            // http://www.w3.org/Amaya/
    const BROWSER_EPIPHANY = 'Epiphany';                      // http://projects.gnome.org/epiphany/
    const BROWSER_LYNX = 'Lynx';                              // http://en.wikipedia.org/wiki/Lynx
    const BROWSER_SAFARI = 'Safari';                          // http://apple.com
    const BROWSER_IPHONE = 'iOS';                             // http://apple.com
    const BROWSER_IPOD = 'iOS';                               // http://apple.com
    const BROWSER_IPAD = 'iOS';                               // http://apple.com
    const BROWSER_CHROME = 'Chrome';                          // http://www.google.com/chrome
    const BROWSER_ANDROID = 'Android Stock Browser';          // http://www.android.com/
    const BROWSER_GOOGLEBOT = 'GoogleBot';                    // http://en.wikipedia.org/wiki/Googlebot
    const BROWSER_SLURP = 'Yahoo! Slurp';                     // http://en.wikipedia.org/wiki/Yahoo!_Slurp
    const BROWSER_W3CVALIDATOR = 'W3C Validator';             // http://validator.w3.org/
    const BROWSER_BLACKBERRY = 'BlackBerry';                  // http://www.blackberry.com/
    const BROWSER_ICECAT = 'IceCat';                          // http://en.wikipedia.org/wiki/GNU_IceCat
    const BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser';        // http://en.wikipedia.org/wiki/Web_Browser_for_S60
    const BROWSER_NOKIA = 'Nokia Browser';                    // * all other WAP-based browsers on the Nokia Platform
    const BROWSER_MSN = 'MSN Browser';                        // http://explorer.msn.com/
    const BROWSER_MSNBOT = 'MSN Bot';                         // http://search.msn.com/msnbot.htm
    const BROWSER_BINGBOT = 'Bing Bot';

    const BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator';  // http://browser.netscape.com/ (DEPRECATED)
    const BROWSER_GALEON = 'Galeon';                          // http://galeon.sourceforge.net/ (DEPRECATED)
    const BROWSER_NETPOSITIVE = 'NetPositive';                // http://en.wikipedia.org/wiki/NetPositive (DEPRECATED)
    const BROWSER_PHOENIX = 'Phoenix';                        // http://en.wikipedia.org/wiki/History_of_Mozilla_Firefox (DEPRECATED)

    const PLATFORM_UNKNOWN = 'unknown';
    const PLATFORM_WINDOWS = 'Windows';
    const PLATFORM_WINDOWS_PHONE = 'Windows Phone';
    const PLATFORM_WINDOWS_CE = 'Windows CE';
    const PLATFORM_APPLE = 'Mac OS X';
    const PLATFORM_LINUX = 'GNU/Linux';
    const PLATFORM_OS2 = 'OS/2';
    const PLATFORM_BEOS = 'BeOS';
    const PLATFORM_IPHONE = 'iPhone';
    const PLATFORM_IPOD = 'iPod';
    const PLATFORM_IPAD = 'iPad';
    const PLATFORM_BLACKBERRY = 'BlackBerry';
    const PLATFORM_NOKIA = 'Nokia';
    const PLATFORM_FREEBSD = 'FreeBSD';
    const PLATFORM_OPENBSD = 'OpenBSD';
    const PLATFORM_NETBSD = 'NetBSD';
    const PLATFORM_SUNOS = 'SunOS';
    const PLATFORM_OPENSOLARIS = 'OpenSolaris';
    const PLATFORM_ANDROID = 'Android';
    const PLATFORM_CHROMEOS = 'Chrome OS (GNU/Linux)';

    const WINVER_XP = 'XP';
    const WINVER_VISTA = 'Vista';
    const WINVER_7 = '7';
    const WINVER_8 = '8';
    const WINVER_81 = '8.1';

    const LINARCH_IA32 = 'IA32';
    const LINARCH_x86_64 = 'x86_64';
    const LINARCH_ARM = 'ARM';

    const ANDRVER_GB = 'Gingerbread';
    const ANDRVER_ICS = 'Ice Cream Sandwich';
    const ANDRVER_JB = 'Jelly Bean';
    const ANDRVER_KK = 'KitKat';

    const PLATVER_UNKNOWN = '';

    const OPERATING_SYSTEM_UNKNOWN = 'Unknown OS';

    public function __construct($useragent = null)
    {
        $this->reset();
        if ($useragent != null) {
            $this->setUserAgent($useragent);
        }
    }

        /**
         * Reset all properties.
         */
        public function reset()
        {
            $this->_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $this->_browser_name = static::BROWSER_UNKNOWN;
            $this->_version = static::VERSION_UNKNOWN;
            $this->_platform = static::PLATFORM_UNKNOWN;
            $this->_platver = static::PLATVER_UNKNOWN;
            $this->_is_aol = false;
            $this->_is_mobile = false;
            $this->_is_robot = false;
            $this->_aol_version = static::VERSION_UNKNOWN;
        }

        /**
         * Check to see if the specific browser is valid.
         *
         * @param string $browserName
         *
         * @return true if the browser is the specified browser
         */
        public function isBrowser($browserName)
        {
            return  0 == strcasecmp($this->_browser_name, trim($browserName));
        }

        /**
         * The name of the browser.  All return types are from the class contants.
         *
         * @return string Name of the browser
         */
        public function getBrowser()
        {
            return $this->_browser_name;
        }

        /**
         * Set the name of the browser.
         *
         * @param $browser The name of the Browser
         */
        public function setBrowser($browser)
        {
            return $this->_browser_name = $browser;
        }

        /**
         * The name of the platform.  All return types are from the class contants.
         *
         * @return string Name of the browser
         */
        public function getPlatform()
        {
            return $this->_platform;
        }

        /**
         * Platform version, if available.
         *
         * @return string Name of the version or architecture of the platform you're running
         */
        public function getPlatformVersion()
        {
            return $this->_platver;
        }

    public function setPlatformVersion($platver)
    {
        $this->_platver = $platver;
    }

        /**
         * Set the name of the platform.
         *
         * @param $platform The name of the Platform
         */
        public function setPlatform($platform)
        {
            return $this->_platform = $platform;
        }

        /**
         * The version of the browser.
         *
         * @return string Version of the browser (will only contain alpha-numeric characters and a period)
         */
        public function getVersion()
        {
            return $this->_version;
        }

        /**
         * Set the version of the browser.
         *
         * @param $version The version of the Browser
         */
        public function setVersion($version)
        {
            $this->_version = preg_replace('/[^0-9,.,a-z,A-Z-]/', '', $version);
        }

        /**
         * The version of AOL.
         *
         * @return string Version of AOL (will only contain alpha-numeric characters and a period)
         */
        public function getAolVersion()
        {
            return $this->_aol_version;
        }

        /**
         * Set the version of AOL.
         *
         * @param $version The version of AOL
         */
        public function setAolVersion($version)
        {
            $this->_aol_version = preg_replace('/[^0-9,.,a-z,A-Z]/', '', $version);
        }
        /**
         * Is the browser from AOL?
         *
         * @return bool True if the browser is from AOL otherwise false
         */
        public function isAol()
        {
            return $this->_is_aol;
        }

        /**
         * Is the browser from a mobile device?
         *
         * @return bool True if the browser is from a mobile device otherwise false
         */
        public function isMobile()
        {
            return $this->_is_mobile;
        }

        /**
         * Is the browser from a robot (ex Slurp,GoogleBot)?
         *
         * @return bool True if the browser is from a robot otherwise false
         */
        public function isRobot()
        {
            return $this->_is_robot;
        }

        /**
         * Set the browser to be from AOL.
         *
         * @param $isAol
         */
        public function setAol($isAol)
        {
            $this->_is_aol = $isAol;
        }

        /**
         * Set the Browser to be mobile.
         *
         * @param bool $value is the browser a mobile brower or not
         */
        private function setMobile($value = true)
        {
            $this->_is_mobile = $value;
        }

        /**
         * Set the Browser to be a robot.
         *
         * @param bool $value is the browser a robot or not
         */
        private function setRobot($value = true)
        {
            $this->_is_robot = $value;
        }

        /**
         * Get the user agent value in use to determine the browser.
         *
         * @return string The user agent from the HTTP header
         */
        public function getUserAgent()
        {
            return $this->_agent;
        }

        /**
         * Set the user agent value (the construction will use the HTTP header value - this will overwrite it).
         *
         * @param $agent_string The value for the User Agent
         */
        public function setUserAgent($agent_string)
        {
            $this->reset();
            $this->_agent = $agent_string;
            $this->determine();
        }

        /**
         * Used to determine if the browser is actually "chromeframe".
         *
         * @since 1.7
         *
         * @return bool True if the browser is using chromeframe
         */
        public function isChromeFrame()
        {
            return  strpos($this->_agent, 'chromeframe') !== false;
        }
        /**
         * Returns a formatted string with a summary of the details of the browser.
         *
         * @return string formatted string with a summary of the browser
         */
        public function toString()
        {
            return "{$this->getBrowser()}\n".
                "{$this->getVersion()}\n".
                $this->getPlatform();
        }

        /**
         * Returns an array with name, version and plaform of the browser.
         */
        public function getArray()
        {
            return [
                'name' => $this->getBrowser(),
                    'version' => $this->versionTrim($this->getVersion()),
                'platform' => $this->getPlatform(),
                'platver' => $this->getPlatformVersion(),
            ];
        }

        /**
         * private routine to calculate and determine what the browser is in use (including platform).
         */
        private function determine()
        {
            $this->checkPlatform();
            $this->checkBrowsers();
            $this->checkForAol();
        }

         /**
          * private routine to determine the browser type.
          *
          * @return bool True if the browser was detected otherwise false
          */
         private function checkBrowsers()
         {
             return
                 // well-known, well-used
                 // Special Notes:
                 // (1) Classic Opera must be checked before FireFox due to the odd
                 //     user agents used in some older versions of Opera and new Opera must be checked 
                 //     before Chrome due its being a Chromium-based browser.
                 // (2) WebTV is strapped onto Internet Explorer so we must
                 //     check for WebTV before IE
                 // (3) (deprecated) Galeon is based on Firefox and needs to be
                 //     tested before Firefox is tested
                 // (4) OmniWeb is based on Safari so OmniWeb check must occur
                 //     before Safari
                 // (5) Netscape 9+ is based on Firefox so Netscape checks
                 //     before FireFox are necessary
                 $this->checkBrowserWebTv() ||
                 $this->checkBrowserInternetExplorer() ||
                 $this->checkBrowserOpera() ||
                 $this->checkBrowserClassicOpera() ||
                 $this->checkBrowserGaleon() ||
                 $this->checkBrowserNetscapeNavigator9Plus() ||
                 $this->checkBrowserSeaMonkey() ||
                 $this->checkBrowserIceweasel() ||
                 $this->checkBrowserFirefox() ||
                 $this->checkBrowserChrome() ||
                 $this->checkBrowserEpiphany() ||
                 $this->checkBrowserOmniWeb() ||

                 // common mobile
                 $this->checkBrowserAndroid() ||
                 $this->checkBrowseriPad() ||
                 $this->checkBrowseriPod() ||
                 $this->checkBrowseriPhone() ||
                 $this->checkBrowserBlackBerry() ||
                 $this->checkBrowserNokia() ||

                 // common bots
                 $this->checkBrowserGoogleBot() ||
                 $this->checkBrowserMSNBot() ||
                 $this->checkBrowserBingBot() ||
                 $this->checkBrowserSlurp() ||

                 // WebKit base check (post mobile and others)
                 $this->checkBrowserSafari() ||

                 // everyone else
                 $this->checkBrowserNetPositive() ||
                 $this->checkBrowserFirebird() ||
                 $this->checkBrowserKonqueror() ||
                 $this->checkBrowserIcab() ||
                 $this->checkBrowserPhoenix() ||
                 $this->checkBrowserAmaya() ||
                 $this->checkBrowserLynx() ||
                 $this->checkBrowserShiretoko() ||
                 $this->checkBrowserIceCat() ||
                 $this->checkBrowserW3CValidator() ||
                 $this->checkBrowserMozilla() /* Mozilla is such an open standard that you must check it last */
             ;
         }

        /**
         * Determine if the user is using a BlackBerry (last updated 1.7).
         *
         * @return bool True if the browser is the BlackBerry browser otherwise false
         */
        private function checkBrowserBlackBerry()
        {
            if (stripos($this->_agent, 'blackberry') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'BlackBerry'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->_browser_name = static::BROWSER_BLACKBERRY;
                    $this->setMobile(true);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the user is using an AOL User Agent (last updated 1.7).
         *
         * @return bool True if the browser is from AOL otherwise false
         */
        private function checkForAol()
        {
            $this->setAol(false);
            $this->setAolVersion(static::VERSION_UNKNOWN);

            if (stripos($this->_agent, 'aol') !== false) {
                $aversion = explode(' ', stristr($this->_agent, 'AOL'));
                $this->setAol(true);
                $this->setAolVersion(preg_replace('/[^0-9\.a-z]/i', '', $aversion[1]));

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is the GoogleBot or not (last updated 1.7).
         *
         * @return bool True if the browser is the GoogletBot otherwise false
         */
        private function checkBrowserGoogleBot()
        {
            if (stripos($this->_agent, 'googlebot') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'googlebot'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion(str_replace(';', '', $aversion[0]));
                    $this->_browser_name = static::BROWSER_GOOGLEBOT;
                    $this->setRobot(true);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is the MSNBot or not (last updated 1.9).
         *
         * @return bool True if the browser is the MSNBot otherwise false
         */
        private function checkBrowserMSNBot()
        {
            if (stripos($this->_agent, 'msnbot') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'msnbot'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion(str_replace(';', '', $aversion[0]));
                    $this->_browser_name = static::BROWSER_MSNBOT;
                    $this->setRobot(true);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is the BingBot or not (last updated 1.9).
         *
         * @return bool True if the browser is the BingBot otherwise false
         */
        private function checkBrowserBingBot()
        {
            if (stripos($this->_agent, 'bingbot') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'bingbot'));
                $aversion = explode(' ', $aresult[1]);
                if (isset($aresult[1])) {
                    $this->setVersion(str_replace(';', '', $aversion[0]));
                    $this->_browser_name = self::BROWSER_BINGBOT;
                    $this->setRobot(true);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is the W3C Validator or not (last updated 1.7).
         *
         * @return bool True if the browser is the W3C Validator otherwise false
         */
        private function checkBrowserW3CValidator()
        {
            if (stripos($this->_agent, 'W3C-checklink') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'W3C-checklink'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->_browser_name = static::BROWSER_W3CVALIDATOR;

                    return true;
                }
            } elseif (stripos($this->_agent, 'W3C_Validator') !== false) {
                // Some of the Validator versions do not delineate w/ a slash - add it back in
                    $ua = str_replace('W3C_Validator ', 'W3C_Validator/', $this->_agent);
                $aresult = explode('/', stristr($ua, 'W3C_Validator'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->_browser_name = static::BROWSER_W3CVALIDATOR;

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is the Yahoo! Slurp Robot or not (last updated 1.7).
         *
         * @return bool True if the browser is the Yahoo! Slurp Robot otherwise false
         */
        private function checkBrowserSlurp()
        {
            if (stripos($this->_agent, 'slurp') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Slurp'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->_browser_name = static::BROWSER_SLURP;
                    $this->setRobot(true);
                    $this->setMobile(false);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Internet Explorer or not (last updated 1.7).
         *
         * @return bool True if the browser is Internet Explorer otherwise false
         */
        private function checkBrowserInternetExplorer()
        {
            // Test for v1 - v1.5 IE
            if (stripos($this->_agent, 'microsoft internet explorer') !== false) {
                $this->setBrowser(static::BROWSER_IE);
                $this->setVersion('1.0');
                $aresult = stristr($this->_agent, '/');
                if (preg_match('/308|425|426|474|0b1/i', $aresult)) {
                    $this->setVersion('1.5');
                }

                return true;
            }
            // Test for versions > 1.5
            elseif (stripos($this->_agent, 'msie') !== false && stripos($this->_agent, 'opera') === false) {
                // See if the browser is the odd MSN Explorer
                if (stripos($this->_agent, 'msnb') !== false) {
                    $aresult = explode(' ', stristr(str_replace(';', '; ', $this->_agent), 'MSN'));
                    if (isset($aresult[1])) {
                        $this->setBrowser(static::BROWSER_MSN);
                        $this->setVersion(str_replace(['(', ')', ';'], '', $aresult[1]));

                        return true;
                    }
                }
                $aresult = explode(' ', stristr(str_replace(';', '; ', $this->_agent), 'msie'));
                if (isset($aresult[1])) {
                    $this->setBrowser(static::BROWSER_IE);
                    $this->setVersion(str_replace(['(', ')', ';'], '', $aresult[1]));

                    return true;
                }
            }
            // Test for Pocket IE
            elseif (stripos($this->_agent, 'mspie') !== false || stripos($this->_agent, 'pocket') !== false) {
                $aresult = explode(' ', stristr($this->_agent, 'mspie'));
                if (isset($aresult[1])) {
                    $this->setPlatform(static::PLATFORM_WINDOWS_CE);
                    $this->setBrowser(static::BROWSER_POCKET_IE);
                    $this->setMobile(true);

                    if (stripos($this->_agent, 'mspie') !== false) {
                        $this->setVersion($aresult[1]);
                    } else {
                        $aversion = explode('/', $this->_agent);
                        $this->setVersion($aversion[1]);
                    }

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is the new Chromium-based Opera or not.
         *
         * @return bool True if the browser is Opera otherwise false
         */
        private function checkBrowserOpera()
        {
            if (stripos($this->_agent, 'OPR') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'OPR'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->setBrowser(static::BROWSER_OPERA);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Classic Opera Presto-based or not (last updated 1.7).
         *
         * @return bool True if the browser is Opera otherwise false
         */
        private function checkBrowserClassicOpera()
        {
            if (stripos($this->_agent, 'opera mini') !== false) {
                $resultant = stristr($this->_agent, 'opera mini');
                if (preg_match('/\//', $resultant)) {
                    $aresult = explode('/', $resultant);
                    if (isset($aresult[1])) {
                        $aversion = explode(' ', $aresult[1]);
                        $this->setVersion($aversion[0]);
                    }
                } else {
                    $aversion = explode(' ', stristr($resultant, 'opera mini'));
                    $this->setVersion($aversion[1]);
                }
                $this->_browser_name = static::BROWSER_OPERA_MINI;
                $this->setMobile(true);

                return true;
            } elseif (stripos($this->_agent, 'opera') !== false) {
                $resultant = stristr($this->_agent, 'opera');
                if (preg_match('/Version\/(.*)$/', $resultant, $matches)) {
                    $this->setVersion($matches[1]);
                } elseif (preg_match('/\//', $resultant)) {
                    $aresult = explode('/', str_replace('(', ' ', $resultant));
                    if (isset($aresult[1])) {
                        $aversion = explode(' ', $aresult[1]);
                        $this->setVersion($aversion[0]);
                    }
                } else {
                    $aversion = explode(' ', stristr($resultant, 'opera'));
                    $this->setVersion(isset($aversion[1]) ? $aversion[1] : '');
                }
                $this->_browser_name = static::BROWSER_CLASSIC_OPERA;

                return true;
            }

            return false;
        }

    private function checkBrowserEpiphany()
    {
        if (stripos($this->_agent, 'Epiphany') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Epiphany'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(static::BROWSER_EPIPHANY);

                return true;
            }
        }

        return false;
    }

        /**
         * Determine if the browser is WebTv or not (last updated 1.7).
         *
         * @return bool True if the browser is WebTv otherwise false
         */
        private function checkBrowserWebTv()
        {
            if (stripos($this->_agent, 'webtv') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'webtv'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->setBrowser(static::BROWSER_WEBTV);

                    return true;
                }
            }

            return false;
        }

    private function checkBrowserChrome()
    {
        if (stripos($this->_agent, 'Chrome') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Chrome'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(static::BROWSER_CHROME);

                return true;
            }
        }

        return false;
    }

        /**
         * Determine if the browser is NetPositive or not (last updated 1.7).
         *
         * @return bool True if the browser is NetPositive otherwise false
         */
        private function checkBrowserNetPositive()
        {
            if (stripos($this->_agent, 'NetPositive') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'NetPositive'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion(str_replace(array('(', ')', ';'), '', $aversion[0]));
                    $this->setBrowser(static::BROWSER_NETPOSITIVE);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Galeon or not (last updated 1.7).
         *
         * @return bool True if the browser is Galeon otherwise false
         */
        private function checkBrowserGaleon()
        {
            if (stripos($this->_agent, 'galeon') !== false) {
                $aresult = explode(' ', stristr($this->_agent, 'galeon'));
                if (isset($aresult[1])) {
                    $aversion = explode('/', $aresult[0]);
                    $this->setVersion($aversion[1]);
                    $this->setBrowser(static::BROWSER_GALEON);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Konqueror or not (last updated 1.7).
         *
         * @return bool True if the browser is Konqueror otherwise false
         */
        private function checkBrowserKonqueror()
        {
            if (stripos($this->_agent, 'Konqueror') !== false) {
                $aresult = explode(' ', stristr($this->_agent, 'Konqueror'));
                if (isset($aresult[1])) {
                    $aversion = explode('/', $aresult[0]);
                    $this->setVersion($aversion[1]);
                    $this->setBrowser(static::BROWSER_KONQUEROR);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is iCab or not (last updated 1.7).
         *
         * @return bool True if the browser is iCab otherwise false
         */
        private function checkBrowserIcab()
        {
            if (stripos($this->_agent, 'icab') !== false) {
                $aversion = explode(' ', stristr(str_replace('/', ' ', $this->_agent), 'icab'));
                $this->setVersion($aversion[1]);
                $this->setBrowser(static::BROWSER_ICAB);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is OmniWeb or not (last updated 1.7).
         *
         * @return bool True if the browser is OmniWeb otherwise false
         */
        private function checkBrowserOmniWeb()
        {
            if (stripos($this->_agent, 'omniweb') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'omniweb'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', isset($aresult[1]) ? $aresult[1] : '');
                    $this->setVersion($aversion[0]);
                    $this->setBrowser(static::BROWSER_OMNIWEB);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Phoenix or not (last updated 1.7).
         *
         * @return bool True if the browser is Phoenix otherwise false
         */
        private function checkBrowserPhoenix()
        {
            if (stripos($this->_agent, 'Phoenix') !== false) {
                $aversion = explode('/', stristr($this->_agent, 'Phoenix'));
                $this->setVersion($aversion[1]);
                $this->setBrowser(static::BROWSER_PHOENIX);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Firebird or not (last updated 1.7).
         *
         * @return bool True if the browser is Firebird otherwise false
         */
        private function checkBrowserFirebird()
        {
            if (stripos($this->_agent, 'Firebird') !== false) {
                $aversion = explode('/', stristr($this->_agent, 'Firebird'));
                $this->setVersion($aversion[1]);
                $this->setBrowser(static::BROWSER_FIREBIRD);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Netscape Navigator 9+ or not (last updated 1.7)
         * NOTE: (http://browser.netscape.com/ - Official support ended on March 1st, 2008).
         *
         * @return bool True if the browser is Netscape Navigator 9+ otherwise false
         */
        private function checkBrowserNetscapeNavigator9Plus()
        {
            if (stripos($this->_agent, 'Firefox') !== false && preg_match('/Navigator\/([^ ]*)/i', $this->_agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser(static::BROWSER_NETSCAPE_NAVIGATOR);

                return true;
            } elseif (stripos($this->_agent, 'Firefox') === false && preg_match('/Netscape6?\/([^ ]*)/i', $this->_agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser(static::BROWSER_NETSCAPE_NAVIGATOR);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Shiretoko or not (https://wiki.mozilla.org/Projects/shiretoko) (last updated 1.7).
         *
         * @return bool True if the browser is Shiretoko otherwise false
         */
        private function checkBrowserShiretoko()
        {
            if (stripos($this->_agent, 'Mozilla') !== false && preg_match('/Shiretoko\/([^ ]*)/i', $this->_agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser(static::BROWSER_SHIRETOKO);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Ice Cat or not (http://en.wikipedia.org/wiki/GNU_IceCat) (last updated 1.7).
         *
         * @return bool True if the browser is Ice Cat otherwise false
         */
        private function checkBrowserIceCat()
        {
            if (stripos($this->_agent, 'Mozilla') !== false && preg_match('/IceCat\/([^ ]*)/i', $this->_agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser(static::BROWSER_ICECAT);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Nokia or not (last updated 1.7).
         *
         * @return bool True if the browser is Nokia otherwise false
         */
        private function checkBrowserNokia()
        {
            if (preg_match("/Nokia([^\/]+)\/([^ SP]+)/i", $this->_agent, $matches)) {
                $this->setVersion($matches[2]);
                if (stripos($this->_agent, 'Series60') !== false || strpos($this->_agent, 'S60') !== false) {
                    $this->setBrowser(static::BROWSER_NOKIA_S60);
                } else {
                    $this->setBrowser(static::BROWSER_NOKIA);
                }
                $this->setMobile(true);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is SeaMonkey or not (last updated 1.7).
         *
         * @return bool True if the browser is SeaMonkey otherwise false
         */
        private function checkBrowserSeaMonkey()
        {
            if (stripos($this->_agent, 'safari') === false) {
                if (preg_match("/ SeaMonkey[\/ \(]([^ ;\)]+)/i", $this->_agent, $matches)) {
                    $this->setVersion($matches[1]);
                    $this->setBrowser(static::BROWSER_SEAMONKEY);

                    return true;
                } elseif (preg_match('/SeaMonkey$/i', $this->_agent, $matches)) {
                    $this->setVersion('');
                    $this->setBrowser(static::BROWSER_SEAMONKEY);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Firefox or not (last updated 1.7).
         *
         * @return bool True if the browser is Firefox otherwise false
         */
        private function checkBrowserFirefox()
        {
            if (stripos($this->_agent, 'safari') === false) {
                if (preg_match("/Firefox[\/ \(]([^ ;\)]+)/i", $this->_agent, $matches)) {
                    $this->setVersion($matches[1]);
                    $this->setBrowser(static::BROWSER_FIREFOX);

                    return true;
                } elseif (preg_match('/Firefox$/i', $this->_agent, $matches)) {
                    $this->setVersion('');
                    $this->setBrowser(static::BROWSER_FIREFOX);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Firefox or not (last updated 1.7).
         *
         * @return bool True if the browser is Firefox otherwise false
         */
        private function checkBrowserIceweasel()
        {
            if (stripos($this->_agent, 'Iceweasel') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Iceweasel'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->setBrowser(static::BROWSER_ICEWEASEL);

                    return true;
                }
            }

            return false;
        }
        /**
         * Determine if the browser is Mozilla or not (last updated 1.7).
         *
         * @return bool True if the browser is Mozilla otherwise false
         */
        private function checkBrowserMozilla()
        {
            if (stripos($this->_agent, 'mozilla') !== false  && preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->_agent) && stripos($this->_agent, 'netscape') === false) {
                $aversion = explode(' ', stristr($this->_agent, 'rv:'));
                preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->_agent, $aversion);
                $this->setVersion(str_replace('rv:', '', $aversion[0]));
                $this->setBrowser(static::BROWSER_MOZILLA);

                return true;
            } elseif (stripos($this->_agent, 'mozilla') !== false && preg_match('/rv:[0-9]\.[0-9]/i', $this->_agent) && stripos($this->_agent, 'netscape') === false) {
                $aversion = explode('', stristr($this->_agent, 'rv:'));
                $this->setVersion(str_replace('rv:', '', $aversion[0]));
                $this->setBrowser(static::BROWSER_MOZILLA);

                return true;
            } elseif (stripos($this->_agent, 'mozilla') !== false  && preg_match('/mozilla\/([^ ]*)/i', $this->_agent, $matches) && stripos($this->_agent, 'netscape') === false) {
                $this->setVersion($matches[1]);
                $this->setBrowser(static::BROWSER_MOZILLA);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Lynx or not (last updated 1.7).
         *
         * @return bool True if the browser is Lynx otherwise false
         */
        private function checkBrowserLynx()
        {
            if (stripos($this->_agent, 'lynx') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Lynx'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', (isset($aresult[1]) ? $aresult[1] : ''));
                    $this->setVersion($aversion[0]);
                    $this->setBrowser(static::BROWSER_LYNX);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Amaya or not (last updated 1.7).
         *
         * @return bool True if the browser is Amaya otherwise false
         */
        private function checkBrowserAmaya()
        {
            if (stripos($this->_agent, 'amaya') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Amaya'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                    $this->setBrowser(static::BROWSER_AMAYA);

                    return true;
                }
            }

            return false;
        }

        /**
         * Determine if the browser is Safari or not (last updated 1.7).
         *
         * @return bool True if the browser is Safari otherwise false
         */
        private function checkBrowserSafari()
        {
            if (stripos($this->_agent, 'Safari') !== false && stripos($this->_agent, 'iPhone') === false && stripos($this->_agent, 'iPod') === false) {
                $aresult = explode('/', stristr($this->_agent, 'Version'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                } else {
                    $this->setVersion(static::VERSION_UNKNOWN);
                }

                $this->setBrowser(static::BROWSER_SAFARI);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is iPhone or not (last updated 1.7).
         *
         * @return bool True if the browser is iPhone otherwise false
         */
        private function checkBrowseriPhone()
        {
            if (stripos($this->_agent, 'iPhone') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Version'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                } else {
                    $this->setVersion(static::VERSION_UNKNOWN);
                }

                $this->setMobile(true);
                $this->setBrowser(static::BROWSER_IPHONE);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is iPod or not (last updated 1.7).
         *
         * @return bool True if the browser is iPod otherwise false
         */
        private function checkBrowseriPad()
        {
            if (stripos($this->_agent, 'iPad') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Version'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                } else {
                    $this->setVersion(static::VERSION_UNKNOWN);
                }

                $this->setMobile(true);
                $this->setBrowser(static::BROWSER_IPAD);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is iPod or not (last updated 1.7).
         *
         * @return bool True if the browser is iPod otherwise false
         */
        private function checkBrowseriPod()
        {
            if (stripos($this->_agent, 'iPod') !== false) {
                $aresult = explode('/', stristr($this->_agent, 'Version'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                } else {
                    $this->setVersion(static::VERSION_UNKNOWN);
                }

                $this->setMobile(true);
                $this->setBrowser(static::BROWSER_IPOD);

                return true;
            }

            return false;
        }

        /**
         * Determine if the browser is Android or not (last updated 1.7).
         *
         * @return bool True if the browser is Android otherwise false
         */
        private function checkBrowserAndroid()
        {
            if (stripos($this->_agent, 'Android') !== false) {
                $aresult = explode(' ', stristr($this->_agent, 'Android'));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                } else {
                    $this->setVersion(static::VERSION_UNKNOWN);
                }

                $this->setMobile(true);
                $this->setBrowser(static::BROWSER_ANDROID);

                return true;
            }

            return false;
        }

        /**
         * Determine the user's platform (last updated 1.7).
         */
        private function checkPlatform()
        {
            if (stripos($this->_agent, 'IEMobile') !== false) {
                $this->_platform = static::PLATFORM_WINDOWS_PHONE;

                return;
            }

            if (stripos($this->_agent, 'windows') !== false) {
                $this->checkWindowsVersion();
            } elseif (stripos($this->_agent, 'iPad') !== false) {
                $this->_platform = static::PLATFORM_IPAD;
            } elseif (stripos($this->_agent, 'iPod') !== false) {
                $this->_platform = static::PLATFORM_IPOD;
            } elseif (stripos($this->_agent, 'iPhone') !== false) {
                $this->_platform = static::PLATFORM_IPHONE;
            } elseif (stripos($this->_agent, 'mac') !== false) {
                $this->_platform = static::PLATFORM_APPLE;
            } elseif (stripos($this->_agent, 'android') !== false) {
                $this->_platform = static::PLATFORM_ANDROID;
            } elseif (stripos($this->_agent, 'CrOS') !== false) {
                $this->checkCrOSArchitecture();
            } elseif (stripos($this->_agent, 'linux') !== false) {
                $this->checkLinuxArchitecture();
            } elseif (stripos($this->_agent, 'Nokia') !== false) {
                $this->_platform = static::PLATFORM_NOKIA;
            } elseif (stripos($this->_agent, 'BlackBerry') !== false) {
                $this->_platform = static::PLATFORM_BLACKBERRY;
            } elseif (stripos($this->_agent, 'FreeBSD') !== false) {
                $this->_platform = static::PLATFORM_FREEBSD;
            } elseif (stripos($this->_agent, 'OpenBSD') !== false) {
                $this->_platform = static::PLATFORM_OPENBSD;
            } elseif (stripos($this->_agent, 'NetBSD') !== false) {
                $this->_platform = static::PLATFORM_NETBSD;
            } elseif (stripos($this->_agent, 'OpenSolaris') !== false) {
                $this->_platform = static::PLATFORM_OPENSOLARIS;
            } elseif (stripos($this->_agent, 'SunOS') !== false) {
                $this->_platform = static::PLATFORM_SUNOS;
            } elseif (stripos($this->_agent, 'OS\/2') !== false) {
                $this->_platform = static::PLATFORM_OS2;
            } elseif (stripos($this->_agent, 'BeOS') !== false) {
                $this->_platform = static::PLATFORM_BEOS;
            } elseif (stripos($this->_agent, 'win') !== false) {
                $this->_platform = static::PLATFORM_WINDOWS;
            }
        }

        /**
         * Determine the architecture of the ChromeOS system the user is using.
         */
        private function checkCrOSArchitecture()
        {
            $this->_platform = static::PLATFORM_CHROMEOS;

            if (stripos($this->_agent, 'CrOS x86_64') != false) {
                $this->_platver = static::LINARCH_x86_64;
            } elseif (stripos($this->_agent, 'CrOS i686') != false) {
                $this->_platver = static::LINARCH_IA32;
            } elseif (stripos($this->_agent, 'CrOS arm') != false) {
                $this->_platver = static::LINARCH_ARM;
            }
        }

        /**
         * Determine the architecture of the Linux system the user is using.
         */
        private function checkLinuxArchitecture()
        {
            $this->_platform = static::PLATFORM_LINUX;

            if (stripos($this->_agent, 'Linux x86_64') != false) {
                $this->_platver = static::LINARCH_x86_64;
            } elseif (stripos($this->_agent, 'Linux i686') != false) {
                $this->_platver = static::LINARCH_IA32;
            } elseif (stripos($this->_agent, 'Linux arm') != false) {
                $this->_platver = static::LINARCH_ARM;
            }
        }

        /**
         * Finds what version of Windows the user is using.
         */
        private function checkWindowsVersion()
        {
            $this->_platform = static::PLATFORM_WINDOWS;

            $matches = null;
            if (preg_match('/Windows NT (.*?)(;|\\))/', $this->_agent, $matches)) {
                switch (trim($matches[1])) {
                case '5.1':
                    $this->_platver = static::WINVER_XP;
                    break;
                case '6.0':
                    $this->_platver = static::WINVER_VISTA;
                    break;
                case '6.1':
                    $this->_platver = static::WINVER_7;
                    break;
                case '6.2':
                    $this->_platver = static::WINVER_8;
                    break;
                case '6.3':
                    $this->_platver = static::WINVER_81;
                    break;
                }
            }
        }

    private function versionTrim($string)
    {
        $elems = explode('.', $string);

        return count($elems) > 1 ? $elems[0].(($elems[1] !== '0') ? '.'.$elems[1] : '') : $string;
    }
}
