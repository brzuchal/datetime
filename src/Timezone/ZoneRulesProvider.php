<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

use Brzuchal\DateTime\InvalidTimezone;

/**
 * Provider for timezone rules loaded from tzif files.
 * 
 * @internal This class is not part of the public API.
 */
final class ZoneRulesProvider
{
    /** @var array<string, ZoneRules> */
    private static array $cache = [];

    private static string|null $customTzDataPath = null;

    /**
     * Get ZoneRules for a given IANA timezone identifier.
     *
     * @throws InvalidTimezone If the timezone is not found
     */
    public static function getRules(string $zoneId): ZoneRules
    {
        if (isset(self::$cache[$zoneId])) {
            return self::$cache[$zoneId];
        }

        $tzifPath = self::findTzifFile($zoneId);
        if ($tzifPath === null) {
            throw new InvalidTimezone("Unknown timezone: {$zoneId}");
        }

        // Parse tzif file and create ZoneRules
        $parser = new TzifParser();
        $tzif = $parser->parseFile($tzifPath);

        // Determine cutoff timestamp (last transition in tzif)
        $cutoffTimestamp = 0;
        if (!empty($tzif->transitions)) {
            $lastTransition = $tzif->transitions[\count($tzif->transitions) - 1];
            $cutoffTimestamp = (int) $lastTransition->timestamp;
        }

        // Get POSIX rule for future transitions
        $posixRule = $tzif->posixString ?? '';

        return $self::$cache[$zoneId] = new ZoneRules(
            zoneId: $zoneId,
            cutoffTimestamp: $cutoffTimestamp,
            posixRule: $posixRule,
            tzifPath: $tzifPath,
        );
    }

    /**
     * Find tzif file for a zone ID.
     * 
     * Search hierarchy:
     * 1. Custom tzdata path (if set)
     * 2. System /usr/share/zoneinfo
     * 3. Bundled resources/tzdata (if exists)
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

        // 3. Bundled resources
        $resourcesPath = \dirname(__DIR__, 2) . '/resources/tzdata/' . $zoneId;
        if (\file_exists($resourcesPath)) {
            $candidates[] = $resourcesPath;
        }

        foreach ($candidates as $path) {
            if (\file_exists($path) && \is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Set a custom tzdata directory path.
     */
    public static function setCustomTzDataPath(string $path): void
    {
        self::$customTzDataPath = $path;
        self::$cache = []; // Clear cache when changing data source
    }

    /**
     * Get list of available timezone IDs from system zoneinfo.
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
