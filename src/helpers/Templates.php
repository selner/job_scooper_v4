<?php
/**
 * Copyright 2014-18 Bryan Selner
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


use LightnCandy\LightnCandy;

/**
 * @param $path
 *
 * @throws \Exception
 */
function loadTemplate($path)
{
    LogDebug("Loading Mustache template file {$path}...");
    $tmplFile = realpath($path);
    $tmplDir = dirname($tmplFile);
    $partialsDir = $tmplDir . DIRECTORY_SEPARATOR . "partials/";
    $template = file_get_contents($tmplFile);

    LogDebug("Compiling Mustache template ($tmplFile...");
    $tmpl = LightnCandy::compile(
        $template,
        array(
            'flags' => LightnCandy::FLAG_RENDER_DEBUG | LightnCandy::FLAG_ERROR_LOG | LightnCandy::FLAG_ERROR_EXCEPTION | LightnCandy::FLAG_HANDLEBARSJS_FULL | LightnCandy::FLAG_THIS | LightnCandy::FLAG_PROPERTY | LightnCandy::FLAG_JSOBJECT| LightnCandy::FLAG_ELSE,
            'partialresolver' => function ($cx, $name) use ($partialsDir) {
                $tmplpath = "$partialsDir/$name.tmpl";
                if (file_exists($tmplpath)) {
                    return file_get_contents($tmplpath);
                }
                return "[partial (file:$tmplpath) not found]";
            },
            'prepartial' => function ($context, $template, $name) {
                return "<!-- partial start: $name -->$template<!-- partial end: $name -->";
            }
        )
    );  // set compiled PHP code into $phpStr

    // Save the compiled PHP code into a php file
    $renderFile = generateOutputFileName(basename($path) . '-render', $ext = "php", $includeRunID = true);

    file_put_contents($renderFile, '<?php ' . $tmpl . '?>');

    // Get the render function from the php file
    try {
        $renderer = include $renderFile;
        if ($renderer == false) {
            throw new Exception("Error: unable to compile template '$tmplFile'");
        }
        return $renderer;
    }
    catch (Throwable $t)
    {
        handleThrowable($t, fmtLogMsg: "Unable to get renderer for template $tmpl");
    }
}

function renderTemplate($renderer, $data)
{
    #    return $renderer($data, array('debug' => \LightnCandy\Runtime::DEBUG_TAGS_HTML));
    return $renderer($data);
}
