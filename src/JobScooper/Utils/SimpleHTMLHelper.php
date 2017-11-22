<?php
/**
 * Copyright 2014-17 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
namespace JobScooper\Utils;

use Exception;
use ErrorException;
use Sunra\PhpSimple\HtmlDomParser;


const C__SIMPLEHTML_THROWEXCEPTION = 0x1;
const C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR = 0x2;
const C__SIMPLEHTML_NOTFOUND_RETURN_NULL = 0x4;
const C__SIMPLEHTML_FOUND_RETURN_PLAINTEXT = 0x8;
const C__SIMPLEHTML_FOUND_RETURN_PROPERTY = 0x10;
const C__SIMPLEHTML_FOUND_RETURN_ATTRIB = 0x20;
const C__SIMPLEHTML_FOUND_RETURN_NODE = 0x40;
const C__SIMPLEHTML_FOUND_RETURN_ALLCHILDREN = 0x80;


class SimpleHTMLHelper extends HtmlDomParser
{
    private $nodeObj = null;

    function __construct($nodeObject)
    {
        if(!$nodeObject) throw new ErrorException("Required simple_html_dom_node or simple_html_dom object was not set.");
        $this->nodeObj = $nodeObject;
    }

    function get($strNodePath, $retIndex = null, $fRequired = true)
    {
        $flags = C__SIMPLEHTML_FOUND_RETURN_NODE;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;
        return $this->getNodeValue($strNodePath, $retIndex, $flags);
    }

    function getText($strNodePath, $retIndex, $fRequired = false, $optPropOrAttrName = null)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_PLAINTEXT;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags, "plaintext");
    }

    function getAllChildrenText($strNodePath, $retIndex, $fRequired = false)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_ALLCHILDREN;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags);
    }


    // dump html dom tree
    static function dump_html_tree($node, $show_attr=true, $deep=0)
    {
        $node->dump($node);
    }


    function getProperty($strNodePath, $retIndex, $optPropOrAttrName, $fRequired = false)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_PROPERTY;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags, $optPropOrAttrName);
    }

    function getAttribute($strNodePath, $retIndex, $optPropOrAttrName, $fRequired = false)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_ATTRIB;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags, $optPropOrAttrName);
    }

    function getNodeValue($strNodePath, $retIndex, $flags, $optPropOrAttrName = null)
    {
        $ret = null;
        if(isBitFlagSet($flags,C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR ))
        {
            $ret = "";
        }

        try
        {
            if(isset($strNodePath) && strlen($strNodePath) > 0)
            {
                $subNode = $this->nodeObj->find($strNodePath);
            }
            else
            {
                $subNode = $this->nodeObj;
            }

            if(!isset($subNode))
            {
                throw new ErrorException("Failed to find expected node path: " . $strNodePath);
            }



            if(isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_NODE ))
            {
                $ret = $subNode;
                if(isset($retIndex) && isset($subNode[$retIndex]))
                {
                    $ret = $subNode[$retIndex];
                }
            }
            else
            {
                if(isset($retIndex) && isset($subNode[$retIndex]))
                {
                    $subNodeElement = $subNode[$retIndex];
                }
                elseif(isset($retIndex) && !isset($subNode[$retIndex]))
                {
                    throw new ErrorException("Node element (" . $strNodePath .")[" . $retIndex . "] was not found.");
                }
                else
                {
                    $subNodeElement = $subNode;
                }
                if(isVerbose())
                {
                    print ("Node path(" . (isset($strNodePath)?$strNodePath :"null").")[index=".(isset($retIndex)?$retIndex:"null")."] => " .PHP_EOL);
                    $this->dump_html_tree($subNodeElement);
                    print ("<= end " .PHP_EOL);
                }

                if(isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_PROPERTY ))
                {
                    if(!isset($optPropOrAttrName) || !isset($subNodeElement->$optPropOrAttrName))
                    {
                        throw new ErrorException("Property '" . $optPropOrAttrName . "' for node (" . $strNodePath .")[" . $retIndex . "] was not found.");
                    }
                    else
                    {
                        $ret = $subNodeElement->$optPropOrAttrName;
                    }
                }
                elseif(isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_ATTRIB ))
                {
                    if(!isset($optPropOrAttrName) || !isset($subNodeElement->attr[$optPropOrAttrName]))
                    {
                        throw new ErrorException("Attribute '" . $optPropOrAttrName . "' for node (" . $strNodePath .")[" . $retIndex . "] was not found.");
                    }
                    else
                    {
                        $ret = $subNodeElement->attr[$optPropOrAttrName];
                    }
                }
                elseif(isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_ALLCHILDREN))
                {
                    $ret = combineTextAllChildren($subNodeElement, false);
                    if(!isset($ret) && isBitFlagSet($flags, C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR ))
                    {
                        $ret = "";
                    }
                }
                elseif(isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_PLAINTEXT ))
                {
                    if(!isset($subNodeElement->plaintext))
                    {
                        throw new ErrorException("Plaintext value for node (" . $strNodePath .")[" . $retIndex . "] was not found.");
                    }
                    else
                    {
                        $ret = $subNodeElement->plaintext;
                    }
                }
            }
        } catch (Exception $ex) {
            $strErr = $ex->getMessage();
            if(isBitFlagSet($flags, C__SIMPLEHTML_THROWEXCEPTION ))
            {
                $GLOBALS['logger']->logLine("Error getting SimpleObjectHTML node:  " . $strErr, \C__DISPLAY_ERROR__);
                throw $ex;
            }
            else
            {
                if(isDebug()) $GLOBALS['logger']->logLine("" . $strErr, \C__DISPLAY_ITEM_DETAIL__);
            }
        }

        return $ret;
    }
}