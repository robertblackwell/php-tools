<?php
namespace SimplePhar;
use Symfony\Component\Finder\Finder;

class Builder
{
    private string $rootDir;
    private string $pharName;
    private string $pharAlias;
    private string $pathToMain;

    /**
     * This class builds a phar file for simple cli apps
     * @param string $pharName   full path to the phar file. Will be deleted if it exists
     * @param string $rootDir    project root dir including the projects top dir
     * @param string $pharAlias  single path componene of form "myphar.phar" - must end in .phar
     * @param string $pathToMain full path name of the 'main' file - the entry point to the cli app 
     * @throws Exception
     */
    public function __construct(string $pharName, string $rootDir, string $pharAlias, string $pathToMain)
    {
        if(!file_exists($pathToMain)) {
            throw new \RuntimeException("pathToMain: {$pathToMain} does not exist");
        }
        $this->pathToMain = (new \SplFileInfo($pathToMain))->getRealPath();
        $this->pharAlias = $pharAlias;
        $rootInfo = new \SplFileInfo($rootDir);
        if(is_null($rootInfo)) {
            throw new \Exception("root path {$rootDir} does not exist");
        }
        $this->rootDir = $rootInfo->getRealPath();
        $pharInfo = new \SplFileInfo($pharName);
        if(is_null($pharInfo)) {
            throw new \Exception("phar path {$pharName} does not exist");
        }
        $x = $pharInfo->getExtension();
        if($pharInfo->getExtension() !== "phar") {
            throw new \Exception("phar path {$pharName} must have extension '.phar'");
        }
        $y = $pharInfo->getRealPath();
        $this->pharName = $pharInfo->getPathname();
    }
    function build(): void {
        if(file_exists($this->pharName)) {
            unlink($this->pharName);
        }
        $target  = $this->pharName;
        try {
            $phar = new \Phar($this->pharName, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_FILENAME, $this->pharAlias);
        } catch(\Exception $e) {
            var_dump($e);
        }
        //$phar->setSignatureAlgorithm(\Phar::SHA1);
        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->in($this->rootDir.'/src')
        ;
        if(file_exists($this->rootDir."/exp/test3_main.php")) {
            $this->addFile($phar,new \SplFileInfo($this->rootDir."/exp/test3_main.php"));
        }
        foreach ($finder as $file) {
            print("$file \n");
            $this->addFile($phar, $file);
        }
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->exclude('vendor')
            ->in($this->rootDir.'/vendor/symfony/')
            ->in($this->rootDir.'/vendor/kevinlebrun/')
            ->in($this->rootDir.'/vendor/psr/')
            ->in($this->rootDir.'/vendor/composer/')
        ;
        foreach ($finder as $file) {
            print("$file \n");
            $this->addFile($phar, $file);
        }
        $x = new \SplFileInfo(__DIR__.'/../../vendor/autoload.php');
        $this->addFile($phar, new \SplFileInfo($this->rootDir.'/vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo($this->rootDir.'/bin/runner.php'));

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

    }

    function addFile($phar, $file, $strip = true): void
    {
        $path    = str_replace(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR, '', $file->getRealPath());
        $content = file_get_contents($file);
        $phar->addFromString($path, $content);
    }

    function addBin($phar): void
    {
        $content = file_get_contents(__DIR__ . '/../../bin/composer');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/composer', $content);
    }
    private function getStub(): string
    {
        $rootParent = (dirname($this->rootDir));
        $pathToMain = $this->pathToMain;
        $alias = $this->pharAlias;
        $relativePathToMain = str_replace("{$rootParent}/", "", $pathToMain);
        $requireMainRelativePath = "../$relativePathToMain";
        $stub = <<<EOD
#!/usr/bin/env php
<?php
Phar::mapPhar("$alias");
Phar::interceptFileFuncs();
print("stub path is : ".__FILE__."\n");
print("stub dir is : ". __DIR__."\n");
print("running true " . Phar::running(true) . "\n");
print("running false " . Phar::running(false) . "\n");
// [$pathToMain]
require "$requireMainRelativePath";
//require "../litetest/exp/test3_main.php";
//require "phar://$alias/$relativePathToMain";
__HALT_COMPILER();
EOD;
        return $stub;
    }
}
