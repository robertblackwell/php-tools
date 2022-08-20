#!/usr/bin/env php
<?php
ini_set("error_reporting", E_ALL);
// require_once(dirname(__DIR__)."/vendor/autoload.php");
require_once(__DIR__."/phar_builder_class.php");
function printUsage()
{
    $s=<<<EOD

Description:

    create-phar Builds a phar archive of this projects cli app.
    
    Assumes 
    -   project code is in root/src. 
    -   All *.php files (and only *.php files) in root/src are included in the phar
    -   assumes the vendor directory is root/vendor
    -   includes all *.php files from root/vendor in the phar

Usage:

    create-phar <root> <alias> <phar_main> <output>
    
    root        Full path to the project root directory, usually `pwd`
    alias       Name of the form "project.phar" - an alias to be used in Phar::mapPhar() call in the phar stub
    phar_main   Full path to a short php file that contains this projects entry point code intended for
                use in a phar archive. At a minimum should contains the following:
                
<?php
/**
 * This file is the main entry point for a phar packaging of this project
 */
ini_set("display_errors", '1');
require_once "phar://alias.phar/project-name/vendor/autoload.php";
require_once "phar://alias/project-name/otherfilesinprojecysrc.php";
require_once "main.php"; //the top level file to run the project
?>                
                 
    output      Full path to the expected output phar file. If this file already exists it will be 
                deleted as part of a build

EOD;
    print($s."\n");
}
function main(array $argv): void
{
    if(count($argv) != 5) {
        printUsage();
        exit(1);
    }
    $rootInfo = new \SplFileInfo($argv[1]);
    $root = $rootInfo->getRealPath();
    if($root === false) {
        print("Failed: root argument does not exist as file or directory \n");
        exit(1);
    }
    $alias = $argv[2];
    $pharInfo = new \SplFileInfo($argv[3]);
    $pharMain = $pharInfo->getRealPath();
    if($pharMain === false) {
        print("Failed: pharMain does not exist as file or dirctory \n");
        exit(1);
    }
    $outFileInfo = new \SplFileInfo($argv[4]);
    $outfile = $outFileInfo->getRealPath();
    if($outfile !== false) {
        unlink($outfile);
    } else {
        $outfile = $outFileInfo->getPathname();
        $d = realpath(dirname($outfile));
        $fn = basename($outfile);
        $outfile = $d . "/" . $fn;
    }
    $targetPath = __DIR__."/../build/litetest.phar";
    $targetAlias = "litetest.phar";
    $rootPath = __DIR__."/../";
//    return;
    $targetPath = $outfile;
    $targetAlias = $alias;
    $rootPath = $root;
    try {
        $builder = new PharBuilderClass($targetPath, $root, $targetAlias, $root."/src/phar_main.php");
    //    var_dump($builder);
        $builder->build();
    //    var_dump(scandir("phar://{$targetAlias}/"));
    //    var_dump(scandir("phar://{$targetAlias}/litetest"));
        print("Build complete\n");
    } catch (Exception $e) {
        var_dump($e);
    }
}

try {
    main($argv);
} catch (Exception $e) {
    var_dump($e);
}