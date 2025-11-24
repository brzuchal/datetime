#!/usr/bin/env php
<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Bundler;

use PharData;
use Throwable;

/**
 * TzData Bundler
 *
 * Automatically downloads and bundles IANA timezone data if system zoneinfo is unavailable.
 * Runs on composer post-install and post-update.
 *
 * @internal
 */
// phpcs:ignore
final class TzdataBundler
{
    private const string TZDATA_URL = 'https://data.iana.org/time-zones/releases/tzdata-latest.tar.gz';
    private const string DEFAULT_BUNDLE_DIR = __DIR__ . '/../../vendor/composer/tzdata';
    private const string SYSTEM_ZONEINFO = '/usr/share/zoneinfo';

    private string $bundleDir;

    public function __construct()
    {
        $this->bundleDir = $this->getBundleDir();
    }

    public function run(): int
    {
        $this->logMessage('=== Tzdata Bundler ===');

        // Check if system zoneinfo is available
        if ($this->checkSystemZoneinfo()) {
            $this->logMessage('System zoneinfo available - bundling skipped');

            return 0;
        }

        // Check if we already have bundled data
        if ($this->checkBundledTzdata()) {
            $this->logMessage('Bundled tzdata already available');

            return 0;
        }

        // Download tzdata
        $tarFile = $this->downloadTzdata();
        if ($tarFile === false) {
            $this->logMessage('Failed to download tzdata - library may not work on this system', 'ERROR');

            return 1;
        }

        // Extract and compile
        if (!$this->extractTzdata($tarFile)) {
            $this->logMessage('Failed to extract tzdata', 'ERROR');
            unlink($tarFile);

            return 1;
        }

        // Cleanup
        unlink($tarFile);

        $this->logMessage('Tzdata bundling complete!');

        return 0;
    }

    private function getBundleDir(): string
    {
        // Try to find composer.json
        $composerPath = $this->findComposerJson();

        if ($composerPath !== null) {
            $composerData = @json_decode(file_get_contents($composerPath), true);

            if (is_array($composerData) && isset($composerData['extra']['brzuchal/datetime']['tzdata-path'])) {
                $path = $composerData['extra']['brzuchal/datetime']['tzdata-path'];

                if (is_string($path) && $path !== '') {
                    // Resolve relative paths from composer.json directory
                    if (!str_starts_with($path, '/')) {
                        $path = dirname($composerPath) . '/' . $path;
                    }

                    return $path;
                }
            }
        }

        return self::DEFAULT_BUNDLE_DIR;
    }

    private function findComposerJson(): string|null
    {
        // Start from script directory and walk up
        $dir = __DIR__;

        for ($i = 0; $i < 10; $i++) {
            $composerPath = $dir . '/composer.json';

            if (file_exists($composerPath)) {
                return $composerPath;
            }

            $parentDir = dirname($dir);

            if ($parentDir === $dir) {
                break; // Reached filesystem root
            }

            $dir = $parentDir;
        }

        return null;
    }

