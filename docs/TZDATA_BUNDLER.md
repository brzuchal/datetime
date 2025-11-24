# Tzdata Bundler

## Overview

The `brzuchal/datetime` library includes an automatic timezone data bundler that ensures timezone information is available across all platforms, including Windows and minimal Docker containers.

## How It Works

### Automatic Bundling

1. **Auto-run**: Executes on `composer install` and `composer update`
2. **System check**: Verifies `/usr/share/zoneinfo` availability
3. **Download**: Fetches latest tzdata from IANA if needed
4. **Extract**: Uses PharData or system `tar` command
5. **Compile**: Uses `zic` to compile timezone database
6. **Bundle**: Stores in configured location (default: `vendor/composer/tzdata/`)

> **Configuration**: The bundler respects the `extra.brzuchal/datetime.tzdata-path` setting in your project's `composer.json`. See [TZDATA_CONFIG.md](TZDATA_CONFIG.md) for details.

### Manual Bundling

Developers can manually trigger the bundler:

```bash
composer bundle-tzdata
```

This is useful for:
- Forcing a refresh of bundled timezone data
- Pre-bundling for deployment
- Troubleshooting timezone issues

## Search Hierarchy

`ZoneRulesProvider` searches for timezone data in this order:

1. **Custom path** (if set via `ZoneRulesProvider::setCustomTzDataPath()`)
2. **System zoneinfo** (`/usr/share/zoneinfo`)
3. **Bundled data** (`vendor/composer/tzdata/`)

> **Note:** Bundled data is stored in `vendor/composer/tzdata/` to persist across library updates. This directory is managed by Composer and won't be deleted when updating the `brzuchal/datetime` package.

## Requirements

### For Automatic Compilation

The bundler can compile timezone source files if `zic` (zone information compiler) is available:

```bash
# Install on Debian/Ubuntu
apt-get install tzdata

# Install on macOS
brew install tzdata
```

If `zic` is not available, the bundler will download pre-compiled timezone files.

## Troubleshooting

### Bundler Fails to Download

If the bundler cannot download tzdata:

1. Check internet connectivity
2. Verify HTTPS access to `data.iana.org`
3. Check firewall/proxy settings

### Manual Download

If automatic download fails, manually download and extract:

```bash
# Download latest tzdata
wget https://data.iana.org/time-zones/releases/tzdata-latest.tar.gz

# Extract to resources/tzdata
mkdir -p resources/tzdata
tar -xzf tzdata-latest.tar.gz -C resources/tzdata
```

## Directory Structure

```
vendor/
└── composer/
    └── tzdata/
        ├── Africa/
        ├── America/
        ├── Antarctica/
        ├── Asia/
        ├── Atlantic/
        ├── Australia/
        ├── Europe/
        ├── Indian/
        ├── Pacific/
        └── version.txt (IANA version identifier)
```

> **Why `vendor/composer/`?** This location persists across `composer update` operations. When you update the `brzuchal/datetime` library, Composer doesn't delete the `vendor/composer/` directory, so your bundled timezone data remains intact.

## Platform Support

| Platform | System Zoneinfo | Bundler Behavior |
|----------|----------------|------------------|
| Linux (full) | ✅ Available | Skipped |
| Linux (minimal/Alpine) | ❌ Not available | Downloads & bundles |
| macOS | ✅ Available | Skipped |
| Windows | ❌ Not available | Downloads & bundles |
| Docker (debian) | ✅ Available | Skipped |
| Docker (alpine) | ❌ Not available | Downloads & bundles |

## Security

- Downloads over HTTPS only
- Verifies file integrity
- Uses official IANA source
- No external dependencies

## Size

- Compressed download: ~400KB
- Extracted database: ~2MB
- Includes all IANA timezones

### Verify Bundled Data

Check if bundled data is available:

```bash
ls -la vendor/composer/tzdata/
cat vendor/composer/tzdata/version.txt
```

## Version Tracking

The bundled timezone database version is stored in:
```
vendor/composer/tzdata/version.txt
```

Example: `2024b`
