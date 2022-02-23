<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

require_once 'vendor/autoload.php';

use Commando\Command;
use Colors\Color;

const ENV_OPTIONS = ['staging', 'stg', 'production', 'prod'];
const ROOT_DIR = __DIR__ . '/../';

const META_FILE_PATH = 'Oracle/M2/Impl/Core/Meta.php';

const MIDDLEWARE_FILE_PATH = 'Oracle/M2/Connector/Middleware.php';

const PLATFORM_FILE_PATH = 'Oracle/M2/Connector/Event/Platform.php';

$config = yaml_parse_file(__DIR__ . '/config.yml');
$command = new Command();
$color = new Color();
$env = '';
$cdnPath = '';
$buildDir = '';
parseCommands();
run();

function run()
{
    global $command, $config, $env, $cdnPath, $targetVersion, $targetDir, $buildDir;

    $dryRun = $command['d'] && !$command['f'];
    $env = $command[0];
    $cdnPath = $config['cdn_urls'][$env];
    $buildDir = ROOT_DIR . $config['paths']['build'];
    $targetVersion = $command[1];

    $currentPackages = \GuzzleHttp\json_decode(file_get_contents('packages.json'), true);

    updateMetaVersion($targetVersion);

    $podEnvs = array(
        "pod2" => "https://apps.p02.eloqua.com");

    foreach ($podEnvs as $podEnv => $url) {
        $targetDir = "{$buildDir}/{$podEnv}/{$targetVersion}";
        updatePodEnvURL($url);
        buildPackages($podEnv);
    }

    if (!$dryRun && $command['push-packages']) {
//        pushPackages();
    }
}

/**
 * Defines the commands for the script.
 */
function parseCommands() {
    global $command, $color;
    $commandDesc = $color->bold('Description') . PHP_EOL
        . 'This script creates a new deployable release and packages it into the \'builds\' subdirectory.'
        . PHP_EOL . PHP_EOL
        . $color->bold('Usage') . PHP_EOL
        . 'build.php ' . '<environment> ' . '<version> ' . '[arguments]';
    $command->setHelp($commandDesc);
    // Define environment option
    $command->option()
        ->aka('environment')
        ->require()
        ->describedAs('Environment type to build as.')
        ->title('Environment ' . $color->bold('{stg|prod}'))
        ->must(function($env) {
            $env = strtolower($env);
            return in_array($env, ENV_OPTIONS);
        })
        ->map(function($env) {
            $env = strtolower($env);
            $envs = ['staging' => 'stg', 'stg' => 'stg', 'production' => 'prod', 'prod' => 'prod'];
            if (array_key_exists($env, $envs)) {
                $env = $envs[$env];
            }
            return $env;
        });

    $command->option()
        ->aka('version')
        ->require()
        ->describedAs('Build version')
        ->title('Version');

    // Set dry-run (force) option
    $command->option('d')
        ->aka('dry-run')
        ->title('Dry Run')
        ->describedAs('Run with Dry Run mode activated. Defaults to true.')
        ->boolean()
        ->defaultsTo(true);
    $command->option('f')
        ->aka("force")
        ->title("Force")
        ->describedAs('Alias for disabling Dry Run Mode. Defaults to false')
        ->boolean()
        ->defaultsTo(false);
    $command->option()
        ->aka('push-packages')
        ->title('Push Packages')
        ->describedAs('Push packages.json changes to repo')
        ->boolean()
        ->defaultsTo(false);
}

/**
 * Replace version number in meta file with new version
 *
 * @param string $version
 */
function updateMetaVersion(string $version)
{
    $contents = file_get_contents(META_FILE_PATH);
    $contents = preg_replace("~const EXTENSION_VERSION = '.*'~", "const EXTENSION_VERSION = '{$version}'",
        $contents);
    file_put_contents(META_FILE_PATH, $contents);
}

/**
 * Replace version number in meta file with new version
 *
 * @param string $version
 */
function updatePodEnvURL(string $url)
{
    $contents = file_get_contents(MIDDLEWARE_FILE_PATH);
    $contents = preg_replace("~const BASE_URL = '.*'~", "const BASE_URL = '{$url}'",
        $contents);
    file_put_contents(MIDDLEWARE_FILE_PATH, $contents);
    unset($contents);

    $contents = file_get_contents(PLATFORM_FILE_PATH);
    $sarlacc = $url.'/ecom/ams/app/magento/application/request/object/ingest';

    $responsys_event_url = $url. '/ecom/ams/app/magento/application/request/message/ingest';

    $contents = preg_replace("~const SARLACC = '.*'~", "const SARLACC = '{$sarlacc}'", $contents);

    $contents = preg_replace("~const RESPONSYS_EVENT_URL = '.*'~", "const RESPONSYS_EVENT_URL = '{$responsys_event_url}'", $contents);

    file_put_contents(PLATFORM_FILE_PATH, $contents);
}

/**
 * Builds the module packages and updates metadata (packages.json and module composer.json)
 */
