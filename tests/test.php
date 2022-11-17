<?php
    declare(strict_types=1);

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/../src/Argument.php");
    require_once(__DIR__ . "/../src/Arguments.php");
    require_once(__DIR__ . "/../src/ArgumentableInterface.php");

    use pctlib\cliarguments\Argument;
    use pctlib\cliarguments\Arguments;
    use pctlib\cliarguments\ArgumentableInterface;

    class FileScannerModule implements ArgumentableInterface {
        protected $parameters = [];

        public function GetCLIArguments(): array {
            return [
                "scanPath2" => new Argument("scanPath2", "The directory to scan.", "", Argument::NEW_PATH)
            ];
        }

        public function SetParameters(array $parameters) : bool {
            $this->parameters = $parameters;
            return true;
        }
    }

    class FileScannerRuntimeIO implements ArgumentableInterface {
        protected $parameters = [];
        public function GetCLIArguments(): array {
            return [
                "runtimePath" => new Argument("runtimePath", "The directory to write the runtime files.", "./runtime/", Argument::REQUIRED + Argument::EXISTING_PATH)
            ];
        }

        public function SetParameters(array $parameters) : bool {
            $this->parameters = $parameters;
            return true;
        }
    }

    class FileScanner implements ArgumentableInterface {
        protected $parameters = [];

        protected array $modules = [];
        protected $fileScannerRuntimeIOInterface;

        public function __construct() {
            $this->fileScannerRuntimeIOInterface = new FileScannerRuntimeIO();

            $newModule = new FileScannerModule();

            $this->modules[get_class($newModule)] = $newModule;
        }

        public function SetParameters(array $parameters) : bool {
            $this->parameters = $parameters;
            return true;
        }

        public function GetCLIArguments(): array { 
            return [
                "scanPath"   => new Argument("scanPath", "The directory to scan.", "", Argument::REQUIRED + Argument::NEW_PATH),
                "includeDir" => new Argument("includeDir", "The directory to scan.", [], Argument::NEW_PATH),
                "loadModule" => new Argument("loadModule", "Path to scanning module file.", [])
            ];
        }

        public function Execute() {
            $combinedArgumentsArray = [
                "FILESCANNER" => $this
            ];

            //if ($this->fileScannerRuntimeIOInterface instanceof ArgumentableInterface) {
                $combinedArgumentsArray["RUNTIME_IO"] = $this->fileScannerRuntimeIOInterface;//->GetCLIArguments();
            //}

            foreach ($this->modules as $moduleName => $module) {
                //if ($module instanceof ArgumentableInterface) {
                    $combinedArgumentsArray[$moduleName] = $module;//->GetCLIArguments();
                //}
            }

//            print_r($combinedArgumentsArray);

            if (($processedArguments = Arguments::Process($combinedArgumentsArray)) !== true)
                die (Arguments::Usage($combinedArgumentsArray, $processedArguments));
            
//            print_r($processedArguments);
        }
    }

    
    $fileScanner = new FileScanner();
    $fileScanner->Execute();

    print_r($fileScanner);
?>