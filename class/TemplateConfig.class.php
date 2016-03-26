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

/*
 * Classe per la gestione dei template in senso stretto.
 * Si occupa di ottenere le variabili obbligatorie e facoltative dei template.
 * In sostanza, si occupa del parsing dei files /tpl/<tpl no>/template.values
 * Inoltre, gestisce la minimizzazione e la compressione dei file css e javascript che i template useranno
 */

require_once __DIR__.'/Autoload.class.php';

final class TemplateConfig
{
    private $tpl_no;
    private $lang;
    private $tpl;
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
        $this->tpl = $this->user->getTPL();
        $this->lang = $this->user->getBoardLanguage();
        $this->tpl_no = $this->tpl->getActualTemplateNumber();
    }

    public function getTemplateVars($page = null)
    {
        $templatepath = $_SERVER['DOCUMENT_ROOT']."/tpl/{$this->tpl_no}/template.values";
        $cachename = "tpl-{$this->tpl_no}-vars".Config\SITE_HOST;
        $cachevaluestime = "tpl-{$this->tpl_no}-values-time".Config\SITE_HOST;

        $control = false;

        if (!($valuestime = Utils::apc_get($cachevaluestime))) {
            $valuestime = Utils::apc_set($cachevaluestime, function () use (&$control, $templatepath) {
                $control = true;

                return filemtime($templatepath);
            }, 3600);
        }

        if ($control) {
            $newtime = filemtime($templatepath);
            if ($newtime != $valuestime) {
                Utils::apc_set($cachename, function () use ($newtime) {
                    return $newtime;
                }, 3600);
            }
        }

        if (!($ret = Utils::apc_get($cachename))) {
            $ret = Utils::apc_set($cachename, function () use ($templatepath) {
                if (!($txt = file_get_contents($templatepath))) {
                    return [];
                }
                // thanks for the following regexp to 1franck (http://it2.php.net/manual/en/function.json-decode.php#111551)
                $ret = json_decode(preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#', '', $txt), true);
                if (!is_array($ret)) {
                    $ret = array('js' => [], 'css' => [], 'langs' => []);
                }

                return $ret;
            }, 3600);
        }

        if ($page != null) {
            foreach ($ret as $pid => &$ff) {
                foreach ($ff as $id => &$val) {
                    if (($id != $page) && ($id != 'default')) {
                        unset($ret[$pid][$id]);
                    }
                }
            }
        }

        if (count($ret) < 3) {
            if (isset($ret['js'])) {
                $ret['css'] = [];
            } elseif (isset($ret['css'])) {
                $ret['js'] = [];
            } elseif (isset($ret['langs'])) {
                $ret['langs'] = [];
            } else {
                $ret['js'] = $ret['css'] = $ret['langs'] = [];
            }
        }

        //control for css/js file modification and substitution of %lang% with selected language
        foreach ($ret as $id => &$arr) {
            if ($id != 'langs') {
                $workArr = $arr;

                foreach ($workArr as $nestedID => &$path) {
                    if (is_array($path)) {
                        //Now is possible to use array to include more than 1 file (eg "default": ["js/default.js", "http://cdn.jslibrary", "js/otherdefile.js"]

                        foreach ($path as &$value) {
                            if (!is_array($value)) {
                                $this->validatePath($value, $id);
                                if (isset($value)) {
                                    $ret[$id][] = $value;
                                }
                            } else {
                                if (isset($ret[$id]['staticData'])) {
                                    $ret[$id]['staticData'] = array_merge_recursive($ret[$id]['staticData'], $value);
                                } else {
                                    $ret[$id]['staticData'] = $value;
                                }
                            }
                        }
                        unset($ret[$id][$nestedID]);
                    } else {
                        $this->validatePath($path, $id);
                        if (isset($path)) {
                            $ret[$id][$nestedID] = $path;
                        }
                    }
                }
                // this checks if there is $ret[$id]['staticData'] AND if we are not being
                // called from the lang() function. this IS necessary because if the language cache
                // is being rebuilt by calling getTemplateVars this is going to cause an infinite loop
                // and will cause explosions. To get the calling function I used debug_backtrace
                // which returns the latest callers of any function. This should NOT be slow
                // since it is called once per section and ONLY when there is a staticData section.
                // Practically this is called ONE time on each invocation.
                if (isset($ret[$id]['staticData']['lang']) && is_array($ret[$id]['staticData']['lang'])) {
                    $Dbg = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    if (isset($Dbg[1]['function']) && $Dbg[1]['function'] === 'lang') {
                        continue;
                    }
                    foreach ($ret[$id]['staticData']['lang'] as $key => $entry) {
                        if (!is_numeric($key)) {
                            continue;
                        }
                        $ret[$id]['staticData']['lang'][$entry] = $this->user->lang($entry);
                        unset($ret[$id]['staticData']['lang'][$key]);
                    }
                }
                //print_r($ret[$id]); // * debug *
            } else { //id == langs
                foreach ($arr as &$langFile) {
                    $langFile = str_replace('%lang%', $this->lang, $langFile);
                }
            }
        }

        // move 'default' (numeric) keys in the top of the array
        $sortFunc = function ($a, $b) {
            if (is_string($a) && is_string($b)) {
                return 0;
            }
            if (is_int($a) && is_string($b)) {
                return -1;
            }
            if (is_string($a) && is_int($b)) {
                return 1;
            }

            return $a > $b;
        };

        uksort($ret['js'], $sortFunc);
        uksort($ret['css'], $sortFunc);

        return $ret;
    }

    private function validatePath(&$path, $id)
    {
        if (!Utils::isValidURL($path)) {
            $userfile = "{$_SERVER['DOCUMENT_ROOT']}/tpl/{$this->tpl_no}/{$path}";
            if (!file_exists($userfile)) {
                unset($path);

                return;
            }
            if (!Config\MINIFICATION_ENABLED) {
                $path .= '?'.filemtime($userfile); //force cache refresh if file is changed
                return;
            }

            $userfiletime = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.md5($path).$this->tpl_no.$id;
            $ext = "min.{$id}";

            $updateTime = 0;

            if (!file_exists($userfiletime) || // intval won't get called if the file doesn't exist
                intval(file_get_contents($userfiletime)) < ($updateTime = filemtime($userfile))) {
                Minification::minifyTemplateFile($userfiletime, $userfile, $userfile.$ext, $id);
                $updateTime = time();
            }

            $path .= $ext."?$updateTime"; // append min.ext to file path, and add ?time, to force cache refresh if file is changed
        }
    }
}
