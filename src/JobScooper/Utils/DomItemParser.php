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

namespace JobScooper\Utils;

use DiDom\Query;
use \Exception;
use JobScooper\Utils\ExtendedDiDomElement;
use JobScooper\Utils\SimpleHTMLHelper;
use Psr\Log\LogLevel;

/**
 * Class SimplePlugin
 * @package JobScooper\BasePlugin\Classes
 */
class DomItemParser
{
    private $_tagParseInfo = null;
    private $_domNodeData = null;
    private $_itemData = null;
    private $_callbackObject = null;

    /**
     * @param \JobScooper\Utils\SimpleHTMLHelper|ExtendedDiDomElement $nodeData
     * @param array $tagInfo
     * @param array|null $itemData
     *
     * @return mixed|null|string
     * @throws \Exception
     */
    public static function getTagValue($nodeData, $tagInfo, $itemData=null, $callbackObject=null)
    {
        $parser = new DomItemParser($nodeData, $tagInfo, $itemData, $callbackObject);
        return $parser->getParsedValue();
    }


    /**
     * @param \JobScooper\Utils\SimpleHTMLHelper|ExtendedDiDomElement $nodeData
     * @param array $tagInfo
     * @param array|null $itemData
     *
     * @return mixed|null|string
     * @throws \Exception
     */
    public static function getSelector($tagInfo)
    {
        $parser = new DomItemParser(null, $tagInfo, null);
        return $parser->getTagSelector();
    }


    /**
     * @param $key
     *
     * @return null
     */
    public function getItemDataValue($key)
    {
        $data = $this->getItemData();
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return null;
    }

    /**
     * @return null
     */
    public function getItemData()
    {
        return $this->_itemData;
    }

    /**
     * @param array|null $itemData
     */
    public function setItemData($itemData)
    {
        if (!empty($itemData) && is_array($itemData)) {
            $this->_itemData = $itemData;
        }
    }

    /**
     * @return null
     */
    public function getDomNode()
    {
        return $this->_domNodeData;
    }

    /**
     * @param ExtendedDiDomElement|SimpleHTMLHelper|null $domNodeData
     * @throws \Exception
     */
    public function setDomNode($domNodeData)
    {
        if (!(is_a($domNodeData, ExtendedDiDomElement::class) ||
            is_a($domNodeData, SimpleHTMLHelper::class))) {
            try {
                $content = (string) $domNodeData;
                $this->_domNodeData = new ExtendedDiDomElement($content);
            } catch (Exception $ex) {
                throw new Exception('Unable to get DomElement from passed node data.');
            }
        }

        $this->_domNodeData = $domNodeData;
    }

    /**
     * Constructor
     * @throws \Exception
     */
    public function __construct($domNode=null, $parseInfo=array(), $itemData=array(), $callbackObject=null)
    {
        if (!empty($parseInfo)) {
            $this->setTagParserDetails($parseInfo);
        }

        if (!empty($domNode)) {
            $this->setDomNode($domNode);
        }

        $this->_callbackObject = $callbackObject;

        $this->setItemData($itemData);
    }

    /**
     * @return array|null
     */
    public function getTagParserDetails()
    {
        return $this->_tagParseInfo;
    }

    /**
     * @param array|null $tagParseInfo
     */
    public function setTagParserDetails($tagParseInfo)
    {
        $this->_tagParseInfo = $tagParseInfo;
    }


    /**
     * @param array $arrTag
     *
     * @return null|string
     * @throws \Exception
     */
    public function getTagSelector()
    {
        if ($this->_tagParseInfo == null) {
            return null;
        }

        $parseKeys = array_keys($this->_tagParseInfo);
        if (!in_array('Selector', $parseKeys)) {
            throw (new \Exception('Invalid tag configuration ' . getArrayValuesAsString($this->_tagParseInfo)));
        }
        $strMatch = '';

        if (array_key_exists('Selector', $this->_tagParseInfo)) {
            $strMatch = $strMatch . $this->_tagParseInfo['Selector'];
        }

        return $strMatch;
    }


