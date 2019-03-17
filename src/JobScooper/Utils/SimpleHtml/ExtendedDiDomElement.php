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

use DiDom\Element;
use DiDom\Query;

/**
 * Class ExtendedDiDomElement
 * @package JobScooper\Utils
 */
class ExtendedDiDomElement extends Element
{
    /**
     * @param string $name
     *
     * @return null|string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'text':
                return $this->text();
                break;
            case 'sourceUrl':
                return $this->getDocument()->getSource();
                break;
            default:
                return parent::__get($name);
        }
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        $elemVisible = true;
        $style = $this->style;
        if (null !== $style) {
            $parts = explode(";", $style);
            foreach ($parts as $part) {
                $kvPair = explode(':', $part);
                if (!empty($kvPair) && \count($kvPair) > 1) {
                    $key = strtolower($kvPair[0]);
                    $val = strtolower($kvPair[1]);
                    switch ($key) {
                        case 'display':
                            if (substr_count_multi($val, array('collapse', 'none'))) {
                                return false;
                            }
                            break;

                        case 'visibility':
                            if (substr_count_multi($val, array('collapse', 'hidden'))) {
                                return false;
                            }
                            break;
                    }
                }
            }
        }

        if ($elemVisible !== false) {
            if (method_exists($this, 'parent') && null !== $this->parent()) {
                if (method_exists($this->parent(), 'getNode')) {
                    $parent = new ExtendedDiDomElement($this->parent()->getNode());
                    $elemVisible = $parent->isVisible();
                }
            }
        }

        return $elemVisible;
    }

    /**
     * @return \DiDom\Document|\JobScooper\Utils\SimpleHtml\ExtendedDiDomDocument|null
     */
    public function getDocument()
    {
        if ($this->node->ownerDocument === null) {
            return null;
        }

        return new ExtendedDiDomDocument($this->node->ownerDocument);
    }

    /**
     * Get the DOM document with the current element.
     *
     * @param string $encoding The document encoding
     *
     * @return \DiDom\Document
     */
    public function toDocument($encoding = 'UTF-8')
    {
        $document = new ExtendedDiDomDocument(null, false, $encoding);

        $document->appendChild($this->node);

        return $document;
    }

    /**
     * @param        $expression
     * @param string $type
     * @param bool $wrapElement
     *
     * @return array|\DiDom\Element[]|\DOMElement[]|\JobScooper\Utils\SimpleHtml\ExtendedDiDomElement
     * @throws \Exception
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        $ret = parent::find($expression, $type, $wrapElement);
        if (is_array($ret)) {
            $retExt = array();
            foreach ($ret as $elem) {
                $retExt[] = new ExtendedDiDomElement($elem->getNode());
            }
            return $retExt;
        } elseif (is_a($ret, 'Element', true)) {
            return new ExtendedDiDomElement($ret->getNode());
        }
        throw new \Exception('Invalid return type from DiDom->Find');
    }
}