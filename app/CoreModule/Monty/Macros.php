<?php

namespace Monty;

use ScssPhp\ScssPhp\Compiler as ScssCompiler;


class Macros extends \Latte\Macros\MacroSet
{

    public static function install(
        \Latte\Compiler $compiler
    ) {
        $set = new static($compiler);
 
        $set->addMacro("sass", [$set, "sassToCss"], [$set, "sassToCssEnd"]);
    }

    public static function sassToCss(\Latte\MacroNode $node, \Latte\PhpWriter $writer) {
    	\Tracy\Debugger::barDump($node, "node");
    	return "ob_start();";
    }  

    public static function sassToCssEnd(\Latte\MacroNode $node, \Latte\PhpWriter $writer) {
    	\Tracy\Debugger::barDump($node, "node");
    	$sass = $node->content;
    	\Tracy\Debugger::barDump($sass, "sass");
    	$sass = trim($sass);
    	$sass = str_replace(["<style>", "</style>"], "", $sass);
    	\Tracy\Debugger::barDump($sass, "sass stripped");

    	$scss = new ScssCompiler();
    	$css = $scss->compile($sass);
    	\Tracy\Debugger::barDump($css, "css");

    	return "\$content = ob_get_clean();"
    		. "echo '" . $css . "'";
    }

}