    /**
     * @return mixed|null|string
     * @throws \Exception
     */
    public function getParsedValue()
    {
        $ret = null;

        if (!is_array($this->_tagParseInfo) || \count($this->_tagParseInfo) == 0 ||
            $this->_domNodeData == null) {
            return null;
        }

        if (!array_key_exists('Type', $this->_tagParseInfo) || empty($this->_tagParseInfo['Type'])) {
            $this->_tagParseInfo['Type'] = 'CSS';
        }

        switch (strtoupper($this->_tagParseInfo['Type'])) {
            case 'CSS':
                $ret = $this->_getTagMatchValueViaFind_($this->_domNodeData, Query::TYPE_CSS);
                break;

            case 'XPATH':
                $ret = $this->_getTagMatchValueViaFind_($this->_domNodeData, Query::TYPE_XPATH);
                break;

            case 'STATIC':
                return $this->_getTagMatchValueStatic_($this->_tagParseInfo);
                break;

            case 'SOURCEFIELD':
                $ret = $this->_getTagMatchValueSourceField_();
                break;

            case 'MICRODATA':
                // Do nothing; we've already parsed the microdata
                break;

            default:
                throw new \Exception('Unknown field definition type of ' . $this->_tagParseInfo['Type']);
        }

        if (null !== $ret) {
            if (array_key_exists('Callback', $this->_tagParseInfo) && (strlen($this->_tagParseInfo['Callback']) > 0)) {
                if (null !== $this->_callbackObject) {
                    $callback = array($this->_callbackObject, $this->_tagParseInfo['Callback']);
                } else {
                    $callback = $this->_tagParseInfo['Callback'];
                }
                
                $params = array('current_value' => $ret);
                if (array_key_exists('CallbackParameter', $this->_tagParseInfo) && null !== $this->_tagParseInfo['CallbackParameter']) {
                    $params['parameter'] = $this->_tagParseInfo['CallbackParameter'];
                }
                
                $ret = $callback($params);
            }
        }
        return $ret;
    }

    /**
     * @param $arrTag
     *
     * @return null
     */
    protected function _getTagMatchValueStatic_($arrTag)
    {
        $ret = null;
        if (array_key_exists('Value', $arrTag) && null !== $arrTag['Value']) {
            $value  = $arrTag['Value'];

            if (is_empty_value($value)) {
                $ret = null;
            } else {
                $ret = $value;
            }
        }

        return $ret;
    }

    /**
     * @param $arrTag
     * @param $item
     *
     * @return null
     */
    protected function _getTagMatchValueSourceField_()
    {
        $arrTag = $this->getTagParserDetails();

        $ret = null;

        if (array_key_exists('Pattern', $arrTag) && !empty($arrTag['Pattern'])) {
            $pattern = $arrTag['Pattern'];
            $value = '';

            if (array_key_exists('Field', $arrTag) && !empty($arrTag['Field'])) {
                $value = $this->getItemDataValue($arrTag['Field']);
            }

            if (is_empty_value($value)) {
                $ret = null;
            } else {
                $newPattern = str_replace('\\\\', '\\', $pattern);

                if (preg_match($newPattern, $value, $matches) > 0) {
                    array_shift($matches);
                    $ret = $this->_getReturnValueByIndex($matches, $arrTag['Index']);
                }
            }
        }

        return $ret;
    }

    /**
     * @param $arr
     * @param $indexValue
     *
     * @return null
     */
    private function _getReturnValueByIndex($arr, $indexValue)
    {
        $index = $this->translateTagIndexValue($arr, $indexValue);
        if (is_empty_value($index)) {
            return null;
        }

        return $arr[$index];
    }

