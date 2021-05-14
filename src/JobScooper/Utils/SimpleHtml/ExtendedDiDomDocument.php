<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the 'License'); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace JobScooper\Utils\SimpleHtml;

use DiDom\Document;
use DiDom\Element;
use DiDom\Query;

/**
 * Class ExtendedDiDomDocument
 * @package JobScooper\Utils
 */
class ExtendedDiDomDocument extends Document
{
    /**
     * @var
     */
    protected $_sourceUrl;

    /**
     * @param $name
     *
     * @return string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'text':
                return $this->text();
                break;
            default:
                return parent::getElement()[$name];
        }
    }

    /**
     * @param $strUrl
     */
    public function setSource($strUrl)
    {
        $this->_sourceUrl = $strUrl;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->_sourceUrl;
    }

    /**
     * @param $xpath
     *
     * @return array|\DiDom\Element[]|\DOMElement[]|\JobScooper\Utils\SimpleHtml\ExtendedDiDomElement
     * @throws \Exception
     */
    public function findByXpath($xpath)
    {
        return $this->find($xpath, Query::TYPE_XPATH);
    }

    function first($expression, $type = Query::TYPE_CSS, $wrapNode = true, $contextNode = null)
    {
        try {
            $ret = parent::find($expression, $type, $wrapNode, $contextNode);
            if($ret != null) {
                if (count($ret) > 0) {
                    return $ret[0];
                }
                else {
                    return $ret;
                }
            }
        } catch (\Exception $ex) {
            $this->debug_dump_to_file();
            throw $ex;
        }
        return null;
    }

    /**
     * @param string $expression
     * @param string $type
     * @param bool $wrapNode
     * @param null $contextNode
     *
     * @return array|\DiDom\Element[]|\DOMElement[]|\JobScooper\Utils\SimpleHtml\ExtendedDiDomElement
     * @throws \Exception
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapNode = true, $contextNode = null)
    {
        try {
            $ret = parent::find($expression, $type, $wrapNode, $contextNode);
            if (is_array($ret)) {
                $retExt = array();
                foreach ($ret as $elem) {
                    $foundNode = new ExtendedDiDomElement($elem->getNode());
                    if ($foundNode->isVisible()) {
                        $retExt[] = $foundNode;
                    }
                }
                return $retExt;
            } elseif (is_a($ret, 'Element', true)) {
                $foundNode = new ExtendedDiDomElement($ret->getNode());
                if ($foundNode->isVisible()) {
                    return $foundNode;
                }
            }
            throw new \Exception('Invalid return type from ExtendedDiDomDocument->Find');
        } catch (\Exception $ex) {
            $this->debug_dump_to_file();
            throw $ex;
        }
    }

    /**
     * @return string
     */
    public function debug_dump_to_file()
    {
        $src = $this->getSource();
        if (empty($src)) {
            $basefile = 'debug_dump_' . uniqid();
        } else {
            $parsed_url = parse_url($src);
            $basefile = preg_replace('/[^\w]/', '_', $parsed_url['host'] . $parsed_url['path']);
        }
        $outfile = generateOutputFileName($basefile, 'html', true, 'debug');
        file_put_contents($outfile, $this->html());
        return $outfile;
    }
}