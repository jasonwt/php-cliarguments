<?php
    declare(strict_types=1);
        
    namespace pctlib\cliarguments;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/Argument.php");
    require_once(__DIR__ . "/ArgumentableInterface.php");

    class Arguments {
        public static function Usage(array $arguments, string $message, string $usageString = "", int $maxLineLength = 0) : string {

            $arguments = array_filter($arguments, function ($v, $k) {return ($v instanceof ArgumentableInterface);}, ARRAY_FILTER_USE_BOTH);

            $returnValue = "\n\n" . $usageString;

            if (!$usageString)
                $returnValue .= "USAGE " . $_SERVER["argv"][0] . " [OPTIONS]\n";

            if ($message)
                $returnValue .= "\n  " . $message . "\n";

            $maxNameLength = 0;

            foreach ($arguments as $argumentsGroupName => $argumentsGroupValue) {
                $argumentsGroupValue = $argumentsGroupValue->GetCLIArguments();
                foreach ($argumentsGroupValue as $argument)
                    $maxNameLength = max($maxNameLength, strlen($argument->GetNameUsageLine()));
            }
            
            foreach ($arguments as $argumentsGroupName => $argumentsGroupValue) {
                $argumentsGroupValue = $argumentsGroupValue->GetCLIArguments();
                if (count($argumentsGroupValue) == 0)
                    continue;

                $returnValue .= "\n  " . $argumentsGroupName . " OPTIONS\n";

                foreach ($argumentsGroupValue as $argument)
                    $returnValue .= $argument->GetUsageLine($maxNameLength, $maxLineLength) . "\n";
            }
            
            return $returnValue;
        }

        public static function Process(array $arguments) {
            $returnValue = [];

            $arguments = array_filter($arguments, function ($v, $k) {return ($v instanceof ArgumentableInterface);}, ARRAY_FILTER_USE_BOTH);

            $argv = $_SERVER["argv"];

            for ($acnt = 1; $acnt < count($argv); $acnt ++) {
                
                if (substr($argv[$acnt], 0, 2) != "--")
                    return "Invalid argument '" . $argv[$acnt] . "'";

                if (strtolower($argv[$acnt]) == "--help")
                    return "";

                $parts = explode("=", substr($argv[$acnt], 2), 2);
                $argName = $parts[0];
                $argValue = (count($parts) == 2 ? $parts[1] : null);

                $argumentFound = false;

                foreach ($arguments as $argumentsGroupName => $argumentsGroupValue) {
                    $argumentsGroupValue = $argumentsGroupValue->GetCLIArguments();

                    if (count($argumentsGroupValue) == 0)
                        continue;

                    if (!isset($returnValue[$argumentsGroupName]))
                        $returnValue[$argumentsGroupName] = [];

                    foreach ($argumentsGroupValue as $argument) {
                        $argumentName = $argument->GetName();
                        $argumentDefaultValue = $argument->GetDefaultValue();

                        if (!isset($returnValue[$argumentsGroupName][$argumentName]))
                            $returnValue[$argumentsGroupName][$argumentName] = $argumentDefaultValue;                        

                        if (strtolower($argName) != strtolower($argumentName))
                            continue;

                        $argumentFound = true;

                        switch (gettype($argumentDefaultValue)) {
                            case "string": 
                            case "integer": {
                                if (is_null($argValue))
                                    return "Missing value for argument '$argName'.";

                                $returnValue[$argumentsGroupName][$argumentName] = $argValue;
                                break;
                            }

                            case "boolean": {
                                if (is_null($argValue))  {
                                    $returnValue[$argumentsGroupName][$argumentName] = true;
                                    break;
                                }

                                if (strtolower($argValue) == "true" || $argValue == "1") {
                                    $returnValue[$argumentsGroupName][$argumentName] = true;
                                    break;
                                }

                                if (strtolower($argValue) == "false" || $argValue == "0") {
                                    $returnValue[$argumentsGroupName][$argumentName] = false;
                                    break;
                                }

                                return "Invalid argument value for $argName '$argValue'.  Expected true or false.";
                            }

                            case "array": {
                                if (is_null($argValue))
                                    return "Missing value for argument '$argName'.";

                                $returnValue[$argumentsGroupName][$argumentName][] = $argValue;
                                break;
                            }

                            case "NULL": {
                                $returnValue[$argumentsGroupName][$argumentName][] = $argValue;
                                break;
                            }

                            default : {
                                throw new \Exception("Unknown error. " . gettype($argumentDefaultValue));
                            }
                        }                        
                    }
                }

                if (!$argumentFound)
                    return "Invalid argument '--$argName'";
            }

            foreach ($arguments as $argumentsGroupName => $argumentsGroupValue) {
                $argumentsGroupValue = $argumentsGroupValue->GetCLIArguments();

                if (count($argumentsGroupValue) == 0)
                    continue;

                if (!isset($returnValue[$argumentsGroupName]))
                    $returnValue[$argumentsGroupName] = [];

                foreach ($argumentsGroupValue as $argument) {      
                    $argumentName        = $argument->GetName();
                    $argumentOptions     = $argument->GetOptions();
                    
                    if (!isset($returnValue[$argumentsGroupName]))
                        $returnValue[$argumentsGroupName] = [];

                    $argumentDefaultValue = $argument->GetDefaultValue();

                    if (!isset($returnValue[$argumentsGroupName][$argumentName]))
                        $returnValue[$argumentsGroupName][$argumentName] = $argumentDefaultValue;

                    $argumentReturnValue = &$returnValue[$argumentsGroupName][$argumentName];

                    if ($argumentOptions <= 0)
                        continue;
                        
                    if ($argumentOptions & Argument::REQUIRED) {
                        if (is_array($argumentReturnValue)) {
                            if (count($argumentReturnValue) == 0)
                                return "--$argumentName is required.";
                        } else if (!is_numeric($argumentReturnValue)) {
                            if ($argumentReturnValue == "")
                                return "--$argumentName is required.";
                        }
                    }

                    if ($argumentOptions & Argument::INT) {
                        if (is_array($argumentReturnValue)) {
                            foreach ($argumentReturnValue as $k => $v) {
                                if (!preg_match('/^-?\d+$/', strval($v)))
                                    return "--$argumentName: '$v' must be an integer.";
                            }
                            
                        } else {
                            if (!preg_match('/^-?\d+$/', strval($argumentReturnValue)))
                                return "--$argumentName: '$argumentReturnValue' must be an integer.";
                        }
                    }

                    if (($argumentOptions & Argument::EXISTING_PATH) || ($argumentOptions & Argument::EXISTING_FILE)) {
                        if (is_array($argumentReturnValue)) {
                            for ($ac = 0; $ac < count($argumentReturnValue); $ac ++) {
                                $v = $argumentReturnValue[$ac];

                                if (is_null($v))
                                    continue;

                                if ($argumentOptions & Argument::EXISTING_FILE && !is_file($v))
                                    return "--$argumentName: '$v' is not a file or does not exist.";
                                else if ($argumentOptions & Argument::EXISTING_PATH && !is_dir($v))
                                    return "--$argumentName: '$v' is not a folder or does not exist.";

                                if (is_dir($v)) {                                    
                                    if ($v[0] != "/")
                                        $argumentReturnValue[$ac] = getcwd() . "/" . $argumentReturnValue[$ac];

                                    if (substr($v, -1) != "/")
                                       $argumentReturnValue[$ac] .= "/";
                                }
                            }                            
                        } else {
                            if (!is_null($argumentReturnValue)) {
                                if ($argumentOptions & Argument::EXISTING_FILE && !is_file($argumentReturnValue))
                                    return "--$argumentName: '$argumentReturnValue' is not a file or does not exist.";
                                else if ($argumentOptions & Argument::EXISTING_PATH && !is_dir($argumentReturnValue))
                                    return "--$argumentName: '$argumentReturnValue' is not a folder or does not exist.";

                                if (is_dir($argumentReturnValue)) {                                    
                                    if ($argumentReturnValue[0] != "/")
                                        $argumentReturnValue = getcwd() . "/" . $argumentReturnValue;

                                    if (substr($argumentReturnValue, -1) != "/")
                                        $argumentReturnValue .= "/";
                                }
                            }
                        }
                    }

                    if (($argumentOptions & Argument::NEW_PATH) || ($argumentOptions & Argument::NEW_FILE)) {
                        if (is_array($argumentReturnValue)) {
                            for ($ac = 0; $ac < count($argumentReturnValue); $ac ++) {
                                $v = $argumentReturnValue[$ac];
                                if (is_null($v))
                                    continue;

                                if (file_exists($v))
                                    return "--$argumentName: '$v' already exists.";

                                if ($argumentOptions & Argument::NEW_PATH && $v != "") {                                    
                                    if ($v[0] != "/")
                                        $argumentReturnValue[$ac] = getcwd() . "/" . $argumentReturnValue[$ac];

                                    if (substr($v[0], -1) != "/")
                                        $argumentReturnValue[$ac] .= "/";
                                }                                
                            }                            
                        } else {
                            if (!is_null($argumentReturnValue)) {
                                if (file_exists($argumentReturnValue))
                                    return "--$argumentName: '$argumentReturnValue' already exists.";

                                if ($argumentOptions & Argument::NEW_PATH && $argumentReturnValue != "") {                                    
                                    if ($argumentReturnValue[0] != "/")
                                        $argumentReturnValue = getcwd() . "/" . $argumentReturnValue;

                                    if (substr($argumentReturnValue, -1) != "/")
                                        $argumentReturnValue .= "/";
                                }
                            }
                        }
                    }
                }
            }

            foreach ($returnValue as $k => $v) {
                if (!$arguments[$k]->SetParameters($v))
                    return $k . "->SetParameters() failed.";
            }

            return true;
        }
    }

?>