<?php
    declare(strict_types=1);
        
    namespace pctlib\cliarguments;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    interface ArgumentableInterface {
        public function GetCLIArguments() : array;
        public function SetParameters(array $parameters) : bool;
    }

?>