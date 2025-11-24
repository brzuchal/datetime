# Tzdata Configuration

## Custom Tzdata Path via composer.json

You can configure a custom timezone data path in your project's `composer.json`:

```json
{
  "extra": {
    "brzuchal/datetime": {
      "tzdata-path": "path/to/custom/tzdata"
    }
  }
}
```

### Path Resolution

- **Absolute paths** (starting with `/`): Used as-is
- **Relative paths**: Resolved relative to `composer.json` location

### Examples

**Use project-specific tzdata:**
```json
{
  "extra": {
    "brzuchal/datetime": {
      "tzdata-path": "resources/tzdata"
    }
  }
}
```

**Use absolute path:**
```json
{
  "extra": {
    "brzuchal/datetime": {
      "tzdata-path": "/opt/tzdata"
    }
  }
}
```

### Priority

The configuration is loaded automatically on first use. Priority order:

1. **Runtime override** via `ZoneRulesProvider::setCustomTzDataPath()` (highest)
2. **composer.json** configuration via `extra.brzuchal/datetime.tzdata-path`
3. **System zoneinfo** (`/usr/share/zoneinfo`)
4. **Bundled data** (`vendor/composer/tzdata/`) (lowest)

### Use Cases

- **Docker containers**: Point to a mounted volume with timezone data
- **Custom timezone data**: Use modified or extended timezone databases
- **Shared tzdata**: Multiple projects sharing the same timezone data
- **Testing**: Use fixture timezone data for tests
