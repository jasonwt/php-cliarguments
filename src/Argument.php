<?php
    declare(strict_types=1);
        
    namespace cliarguments;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    class Argument {
        protected $name;
        protected $defaultValue;
        protected $description;
        protected $options;

        const REQUIRED = 1;
        const INT = 2;

        const EXISTING_PATH = 4;
        const EXISTING_FILE = 8;
        const NEW_PATH      = 16;
        const NEW_FILE      = 32;        
        

        public function __construct(string $name, string $description, $defaultValue, int $options = 0) {
            if (($this->name = trim($name)) == "")
                throw new \Exception("name can not be empty");

            if (($this->description = trim($description)) == "")
                throw new \Exception("description can not be empty");

            $this->defaultValue = $defaultValue;

            if ($options < 0 || $options >= (16*2))
                throw new \Exception("Invalid options value '$options'");

            $this->options = $options;
        }

        public function GetName() : string {
            return $this->name;
        }

        public function GetDefaultValue() {
            return $this->defaultValue;
        }

        public function GetDescription() : string {
            return $this->description;
        }

        public function GetOptions() : int {
            return $this->options;
        }

        public function GetNameUsageLine() : string {
            $returnValue = "    --" . $this->GetName();

            if (!is_null(($value = $this->GetDefaultValue()))) {
                if (is_bool($value)) {
                    $returnValue .= "=<true|false>";
                } else if (is_array($value)) {
                    $returnValue .= "=<value> --" . $this->GetName() . "=<value> ...";
                } else {
                    $returnValue .= "=<value>";
                }
            }

            return $returnValue;
        }

        public function GetDescriptionUsageLine(int $indent = 0, int $maxLineLength = 0) : string {
            $returnValue = "";

            $description = $this->GetDescription();
            $nameUsageLine = $this->GetNameUsageLine();
            $maxDescriptionLength = $maxLineLength - strlen($nameUsageLine) - $indent;

            if (!is_null(($value = $this->GetDefaultValue()))) {
                if (is_bool($value)) {
                    $description .= " (default: " . ($value == true ? "true" : "false") . ")";
                } else if (is_array($value)) {

                } else {
                    if (is_numeric($value) || $value != "")
                        $description .= " (default: $value)";
                }
            }

            $returnValue = str_repeat(" ", max(0, $indent));

            if ($maxLineLength > 0 && $maxDescriptionLength < strlen($description)) {
                while ($description != "") {
                    $pos = min(strlen($description), $maxDescriptionLength) - 1;

                    if ($pos == $maxDescriptionLength - 1) {
                        while ($pos > 0 && trim($description[$pos]) != "")
                            $pos --;

                        if ($pos == 0)
                            $pos = min(strlen($description), $maxDescriptionLength);
                    }
                    
                    $returnValue .= substr($description, 0, $pos+1) . "\n";
                    $description = substr($description, $pos+1);

                    $returnValue .= str_repeat(" ", strlen($nameUsageLine) + $indent + 2);
                }
            } else {
                    $returnValue .= $description;
            }

            return $returnValue;
        }

        public function GetUsageLine(int $maxNameLength, int $maxLineLength = 0) : string {
            return 
                $this->GetNameUsageLine() . "  " . 
                $this->GetDescriptionUsageLine($maxNameLength - strlen($this->GetNameUsageLine()), $maxLineLength);
        }
    }

?>