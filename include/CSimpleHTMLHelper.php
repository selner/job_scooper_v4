<?php
/**
 * Copyright 2014 Bryan Selner
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
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');

const C__SIMPLEHTML_THROWEXCEPTION = 0x1;
const C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR = 0x2;
const C__SIMPLEHTML_NOTFOUND_RETURN_NULL = 0x4;
const C__SIMPLEHTML_FOUND_RETURN_PLAINTEXT = 0x8;
const C__SIMPLEHTML_FOUND_RETURN_PROPERTY = 0x10;
const C__SIMPLEHTML_FOUND_RETURN_ATTRIB = 0x20;
const C__SIMPLEHTML_FOUND_RETURN_NODE = 0x40;
const C__SIMPLEHTML_FOUND_RETURN_ALLCHILDREN = 0x80;

class CSimpleHTMLHelper
{
    private $nodeObj = null;

    function __construct($nodeObject)
    {
        if(!$nodeObject) throw new ErrorException("Required simple_html_dom_node or simple_html_dom object was not set.");
        $this->nodeObj = $nodeObject;
    }

    function get($strNodePath, $retIndex, $fRequired = true)
    {
        $flags = C__SIMPLEHTML_FOUND_RETURN_NODE;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;
        return $this->getNodeValue($strNodePath, $retIndex, $flags);
    }

    function getText($strNodePath, $retIndex, $fRequired = false, $optPropOrAttrName = null)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_PLAINTEXT;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags, "plaintext", $optPropOrAttrName);
    }

    function getAllChildrenText($strNodePath, $retIndex, $fRequired = false)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_ALLCHILDREN;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags);
    }

    function getProperty($strNodePath, $retIndex, $optPropOrAttrName, $fRequired = false)
    {
        $flags = C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR | C__SIMPLEHTML_FOUND_RETURN_PROPERTY;
        if($fRequired == true)  $flags = $flags | C__SIMPLEHTML_THROWEXCEPTION ;

        return $this->getNodeValue($strNodePath, $retIndex, $flags, $optPropOrAttrName);
    }

    function getNodeValue($strNodePath, $retIndex, $flags, $optPropOrAttrName = null)
    {
        $ret = null;
        if(\Scooper\isBitFlagSet($flags,C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR ))
        {
            $ret = "";
        }

        try
        {
            if(!isset($retIndex) || !isset($strNodePath))
            {
                throw new ErrorException("Failed to set required object, node path and node index.");
            }

            $subNode = $this->nodeObj->find($strNodePath);
            if(!isset($subNode))
            {
                throw new ErrorException("Failed to find expected node path: " & $strNodePath);
            }

            if(isset($retIndex) && !isset($subNode[$retIndex]))
            {
                throw new ErrorException("Node path (" . $strNodePath .")[" . $retIndex . "] was not found.");
            }

            if(isVerbose())
            {
                print ("Node path(" . $strNodePath .")[index=".$retIndex."] => " .PHP_EOL);
                \SimpleHtmlDom\dump_html_tree($subNode[$retIndex]);
                print ("<= end " .PHP_EOL);
            }

            if(\Scooper\isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_NODE ))
            {
                $ret = $subNode[$retIndex];
            }
            elseif(\Scooper\isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_PROPERTY ))
            {
                if(!isset($optPropOrAttrName) || !isset($subNode[$retIndex]->$optPropOrAttrName))
                {
                    throw new ErrorException("Property '" . $optPropOrAttrName . "' for node (" . $strNodePath .")[" . $retIndex . "] was not found.");
                }
                else
                {
                    $ret = $subNode[$retIndex]->$optPropOrAttrName;
                }
            }
            elseif(\Scooper\isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_ATTRIB ))
            {
                if(!isset($optPropOrAttrName) || !isset($subNode[$retIndex]->attr[$optPropOrAttrName]))
                {
                    throw new ErrorException("Attribute '" . $optPropOrAttrName . "' for node (" . $strNodePath .")[" . $retIndex . "] was not found.");
                }
                else
                {
                    $ret = $subNode[$retIndex]->attr[$optPropOrAttrName];
                }
            }
            elseif(\Scooper\isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_ALLCHILDREN))
            {
                $ret = combineTextAllChildren($subNode[$retIndex], true);
                if(!isset($ret) && \Scooper\isBitFlagSet($flags, C__SIMPLEHTML_NOTFOUND_RETURN_EMPTYSTR ))
                {
                    $ret = "";
                }
            }
            elseif(\Scooper\isBitFlagSet($flags, C__SIMPLEHTML_FOUND_RETURN_PLAINTEXT ))
            {
                if(!isset($subNode[$retIndex]->plaintext))
                {
                    throw new ErrorException("Plaintext value for node (" . $strNodePath .")[" . $retIndex . "] was not found.");
                }
                else
                {
                    $ret = $subNode[$retIndex]->plaintext;
                }
            }

        } catch (Exception $ex) {
            $strErr = $ex->getMessage();
            if(\Scooper\isBitFlagSet($flags, C__SIMPLEHTML_THROWEXCEPTION ))
            {
                $GLOBALS['logger']->logLine("Error getting SimpleObjectHTML node:  " . $strErr, \Scooper\C__DISPLAY_ERROR__);
                throw $ex;
            }
            else
            {
                if(isDebug()) $GLOBALS['logger']->logLine("" . $strErr, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            }
        }

        return $ret;
    }
}