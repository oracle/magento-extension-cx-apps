<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */
require_once 'vendor/autoload.php';

use Commando\Command;
use Colors\Color;
use PHLAK\SemVer;

$command = new Command();
$color = new Color();

parseCommands();
run();

function run() {
    global $command, $color;

    // Get latest tagged version
    exec("git describe --tags --abbrev=0", $output, $code);
    $latest = ($code === 0) ? array_pop($output) : '';
    if (empty($latest)) {
        echo "No tagged version found";
        exit(1);
    }

    try {
        $latestVersion = new SemVer\Version($latest);
    } catch (SemVer\Exceptions\InvalidVersionException $ive) {
        echo "Invalid version. Value given: " . $latest;
        exit(1);
    }

    $generatedVersion = null;
    if ($command['prod']) {
        // prep version for production release
        $generatedVersion = clone $latestVersion;
        $generatedVersion->setPreRelease(null)
            ->setBuild(null);
    } else {
        $generatedVersion = bumpVersion($latestVersion);
    }

    echo $generatedVersion;
}

/**
 * Parse options and arguments provided to the script
 */
function parseCommands() {
    global $command, $color;

    $commandDesc = $color->bold('Description') . PHP_EOL
        . 'This command generates a release version and tags it.'
        . PHP_EOL . PHP_EOL
        . $color->bold('Usage') . PHP_EOL
        . 'gen-version.php [arguments]';
    $command->setHelp($commandDesc);

    // Define environment option
    $command->option('p')
        ->aka('prod')
        ->describedAs('Generate and tag a Production version. Defaults to Staging.')
        ->title('Production Environment')
        ->boolean();
}

/**
 * Bumps the semantic version based on commit message keywords found in all commit messages up to the last tag
 *
 * Will append rc pre-release to the version and a new build number
 *
 * Commit Message Keywords:
 *  '#patch' - patch level (default)
 *  '#minor' - minor level
 *  '#major' - major level
 *
 * @param SemVer\Version $latest
 * @returns SemVer\Version
 * @throws SemVer\Exceptions\InvalidVersionException when an invalid version is given
 */
function bumpVersion(SemVer\Version $latestVersion) {

    // Get last released version. We will resolve all commit message keywords since the last public release.
    exec("git tag --sort=-creatordate", $tags);
    $lastRelease = array_shift($tags);
    while(stripos($lastRelease, '-rc')) {
        $lastRelease = array_shift($tags);
    }
    $newVersion = new SemVer\Version($lastRelease);

    // Get commit messages
    exec("git log {$lastRelease}..HEAD --pretty=format:\"- %s\" ", $commitMessages);
    if (empty($commitMessages)) {
        // skip bumping if no commits were made since last tag.
        return $latestVersion;
    }

    // Determine bump level
    $bumpLevel = '';
    foreach ($commitMessages as $line) {
        if (stripos($line, '#major') !== false) {
            $bumpLevel = 'major';
            break;
        }
        if (stripos($line, '#patch') !== false) {
            $bumpLevel = 'patch';
        }
        if (stripos($line, '#minor') !== false) {
            $bumpLevel = 'minor';
        }
    }

    $release = "stg";

    // Bump it
    switch ($bumpLevel) {
        case 'major':
            $newVersion->incrementMajor();
            $release = "prod";
            break;
        case 'minor':
            $newVersion->incrementMinor();
            $release = "prod";
            break;
        case 'patch':
            $newVersion->incrementPatch();
            $release = "prod";
    }

    file_put_contents("./environment", $release);
//    exec("echo $release > ./environment 2>&1");
    if(!$newVersion->eq(new SemVer\Version($lastRelease))) {
        tagRepo($newVersion);
    }
    // Increment build number if necessary
    // $buildNumber = (int) $latestVersion->build ?: 0;
    // $newVersion->setPreRelease('rc')->setBuild((string) ++$buildNumber);

    return $newVersion;
}

/**
 * Push tag of the latest version
 *
 * @param SemVer\Version $version
 */
function tagRepo(SemVer\Version $version) {

    // $repoUrl = getenv("CI_REPOSITORY_URL");
//    $pushUrl = preg_replace("~.+@([^/]+)\/~", "git@$1:", $repoUrl);
    // TODO: use a client to execute the below commands
    // exec("git remote set-url origin {$pushUrl}");
    try {
        exec("git tag {$version}");
        exec("git push origin {$version}");
    } catch (Exception $ex) {

    }

}
