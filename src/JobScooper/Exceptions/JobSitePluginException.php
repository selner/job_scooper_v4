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

namespace JobScooper\Exceptions;


class JobSitePluginException extends \Exception implements \Throwable
{
    protected $_pluginName = "";

    /**
     * JobSitePluginException constructor.
     * @param string $message
     * @param null $code
     * @param \Throwable|null $previous
     * @param null $plugin
     */
    public function __construct($message = "", $code=null, \Throwable $previous = null, $plugin = null)
    {
        $this->_pluginName = $plugin;
        if(!is_empty_value($this->_pluginName)) {
            $message .= PHP_EOL . "Plugin:  $this->_pluginName";
        }
        parent::__construct(message: $message, code:$code, previous: $previous);
    }
}
