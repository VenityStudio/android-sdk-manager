<?php

use php\format\JsonProcessor;
use php\io\Stream;
use php\lib\fs;
use packager\Event;

/**
 * @jppm-task download-tools
 * @jppm-description Download android sdk tools from google repository
 */
function task_download_sdk_tools(Event $e)
{
    $config = (new JsonProcessor(JsonProcessor::DESERIALIZE_AS_ARRAYS))->parse(Stream::getContents("./sdk-tools.json"));

    if (!fs::isDir("./sdk-tools"))
        fs::makeDir("./sdk-tools");

    foreach ($config as $os => $data)
    {
        echo "Downloading sdk tools for {$os} from {$data['url']} \n";

        $file = "./sdk-tools/{$data['name']}";

        if (!fs::isFile($file))
            fs::makeFile($file);

        fs::copy(Stream::of($data['url']), $file);
        echo " -> done\n";
    }
}