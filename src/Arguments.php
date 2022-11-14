<?php
    declare(strict_types=1);
        
    namespace pctlib\cliarguments;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once(__DIR__ . "/Argument.php");

    class Arguments {
        public static function Usage(array $arguments, string $message, int $maxLineLength = 0) : string {
            $returnValue = "";

            if ($message)
                $returnValue .= "\n  " . $message . "\n";

            $maxNameLength = 0;

            foreach ($arguments as $argumentsGroupName => $argumentsGroupValue) {
                foreach ($argumentsGroupValue as $argument)
                    $maxNameLength = max($maxNameLength, strlen($argument->GetNameUsageLine()));
            }
            
            foreach ($arguments as $argumentsGroupName => $argumentsGroupValue) {
                if (count($argumentsGroupValue) == 0)
                    continue;

                $returnValue .= "\n  " . $argumentsGroupName . " OPTIONS\n";

                foreach ($argumentsGroupValue as $argument)
                    $returnValue .= $argument->GetUsageLine($maxNameLength, $maxLineLength) . "\n";
            }
            
            return $returnValue;
        }

        public static function ProcessArguments(array $arguments) {
            $returnValue = [];

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

                    
                    
                    
                    $argumentReturnValue = $returnValue[$argumentsGroupName][$argumentName];

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

                    if ($argumentOptions & Argument::EXISTING_FILE) {
                        if (is_array($argumentReturnValue)) {
                            foreach ($argumentReturnValue as $k => $v) {
                                if (!is_file($v))
                                    return "--$argumentName: '$v' must be a existing file.";
                            }                            
                        } else {
                            if (!is_file($argumentReturnValue))
                                return "--$argumentName: '$argumentReturnValue' must be a existing file.";                            
                        }
                    }

                    if ($argumentOptions & Argument::EXISTING_PATH) {
                        if (is_array($argumentReturnValue)) {
                            foreach ($argumentReturnValue as $k => $v) {
                                if (!is_dir($v))
                                    return "--$argumentName: '$v' must be a existing directory.";
                            }                            
                        } else {
                            if (!is_dir($argumentReturnValue))
                                return "--$argumentName: '$argumentReturnValue' must be a existing directory.";                            
                        }
                    }

                    if ($argumentOptions & Argument::EXISTING_FILE) {
                        if (is_array($argumentReturnValue)) {
                            foreach ($argumentReturnValue as $k => $v) {
                                if (!is_file($v))
                                    return "--$argumentName: '$v' must be a existing file.";
                            }                            
                        } else {
                            if (!is_file($argumentReturnValue))
                                return "--$argumentName: '$argumentReturnValue' must be a existing file.";                            
                        }
                    }

                    if (($argumentOptions & Argument::NEW_PATH) || ($argumentOptions & Argument::NEW_FILE)) {
                        if (is_array($argumentReturnValue)) {
                            foreach ($argumentReturnValue as $k => $v) {
                                if (file_exists($v))
                                    return "--$argumentName: '$v' already exists.";
                            }                            
                        } else {
                            if (!is_dir($argumentReturnValue))
                                return "--$argumentName: '$argumentReturnValue' already exists.";
                        }
                    }
                }
            }

            return $returnValue;
        }
    }

?>