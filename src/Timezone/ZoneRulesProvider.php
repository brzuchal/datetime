<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

use Brzuchal\DateTime\InvalidTimezone;

/**
 * Provider for timezone rules loaded from Tzif files.
 *
 * @internal This class is not part of the public API.
 */
final class ZoneRulesProvider
{
    /** @var array<string, ZoneRules> */
    private static array $cache = [];

    private static string|null $customTzDataPath = null;
    private static bool $configLoaded = false;

    /**
     * Get ZoneRules for a given IANA timezone identifier.
     *
     * @throws InvalidTimezone If the timezone is not found.
     */
    public static function getRules(string $zoneId): ZoneRules
    {
        // Load config from composer.json on first use
        if (!self::$configLoaded) {
            self::loadConfig();
            self::$configLoaded = true;
        }

        if (isset(self::$cache[$zoneId])) {
            return self::$cache[$zoneId];
        }

        $tzifPath = self::findTzifFile($zoneId);
        if ($tzifPath === null) {
            throw new InvalidTimezone(sprintf('Unknown timezone: %s', $zoneId));
        }

        // Parse tzif file and create ZoneRules
        $tzif = new TzifParser()->parseFile($tzifPath);

        // Determine cutoff timestamp (last transition in tzif)
        $cutoffTimestamp = 0;
        if (!empty($tzif->transitions)) {
            $lastTransition = $tzif->transitions[\count($tzif->transitions) - 1];
            $cutoffTimestamp = (int) $lastTransition->timestamp;
        }

        // Get POSIX rule for future transitions
        $posixRule = $tzif->posixString ?? '';

        return self::$cache[$zoneId] = new ZoneRules(
            zoneId: $zoneId,
            cutoffTimestamp: $cutoffTimestamp,
            posixRule: $posixRule,
            tzifPath: $tzifPath,
        );
    }

    /**
     * Find a Tzif file for a zone ID.
     *
     * Search hierarchy:
     * 1. Custom tzdata path (if set)
     * 2. System /usr/share/zoneinfo
     * 3. Bundled vendor/composer/tzdata (if it exists)
     */
    private static function findTzifFile(string $zoneId): string|null
    {
        $candidates = [];

        // 1. Custom path
        if (self::$customTzDataPath !== null) {
            $candidates[] = self::$customTzDataPath . '/' . $zoneId;
        }

        // 2. System zoneinfo
        $candidates[] = '/usr/share/zoneinfo/' . $zoneId;

        // 3. Bundled in vendor/composer/tzdata
        $vendorPath = \dirname(__DIR__, 2) . '/vendor/composer/tzdata/' . $zoneId;
        if (\file_exists($vendorPath)) {
            $candidates[] = $vendorPath;
        }

        foreach ($candidates as $path) {
            if (\file_exists($path) && \is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Load tzdata path configuration from composer.json.
     */
    private static function loadConfig(): void
    {
        // Try to find composer.json in project root
        $composerPath = self::findComposerJson();

        if ($composerPath === null) {
            return;
        }

        $composerData = \json_decode(\file_get_contents($composerPath), true);

        if (!\is_array($composerData)) {
            return;
        }

        // Check for extra.brzuchal/datetime.tzdata-path
        if (!isset($composerData['extra']['brzuchal/datetime']['tzdata-path'])) {
            return;
        }

        $path = $composerData['extra']['brzuchal/datetime']['tzdata-path'];

        if (!\is_string($path) || $path === '') {
            return;
        }

        // Resolve relative paths from composer.json directory
        if (!\str_starts_with($path, '/')) {
            $path = \dirname($composerPath) . '/' . $path;
        }

        self::$customTzDataPath = $path;
    }

    /**
     * Find composer.json in project root.
     */
    private static function findComposerJson(): string|null
    {
        // Start from this file's directory and walk up
        $dir = __DIR__;

        for ($i = 0; $i < 10; $i++) {
            $composerPath = $dir . '/composer.json';

            if (\file_exists($composerPath)) {
                return $composerPath;
            }

            $parentDir = \dirname($dir);

            if ($parentDir === $dir) {
                break; // Reached filesystem root
            }

            $dir = $parentDir;
        }

        return null;
    }

    /**
     * Set a custom TzData directory path.
     */
    public static function setCustomTzDataPath(string $path): void
    {
        self::$customTzDataPath = $path;
        self::$cache = []; // Clear cache when changing a data source
    }

    /**
     * Get the list of available timezone IDs from system zone info.
     *
     * @return array<int, string>
     */
    public static function getAvailableZoneIds(): array
    {
        $baseDir = '/usr/share/zoneinfo';

        if (!\is_dir($baseDir)) {
            return [];
        }

        $zoneIds = [];
        $skipFiles = ['posixrules', 'tzdata.zi', 'zone.tab', 'zone1970.tab', 'iso3166.tab'];

        // Use glob to find all files recursively
        $pattern = $baseDir . '/{*,*/*,*/*/*,*/*/*/*}';
        $files = \glob($pattern, \GLOB_BRACE);

        if ($files === false) {
            return [];
        }

        foreach ($files as $filePath) {
            if (!\is_file($filePath)) {
                continue;
            }

            $basename = \basename($filePath);
            if (\in_array($basename, $skipFiles, true)) {
                continue;
            }

            // Get relative path
            $relativePath = \substr($filePath, \strlen($baseDir) + 1);
            $zoneIds[] = $relativePath;
        }

        \sort($zoneIds);

        return $zoneIds;
    }

    /**
     * Clear the rules cache.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
