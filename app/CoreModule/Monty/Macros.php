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
    	bdump($node, "node");
    	return "ob_start();";
    }  

    public static function sassToCssEnd(\Latte\MacroNode $node, \Latte\PhpWriter $writer) {
    	bdump($node, "node");
    	$sass = $node->content;
    	bdump($sass, "sass");
    	$sass = trim($sass);
    	$sass = str_replace(["<style>", "</style>"], "", $sass);
    	bdump($sass, "sass stripped");

    	$scss = new ScssCompiler();
    	$css = $scss->compile($sass);
    	bdump($css, "css");

    	return "\$content = ob_get_clean();"
    		. "echo '" . $css . "'";
    }

}