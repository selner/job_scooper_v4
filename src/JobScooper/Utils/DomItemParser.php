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
	static function getTagValue($nodeData, $tagInfo, $itemData=null, $callbackObject=null)
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
	static function getSelector($tagInfo)
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
		if(array_key_exists($key, $data))
			return $data[$key];

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
		if(!empty($itemData) && is_array($itemData))
			$this->_itemData = $itemData;
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
		if(!(is_a($domNodeData, ExtendedDiDomElement::class) ||
			is_a($domNodeData, SimpleHtmlHelper::class)))
		{
			try
			{
				$content = strval($domNodeData);
				$this->_domNodeData = new ExtendedDiDomElement($content);
			}
			catch (Exception $ex)
			{
				throw new Exception('Unable to get DomElement from passed node data.');
			}
		}

		$this->_domNodeData = $domNodeData;
	}

	/**
	 * Constructor
	 * @throws \Exception
	 */
	function __construct($domNode=null, $parseInfo=array(), $itemData=array(), $callbackObject=null)
	{
		if (!empty($parseInfo))
			$this->setTagParserDetails($parseInfo);

		if (!empty($domNode))
			$this->setDomNode($domNode);

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
		if ($this->_tagParseInfo == null) return null;

		$parseKeys = array_keys($this->_tagParseInfo);
		if (!(in_array('selector', $parseKeys) || in_array('tag', $parseKeys))) {
			throw (new \Exception('Invalid tag configuration ' . getArrayValuesAsString($this->_tagParseInfo)));
		}
		$strMatch = '';

		if (array_key_exists('selector', $this->_tagParseInfo)) {
			$strMatch = $strMatch . $this->_tagParseInfo['selector'];
		} elseif(array_key_exists('tag', $this->_tagParseInfo)) {
			if (strlen($strMatch) > 0) $strMatch = $strMatch . ' ';
			{
				$strMatch = $strMatch . $this->_tagParseInfo['tag'];
				if (array_key_exists('attribute', $this->_tagParseInfo) && strlen($this->_tagParseInfo['attribute']) > 0) {
					$strMatch = $strMatch . '[' . $this->_tagParseInfo['attribute'];
					if (array_key_exists('attribute_value', $this->_tagParseInfo) && strlen($this->_tagParseInfo['attribute_value']) > 0) {
						$strMatch = $strMatch . '="' . $this->_tagParseInfo['attribute_value'] . '"';
					}
					$strMatch = $strMatch . ']';
				}
			}
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

		if(!is_array($this->_tagParseInfo) || count($this->_tagParseInfo) == 0 ||
			$this->_domNodeData == null)
			return null;

		if (!array_key_exists('type', $this->_tagParseInfo) || empty($this->_tagParseInfo['type'])) {
			$this->_tagParseInfo['type'] = 'CSS';
		}

		switch(strtoupper($this->_tagParseInfo['type']))
		{
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
				throw new \Exception('Unknown field definition type of ' . $this->_tagParseInfo['type']);
		}

		if (null !== $ret) {
			if(array_key_exists('return_value_callback', $this->_tagParseInfo) && (strlen($this->_tagParseInfo['return_value_callback']) > 0)) {
				if (null !== $this->_callbackObject) {
					$callback = array($this->_callbackObject, $this->_tagParseInfo['return_value_callback']);
				} else {
					$callback = $this->_tagParseInfo['return_value_callback'];
				}
				
				$params = array('current_value' => $ret);
				if (array_key_exists('callback_parameter', $this->_tagParseInfo) && null !== $this->_tagParseInfo['callback_parameter']) {
					$params['parameter'] = $this->_tagParseInfo['callback_parameter'];
				}
				
				$ret = call_user_func_array($callback, $params);
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
		if (array_key_exists('value', $arrTag) && null !== $arrTag['value']) {
			$value  = $arrTag['value'];

			if(null === $value)
				$ret = null;
			else
				$ret = $value;
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
		if (array_key_exists('return_value_regex', $arrTag) && !empty($arrTag['return_value_regex']))
			$arrTag['pattern'] = $arrTag['return_value_regex'];

		if (array_key_exists('pattern', $arrTag) && !empty($arrTag['pattern'])) {
			$pattern = $arrTag['pattern'];
			$value = '';

			if (array_key_exists('field', $arrTag) && !empty($arrTag['field'])) {
				$value = $this->getItemDataValue($arrTag['field']);
			}

			if(null === $value)
				$ret = null;
			else
			{
				$newPattern = str_replace('\\\\', '\\', $pattern);

				if (preg_match($newPattern, $value, $matches) > 0) {
					array_shift($matches);
					$ret = $this->_getReturnValueByIndex($matches, $arrTag['index']);
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
		if(null === $index)
			return null;

		return $arr[$index];
	}

	/**
	 * @param $arr
	 * @param $indexValue
	 *
	 * @return null
	 */
	function translateTagIndexValue($arr, $indexValue)
	{
		switch($indexValue)
		{
			case null:
				$ret = 0;
				break;

			case 'LAST':
				$ret = count($arr) - 1;
				break;

			case $indexValue < 0:
				$ret = count($arr) - 1 - abs($indexValue);
				break;

			case $indexValue > count($arr):
				$strError = sprintf('Failed to find index #%d in the %d matching nodes. ', $indexValue, count($arr));
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

		if (!empty($arrTag['return_attribute']))
			$returnAttribute = $arrTag['return_attribute'];
		else
			$returnAttribute = 'text';

		if (array_key_exists('return_value_regex', $arrTag)) {
			$propertyRegEx = $arrTag['return_value_regex'];
		}
		elseif (array_key_exists('pattern', $arrTag)) {
			$propertyRegEx = $arrTag['pattern'];
		}

		$strMatch = $this->getTagSelector();
		if (null === $strMatch) {
			return $ret;
		}
		elseif(strlen($strMatch) > 0)
		{
			$nodeMatches = $node->find($strMatch, $searchType);

			if ($returnAttribute === 'collection') {
				$ret = $nodeMatches;
				// do nothing.  We already have the node set correctly
			} elseif (!empty($nodeMatches) && array_key_exists('index', $arrTag) && is_array($nodeMatches)) {
				$index = (int)$arrTag['index'];
				if ( $index > count($nodeMatches) - 1) {
					$this->log("Tag specified index {$index} but only " . count($nodeMatches) . " were matched.  Defaulting to first node.", LogLevel::WARNING);
					$index = 0;
				} elseif(empty($index) && $index !== 0)
				{
					$this->log("Tag specified index value was invalid {$arrTag['index']}.  Defaulting to first node.", LogLevel::WARNING);
					$index = 0;
				}
				$ret = $this->_getReturnValueByIndex($nodeMatches, $index);
			} elseif (!empty($nodeMatches) && is_array($nodeMatches)) {
				if (count($nodeMatches) > 1) {
					$strError = sprintf('Warning:  %s plugin matched %d nodes to selector \'%s\' but did not specify an index.  Assuming first node.  Tag = %s', $this->JobSiteName, count($nodeMatches), $strMatch, getArrayDebugOutput($arrTag));
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
					if (preg_match($propertyRegEx, $retTemp, $match) !== false && count($match) >= 1)
					{
						$ret = $match[1];
					}
					else
						$this->log(sprintf('%s plugin failed to find match for regex \'%s\' for tag \'%s\' with value \'%s\' as expected.', $this->JobSiteName, $propertyRegEx, getArrayDebugOutput($arrTag), $ret), LogLevel::DEBUG);
				}
			}
		}
		else
		{
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
	function log($msg, $logLevel=\Monolog\Logger::INFO, $extras=array(), $ex=null)
	{
		LogMessage($msg, $logLevel, $extras, $ex, $channel='DomElementParser');
	}

}