    private function logMessage(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        echo sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);
    }

    private function checkSystemZoneinfo(): bool
    {
        // Check if common timezone file exists and is readable
        $testZone = self::SYSTEM_ZONEINFO . '/Europe/Warsaw';

        if (!file_exists($testZone)) {
            $this->logMessage('System zoneinfo not found at ' . self::SYSTEM_ZONEINFO, 'WARN');

            return false;
        }

        if (!is_readable($testZone)) {
            $this->logMessage('System zoneinfo not readable', 'WARN');

            return false;
        }

        // Verify it's a valid tzif file (starts with "TZif")
        $handle = fopen($testZone, 'rb');
        if ($handle === false) {
            return false;
        }

        $magic = fread($handle, 4);
        fclose($handle);

        if ($magic !== 'TZif') {
            $this->logMessage('System zoneinfo file has invalid format', 'WARN');

            return false;
        }

        $this->logMessage('System zoneinfo available and valid');

        return true;
    }

    private function checkBundledTzdata(): bool
    {
        $versionFile = $this->bundleDir . '/version.txt';

        if (!file_exists($versionFile)) {
            return false;
        }

        $version = trim(file_get_contents($versionFile));
        $this->logMessage(sprintf('Bundled tzdata version: %s', $version));

        return true;
    }

    private function downloadTzdata(): string|false
    {
        $this->logMessage('Downloading latest tzdata from IANA...');

        $tmpFile = sys_get_temp_dir() . '/tzdata-latest.tar.gz';

        // Use file_get_contents with HTTP context
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'PHP Tzdata Bundler',
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents(self::TZDATA_URL, false, $context);

        if ($data === false) {
            $this->logMessage('Download failed', 'ERROR');

            return false;
        }

        if (file_put_contents($tmpFile, $data) === false) {
            $this->logMessage('Failed to write downloaded file', 'ERROR');

            return false;
        }

        $this->logMessage(sprintf('Download complete: %s bytes', number_format(strlen($data))));

        return $tmpFile;
    }

    private function extractTzdata(string $tarFile): bool
    {
        $this->logMessage('Extracting tzdata...');

        // Ensure bundle directory exists
        if (!is_dir($this->bundleDir)) {
            mkdir($this->bundleDir, 0755, true);
        }

        // Check if PharData is available
        if (!class_exists('PharData')) {
            $this->logMessage('PharData not available, using system tar command', 'WARN');

            return $this->extractWithTar($tarFile);
        }

        // Use PharData to extract
        try {
            $phar = new PharData($tarFile);

            // Extract only zone files (skip source, docs, etc.)
            $zoneDirectories = [
                'africa',
                'antarctica',
                'asia',
                'australasia',
                'europe',
                'northamerica',
                'southamerica',
                'etcetera',
                'backward',
            ];

            $extractedCount = 0;

            foreach ($phar as $file) {
                $filename = $file->getFilename();

                // Extract zone data files and version
                if ($filename !== 'version' && !in_array(strtolower($filename), $zoneDirectories, true)) {
                    continue;
                }

                $targetPath = $this->bundleDir . '/' . $filename;

                // Create parent directory if needed
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                copy($file->getPathname(), $targetPath);
                $extractedCount++;
            }

            $this->logMessage(sprintf('Extracted %s files', $extractedCount));

            // Compile zones using zic if available
            if (!$this->compileZones()) {
                $this->logMessage('Zone compilation skipped (zic not available)', 'WARN');

                return false;
            }

            $this->logMessage('Timezone database compiled successfully');

            return true;
        } catch (Throwable $e) {
            $this->logMessage('Extraction failed: ' . $e->getMessage(), 'ERROR');

            return false;
        }
    }

    private function extractWithTar(string $tarFile): bool
    {
        // Check if tar command is available
        $tarPath = trim(shell_exec('which tar 2>/dev/null') ?? '');

        if ($tarPath === '') {
            $this->logMessage('Neither PharData nor tar command available', 'ERROR');

            return false;
        }

        // Extract using tar
        $cmd = sprintf(
            // phpcs:ignore
            '%s -xzf %s -C %s africa antarctica asia australasia europe northamerica southamerica etcetera backward version 2>&1',
            escapeshellarg($tarPath),
            escapeshellarg($tarFile),
            escapeshellarg($this->bundleDir),
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logMessage('Tar extraction failed: ' . implode("\n", $output), 'ERROR');

            return false;
        }

        $this->logMessage('Extracted timezone source files using tar');

        // Compile zones
        if (!$this->compileZones()) {
            $this->logMessage('Zone compilation skipped (zic not available)', 'WARN');

            return false;
        }

        $this->logMessage('Timezone database compiled successfully');

        return true;
    }

    private function compileZones(): bool
    {
        // Check if zic (zone information compiler) is available
        $zicPath = trim(shell_exec('which zic 2>/dev/null') ?? '');

        if ($zicPath === '') {
            return false;
        }

        $sourceFiles = [
            'africa',
            'antarctica',
            'asia',
            'australasia',
            'europe',
            'northamerica',
            'southamerica',
            'etcetera',
            'backward',
        ];

        foreach ($sourceFiles as $file) {
            $sourcePath = $this->bundleDir . '/' . $file;

            if (!file_exists($sourcePath)) {
                continue;
            }

            // Compile zone file
            $cmd = sprintf(
                '%s -d %s %s 2>&1',
                escapeshellarg($zicPath),
                escapeshellarg($this->bundleDir),
                escapeshellarg($sourcePath),
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0) {
                continue;
            }

            $this->logMessage(sprintf(
                'Failed to compile %s: %s',
                $file,
                implode("\n", $output),
            ), 'WARN');
        }

        // Read and save version
        $versionFile = $this->bundleDir . '/version';
        if (file_exists($versionFile)) {
            $version = trim(file_get_contents($versionFile));
            file_put_contents($this->bundleDir . '/version.txt', $version);
            unlink($versionFile);
        }

        // Clean up source files
        foreach ($sourceFiles as $file) {
            $path = $this->bundleDir . '/' . $file;
            if (!file_exists($path)) {
                continue;
            }

            unlink($path);
        }

        return true;
    }
}

// Run bundler
exit(new TzdataBundler()->run());