    /**
     * @param $arr
     * @param $indexValue
     *
     * @return null
     */
    public function translateTagIndexValue($arr, $indexValue)
    {
        switch ($indexValue) {
            case null:
                $ret = 0;
                break;

            case 'LAST':
                $ret = \count($arr) - 1;
                break;

            case $indexValue < 0:
                $ret = \count($arr) - 1 - abs($indexValue);
                break;

            case $indexValue > \count($arr):
                $strError = sprintf('Failed to find index #%d in the %d matching nodes. ', $indexValue, \count($arr));
                $this->log($strError, LogLevel::WARNING);
                $ret = null;
                break;

            default:
                $ret = $indexValue;
                break;
        }

        return $ret;
    }


    /**
     * @param        $node
     * @param        $arrTag
     * @param string $searchType
     *
     * @return mixed|null|string
     * @throws \Exception
     */
    protected function _getTagMatchValueViaFind_($node, $searchType=Query::TYPE_CSS)
    {
        $ret = null;
        $propertyRegEx = null;
        $arrTag = $this->getTagParserDetails();

        if (!empty($arrTag['Attribute'])) {
            $returnAttribute = $arrTag['Attribute'];
        } else {
            $returnAttribute = 'text';
        }

        if (array_key_exists('Pattern', $arrTag)) {
            $propertyRegEx = $arrTag['Pattern'];
        }

        $strMatch = $this->getTagSelector();
        if (is_empty_value($strMatch)) {
            return $ret;
        } elseif (strlen($strMatch) > 0) {
            $nodeMatches = $node->find($strMatch, $searchType);

            if ($returnAttribute === 'collection') {
                $ret = $nodeMatches;
            // do nothing.  We already have the node set correctly
            } elseif (!empty($nodeMatches) && array_key_exists('Index', $arrTag) && is_array($nodeMatches)) {
                $index = (int)$arrTag['Index'];
                if ($index > \count($nodeMatches) - 1) {
                    $this->log("Tag specified index {$index} but only " . \count($nodeMatches) . " were matched.  Defaulting to first node.", LogLevel::WARNING);
                    $index = 0;
                } elseif (empty($index) && $index !== 0) {
                    $this->log("Tag specified index value was invalid {$arrTag['Index']}.  Defaulting to first node.", LogLevel::WARNING);
                    $index = 0;
                }
                $ret = $this->_getReturnValueByIndex($nodeMatches, $index);
            } elseif (!empty($nodeMatches) && is_array($nodeMatches)) {
                if (count($nodeMatches) > 1) {
                    $strError = sprintf('Warning: ' . self::class . ' matched %d nodes to selector \'%s\' but did not specify an index.  Assuming first node.  Tag = %s', \count($nodeMatches), $strMatch, getArrayDebugOutput($arrTag));
                    $this->log($strError, LogLevel::WARNING);
                }
                $ret = $nodeMatches[0];
            }

            if (!empty($ret) && !in_array($returnAttribute, ['collection', 'node'])) {
                $ret = $ret->$returnAttribute;


                if (null !== $propertyRegEx && is_string($ret) && strlen($ret) > 0) {
                    $match = array();
                    $propertyRegEx = str_replace("\\\\", "\\", $propertyRegEx);
                    $retTemp = str_replace('\n', ' ', $ret);
                    if (preg_match($propertyRegEx, $retTemp, $match) !== false && \count($match) >= 1) {
                        $ret = $match[1];
                    } else {
                        $this->log(sprintf(self::class . ' failed to find match for regex \'%s\' for tag \'%s\' with value \'%s\' as expected.', $propertyRegEx, getArrayDebugOutput($arrTag), $ret), LogLevel::DEBUG);
                    }
                }
            }
        } else {
            $ret = $strMatch;
        }

        return $ret;
    }

    /**
     * @param $msg
     * @param $logLevel
     * @param array $extras
     * @param Exception $ex
     */
    public function log($msg, $logLevel=\Monolog\Logger::INFO, $extras=array(), $ex=null)
    {
        LogMessage($msg, $logLevel, $extras, $ex, $channel='DomElementParser');
    }
}
