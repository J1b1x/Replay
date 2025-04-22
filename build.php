<?php
/*
 * Copyright (c) 2023-2024 Jibix.
 * All rights reserved.
 * I don't want anyone to use my source code without permission.
 */

declare(strict_types=1);

require (__DIR__) . '/vendor/autoload.php';

const INCLUDED_VIRIONS = [
    "j1b1x/asyncmedoo",
    "j1b1x/functionalitem",
    "j1b1x/forms",
    "Medoo" => "catfan/medoo"
];

function preg_quote_array(array $strings, string $delim) : array{
    return array_map(function(string $str) use ($delim) : string{ return preg_quote($str, $delim); }, $strings);
}

function replace_path_seperators(string $path): string{
    return str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $path);
}

function buildPhar(string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = \Phar::SHA1, ?int $compression = null){
    $basePath = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $includedPaths = array_map(function(string $path) : string{
        return rtrim(str_replace("/", DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }, $includedPaths);
    yield "Creating output file $pharPath";
    if(file_exists($pharPath)){
        yield "Phar file already exists, overwriting...";
        try{
            \Phar::unlinkArchive($pharPath);
        }catch(\PharException $e){
            //unlinkArchive() doesn't like dodgy phars
            unlink($pharPath);
        }
    }

    yield "Adding files...";

    $start = microtime(true);
    $phar = new \Phar($pharPath);
    $phar->setMetadata($metadata);
    $phar->setStub($stub);
    $phar->setSignatureAlgorithm($signatureAlgo);
    $phar->startBuffering();

    //If paths contain any of these, they will be excluded
    $excludedSubstrings = preg_quote_array([
        realpath($pharPath), //don't add the phar to itself
    ], '/');

    $folderPatterns = preg_quote_array([
        DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . '.' //"Hidden" files, git dirs etc
    ], '/');

    //Only exclude these within the basedir, otherwise the project won't get built if it itself is in a directory that matches these patterns
    $basePattern = preg_quote(rtrim($basePath, DIRECTORY_SEPARATOR), '/');
    foreach($folderPatterns as $p){
        $excludedSubstrings[] = $basePattern . '.*' . $p;
    }

    $regex = sprintf('/^(?!.*(%s))^%s(%s).*/i',
        implode('|', $excludedSubstrings), //String may not contain any of these substrings
        preg_quote($basePath, '/'), //String must start with this path...
        implode('|', preg_quote_array($includedPaths, '/')) //... and must be followed by one of these relative paths, if any were specified. If none, this will produce a null capturing group which will allow anything.
    );

    $directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::CURRENT_AS_PATHNAME); //can't use fileinfo because of symlinks
    $iterator = new \RecursiveIteratorIterator($directory);
    $regexIterator = new \RegexIterator($iterator, $regex);
    $count = count($phar->buildFromIterator($regexIterator, $basePath));

    yield "Adding libraries...";
    foreach (INCLUDED_VIRIONS as $customPath => $virion) {
        if (!is_dir($path = replace_path_seperators(__DIR__ . "/vendor/$virion/src"))) {
            yield "Composer package '$virion' not found!";
            continue;
        }
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
            if ($file->isFile()) {
                $targetPath = str_replace($path, "", $filePath = $file->getPathname());
                if (is_string($customPath)) $targetPath = DIRECTORY_SEPARATOR . $customPath . $targetPath;
                $phar->addFromString("src" . $targetPath, file_get_contents($filePath));
                $count++;
            }
        }
    }

    yield "Adding plugin.yml...";
    foreach (["plugin.yml", "virion.yml"] as $other) {
        if (file_exists(getcwd() . DIRECTORY_SEPARATOR .  $other)) {
            $phar->addFile($other);
            $count++;
        }
    }
    yield "Added $count files";

    if($compression !== null){
        yield "Compressing files...";
        $phar->compressFiles($compression);
        yield "Finished compression";
    }
    $phar->stopBuffering();

    yield "Done in " . round(microtime(true) - $start, 3) . "s";
}

function main() : void{
    if(ini_get("phar.readonly") == 1){
        echo "Set phar.readonly to 0 with -dphar.readonly=0" . PHP_EOL;
        exit(1);
    }
    if(file_exists(__DIR__ . '/vendor/phpunit')){
        echo "Remove Composer dev dependencies before building (composer install --no-dev)" . PHP_EOL;
        exit(1);
    }

    $opts = getopt("", ["out:", "build:"]);
    if(isset($opts["build"])){
        $build = (int) $opts["build"];
    }else{
        $build = 0;
    }
    foreach(buildPhar(
                $opts["out"] ?? getcwd() . DIRECTORY_SEPARATOR . yaml_parse_file(getcwd() . DIRECTORY_SEPARATOR . "plugin.yml")['name'] . ".phar",
                __DIR__ . DIRECTORY_SEPARATOR,
                [
                    'resources',
                    'src',
                ],
                [
                    'build' => $build
                ],
                <<<'STUB'
<?php

$tmpDir = sys_get_temp_dir();
if(!is_readable($tmpDir) or !is_writable($tmpDir)){
	echo "ERROR: tmpdir $tmpDir is not accessible." . PHP_EOL;
	echo "Check that the directory exists, and that the current user has read/write permissions for it." . PHP_EOL;
	echo "Alternatively, set 'sys_temp_dir' to a different directory in your php.ini file." . PHP_EOL;
	exit(1);
}
__HALT_COMPILER();
STUB
                ,
                \Phar::SHA1,
                \Phar::GZ
            ) as $line){
        echo $line . PHP_EOL;
    }
}

main();
