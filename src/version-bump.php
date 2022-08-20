#!/usr/bin/env php
<?php
class VersionBump
{
    private string $versFilePath;
    private array $vers;

    public static function initFile(string $versFilePath): void {
        $vers = [
            'major' => 0,
            'minor' => 0,
            'patch' => 1,
        ];
        $info = new \SplFileInfo($versFilePath);
        $rp = $info->getRealPath();
        if($rp !== false) {
            print("the file {$rp}already exists. Are you sure you want to overwrite it ? Type anything and return for Yes Ctrl-C/Z for no)");
        }
        $a = readline();
        $s = '<?php $vers = ' . var_export($vers, true) .'; return $vers;';
        file_put_contents($versFilePath, $s);
    }

    /**
     * @throws Exception
     */
    public function __construct(string $versFilePath)
    {
        $this->loadVersFile($versFilePath);
    }

    public function show() : string
    {
        $major = $this->vers['major'];
        $minor = $this->vers['minor'];
        $patch = $this->vers['patch'];
        $s = "v{$major}.{$minor}.{$patch}";
        return $s;
    }
    /**
     * @throws Exception
     */
    public function asString() : string
    {
        $major = $this->vers['major'];
        $minor = $this->vers['minor'];
        $patch = $this->vers['patch'];
        $s = "v{$major}.{$minor}.{$patch}";
        return $s;
    }
    private function loadVersFile(string $versFilePath): void
    {
        if(!file_exists($versFilePath)) {
            throw new \Exception("version file {$versFilePath} does not exist");
        }
        $this->versFilePath = $versFilePath;
        $this->vers = include $versFilePath;
    }
    private function saveVersFile(): void
    {
        print($this->asString() . "\n");
        $s = '<?php $vers = ' . var_export($this->vers, true) .'; return $vers;';
        file_put_contents($this->versFilePath, $s);
    }

    /**
     * @throws Exception
     */
    public function bumpMajor(): void
    {
        $this->vers['major'] += 1;
        $this->vers['minor'] = 0;
        $this->vers['patch'] = 0;
        $this->saveVersFile();
        $this->gitCommitAndTag();
    }

    /**
     * @throws Exception
     */
    public function bumpMinor(): void
    {
        $this->vers['minor'] += 1;
        $this->vers['patch'] = 0;
        $this->saveVersFile();
        $this->gitCommitAndTag();
    }

    /**
     * @throws Exception
     */
    public function bumpPatch(): void
    {
        $this->vers['patch'] += 1;
        $this->saveVersFile();
        $this->gitCommitAndTag();
    }

    /**
     * @throws Exception
     */
    private function gitCommitAndTag(): void
    {
        $s = $this->asString();
        system("git add -A");
        system( "git commit -a -m 'auto commit for version $s' ");
        system("git tag -a {$s} -m 'automatic tag'");
        system("git tag");
    }

    /**
     * @throws Exception
     */
    public function tag(): void
    {
        $this->show();
        $this->gitCommitAndTag();
    }
}
function printUsage(): void
{
    $u = <<<EOD
Description
    Initialize and/or update a php file containing a version number.
    
    If the command is major, minor, patch performs the following commands to
    make the latest version number into a git tag
    
    git add -A
    git commit -a -m"auto commit vx.x.x"
    git tag -a vx.x.x -m"auto"
    
Usage:
    bump-version <command> [<args>]
Commands
    init  <filePath>    Create a php file at the given path with v0.0.0 as content
    
    tag   <filePath>    Creates a git tag with the current version in the file <filePath>
    
    major <filePath>    Increments the major version number in the fil at the given path
                        sets minor and patch to 0
    minor <filePath>    Increments the minor version number in the file at <filePath> leaves
                        the major number unchanged and sets patch value to 0
    patch <filePath>    Increments the patch version number in the file at <filePath> leaves
                        the major and minor number unchanged.
    show <filePath>     Print out the current version string.
                        
    help(-h)(--help)    Print this message


EOD;
    print($u);
}

/**
 * @throws Exception
 */
function main(array $argv): void
{
    if(count($argv) == 2) {
       $action = $argv[1];
        if (in_array(strtolower($action), ["help", "-h", "--help"])) {
            printUsage();
            exit(0);
        }
        printUsage();
        exit(1);
    } else if(count($argv) !== 3) {
        print("Invalid arguments\n");
        printUsage();
        exit(1);
    }
    $action = strtolower($argv[1]);
    $versFilePath = $argv[2];
    switch($action) {
        case "init":
            \VersionBump::initFile($versFilePath);
            break;
        case "tag":
            $v = new VersionBump($versFilePath);
            $v->tag($versFilePath);
            break;
        case "patch":
            $v = new VersionBump($versFilePath);
            $v->bumpPatch($versFilePath);
            break;
        case "minor":
            $v = new VersionBump($versFilePath);
            $v->bumpMinor($versFilePath);
            break;
        case "major":
            $v = new VersionBump($versFilePath);
            $v->bumpMajor($versFilePath);
            break;
        case "show":
            $v = new VersionBump($versFilePath);
            print($v->show($versFilePath)."\n");
            break;
        case "help":
        case "-h":
        case "--help":
            printUsage();
            break;
        default:
            print("Error invalid first argument");
            printUsage();
            break;
    }
}
main($argv);