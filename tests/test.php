<?php
    declare(strict_types=1);

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../src/Argument.php");
    require_once(__DIR__ . "/../src/Arguments.php");

    use pctlib\cliarguments\Argument;
    use pctlib\cliarguments\Arguments;

    $arguments = [
        "FILESCANNER" => [
            "scanPath"          => new Argument("scanPath", "The directory to scan.", "", Argument::REQUIRED + Argument::EXISTING_PATH),
            "cachePath"         => new Argument("cachePath", "The directory to write the runtime cache files.", "./cache/", Argument::REQUIRED + Argument::EXISTING_PATH),
            "logPath"           => new Argument("logPath", "The directory to write the mscan.log file.", "/var/log/", Argument::REQUIRED + Argument::EXISTING_PATH),
            "findPath"          => new Argument("findPath", "System path to find. (default: which find)", null),
            "diffPath"          => new Argument("diffPath", "System path to diff. (default: which diff)", null),
            "shellPath"         => new Argument("shellPath", "System path to shell. (default: which bash)", null),
            "editorPath"        => new Argument("editorPath", "System path to editor.  (default: which vi)", null),
            "updateCacheFreq"   => new Argument("updateCacheFreq", "How often in seconds to update the find files and files info cache. -1 for no update. ", 0, Argument::REQUIRED + Argument::INT),
            "maxHistoryRecords" => new Argument("maxHistoryRecords", "The maximum number of files info Runtime history to keep.", 3, Argument::REQUIRED + Argument::INT),
            "maxScanFileSize"   => new Argument("maxScanFileSize", "The maximum file size to scan in bytes.", 32*1024*1024, Argument::REQUIRED + Argument::INT),
            "include"           => new Argument("include", "Include file/folder wildcard path.  Include is processed before exclude.", []),
            "exclude"           => new Argument("exclude", "Exclude file/folder wildcard path.", []),
            "excludeMedia"      => new Argument("excludeMedia", "Exclude media type files. *.mp2 *.mp3, *.mp4, *.wav, *.wmv, *.mov, *.ogg, *.webm, *.mpeg", false),
            "loadModule"        => new Argument("loadModule", "Path to scanning module file.", [], Argument::EXISTING_FILE),
            "background"        => new Argument("background", "Run in background with no output or user input.", false)
        ]
    ];
    

    if (!is_array(($results = Arguments::ProcessArguments($arguments))))
        die("\nUsage: test.php [OPTIONS]\n" . Arguments::Usage($arguments, $results, 125) . "\n");

    print_r($results);
?>