function buildPackages($podEnv)
{
    global $config, $env, $buildDir, $targetDir, $targetVersion, $cdnPath, $currentPackages, $dryRun;
    if (!file_exists($targetDir)) {
        echo "Creating target version directory {$targetVersion} in {$targetDir}" . PHP_EOL;
        if (!$dryRun) {
            mkdir($targetDir);
        }
    }
    $repoTemplate = [
        'repositories' => [
            [
                'type' => 'composer',
                'url' => $config['cdn_urls'][$env].'/'.$targetVersion.'/packages.json'
            ]
        ]
    ];

    $newPackages = [];
    $modulesDir = ROOT_DIR . $config['paths']['module'];
    echo "config directory ". PHP_EOL;
    echo  $config['paths']['module']. PHP_EOL;
    foreach (scandir($modulesDir) as $module) {
        if (in_array($module, ['.', '..'])) {
            continue;
        }

        $moduleDir = "{$modulesDir}/{$module}";
        $composerJsonPath = "{$moduleDir}/composer.json";
        if (!file_exists($composerJsonPath)) {
            continue;
        }

        $packageInfo = json_decode(file_get_contents($composerJsonPath), true);
        $packageInfo['version'] = $targetVersion;

        updateModuleComposer($packageInfo, $moduleDir, $composerJsonPath);

        // Archive the module
        $archiveOutput = null;
        echo "Creating a tarball for {$module} for version {$targetVersion}" . PHP_EOL;
        echo 'dry run '.$dryRun;
        echo PHP_EOL;
        if (!$dryRun) {
            $packageName = json_decode(file_get_contents("{$moduleDir}/composer.json"), true)["name"];
            // This command is used to generate a zip/tar archive for a given package in a given version. 
            // It can also be used to archive your entire project without excluded/ignored files.
            // php composer.phar archive vendor/package 2.0.21 --format=zip
            // default format is tar.

            exec("cd {$moduleDir} && composer archive --dir={$targetDir}/ -vvv --format=zip", $archiveOutput);
            echo PHP_EOL;

            // Update packages with new module version info
            $matches = null;
            if (preg_match('|/(.+)|', implode(' ', $archiveOutput), $matches)) {
                $filename = basename($matches[1]);
//                $packagePath = "{$cdnPath}/_packages/{$filename}";
                $packagePath = "{$cdnPath}/{$targetVersion}/{$filename}";
                $packageInfo['dist'] = [
                    'url' => $packagePath,
                    'type' => 'zip'
                ];
                $newPackages[$packageInfo['name']] = [$targetVersion => $packageInfo + $repoTemplate];
            }
        }
    }

    updatePackagesJson($newPackages, $targetVersion);
}

/**
 * Updates the composer.json file with new package information for the given module path
 *
 * @param array $packageInfo
 * @param string $moduleDir
 */
function updateModuleComposer(array &$packageInfo, string $moduleDir, $composerJsonPath)
{
    global $targetVersion;

    foreach ($packageInfo['require'] as $dependencyName => $version) {
        if ($dependencyName == 'oracle/php-common-helper') {
           // continue;
        }

        // Only update versions of Oracle dependencies
        if (substr($dependencyName, 0, strpos($dependencyName, '/')) == 'oracle') {
            $packageInfo['require'][$dependencyName] = $targetVersion;
        }
    }

    file_put_contents(
        $composerJsonPath,
        json_encode($packageInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

/**
 * Updates the packages.json file with the newly built module metadata
 *
 * @param array $newPackages
 * @param string $targetVersion
 */
function updatePackagesJson(array &$newPackages, string $targetVersion)
{
    global $dryRun, $targetDir;

    if (!$dryRun) {
        file_put_contents(
            "packages.json",
            json_encode(
                array_replace_recursive(json_decode(file_get_contents('packages.json'), true),
                    ['packages' => $newPackages]),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
        copy('packages.json', $targetDir . '/../packages.json');
    } else {
        echo "Adding packages to packages.json" . PHP_EOL;
    }
}

/**
 * Creates a tarball of all newly built packages
 */
function archivePackages()
{
    global $dryRun, $buildDir, $targetVersion;

    echo "Packaging all newly built packages into one tarball" . PHP_EOL;
    if (!$dryRun) {
        exec(
            "cd {$buildDir} && tar -czvf Oracle-Extension-{$targetVersion}.tar.gz {$targetVersion}"
        );
    }
}

/**
 * Determines if Dry Run mode is disabled. If so, confirm this was intentional.
 * Aborts the script if it was unintentional.
 */
function confirmDryRun()
{
    global $command, $dryRun, $color;

    $dryRun = $command['d'] && !$command['f'];
    if (!$dryRun) {
        $continue = strtolower(readline(
            $color->yellow(
                "Dry run is disabled. Script execution will make permanent changes. Do you wish to continue? {yes|y}"
            )
        ));
        if (! preg_match("/^(y)$|^(yes)$/", $continue)) {
            killScript('Script aborted');
        }
    }
}

/**
 * Pushes updated packages.json file up to the develop branch
 */
function pushPackages()
{
    $repoUrl = getenv("CI_REPOSITORY_URL");
    $pushUrl = preg_replace("~.+@([^/]+)\/~", "git@$1:", $repoUrl);

    exec("git remote set-url origin {$pushUrl}");
    exec("git add .");
    exec("git commit -m 'Update packages.json'");
    exec("git push origin develop");
}

/**
 * Called when the script ends prematurely.
 *
 * @param string|null $text
 * @param int $code
 */
function killScript(string $text = null, int $code = 1)
{
    global $color;

    $text = ($text !== null) ? $text : 'Script exited with code ';
    echo $color->red($text . PHP_EOL);
    die($code);
}
