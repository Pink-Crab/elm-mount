# PinkCrab ElmMount #

[![Latest Stable Version](https://poser.pugx.org/pinkcrab/elm-mount/v)](https://packagist.org/packages/pinkcrab/elm-mount)
[![Total Downloads](https://poser.pugx.org/pinkcrab/elm-mount/downloads)](https://packagist.org/packages/pinkcrab/elm-mount)
[![License](https://poser.pugx.org/pinkcrab/elm-mount/license)](https://packagist.org/packages/pinkcrab/elm-mount)
[![PHP Version Require](https://poser.pugx.org/pinkcrab/elm-mount/require/php)](https://packagist.org/packages/pinkcrab/elm-mount)
![GitHub contributors](https://img.shields.io/github/contributors/Pink-Crab/elm-mount?label=Contributors)
![GitHub issues](https://img.shields.io/github/issues-raw/Pink-Crab/elm-mount)

[![WP 6.6 [PHP8.0-8.4] Tests](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_6.yaml/badge.svg)](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_6.yaml)
[![WP 6.7 [PHP8.0-8.4] Tests](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_7.yaml/badge.svg)](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_7.yaml)
[![WP 6.8 [PHP8.0-8.4] Tests](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_8.yaml/badge.svg)](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_8.yaml)
[![WP 6.9 [PHP8.0-8.4] Tests](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_9.yaml/badge.svg)](https://github.com/Pink-Crab/elm-mount/actions/workflows/WP_6_9.yaml)

[![codecov](https://codecov.io/gh/Pink-Crab/elm-mount/graph/badge.svg?token=cWP0sQqG4G)](https://codecov.io/gh/Pink-Crab/elm-mount)

Mount compiled Elm apps into WordPress admin pages, shortcodes and other surfaces. Handles the enqueue, the localized flags blob, and the REST nonce. Bundling your Elm is your job — this package takes the compiled `.js` and wires it into WordPress.

Part of a three-package set:

| Role | Package |
|------|---------|
| PHP mount helper (this package) | [`pinkcrab/elm-mount`](https://github.com/Pink-Crab/elm-mount) |
| JS bootstrap | [`@pinkcrab/elm-wp-bootstrap`](https://github.com/Pink-Crab/elm-wp-bootstrap) |
| Elm package | [`Pink-Crab/elm-wp`](https://github.com/Pink-Crab/elm-wp) |

## Install

```bash
composer require pinkcrab/elm-mount
```

## Usage

Build an `Elm_App` once with your script + flags, then either `render()` (echoes) or `parse()` (returns a string) from wherever WordPress asks you for output. This package does not wrap `add_submenu_page` / `add_shortcode` / `add_meta_box` — use WordPress's own APIs.

```php
use PinkCrab\ElmMount\Elm_App;

$app = Elm_App::create( 'my_settings' )
    ->script( plugin_dir_url( __FILE__ ) . 'build/main.js' )
    ->flags( [
        'pageTitle' => __( 'My Settings', 'td' ),
        'canEdit'   => current_user_can( 'manage_options' ),
    ] );

// Admin page / meta box — echo
add_submenu_page(
    'options-general.php',
    'My Settings',
    'My Settings',
    'manage_options',
    'my-settings',
    fn() => $app->render()
);

// Shortcode — return string
add_shortcode( 'my_app', fn() => $app->parse() );
```

Both `render()` and `parse()` enqueue the script, localize the flags blob on `window.my_settings`, and produce `<div id="my_settings-root"></div>` for Elm to attach to. The mount node id defaults to `{handle}-root`; override with `->mount_node( 'custom-id' )` if needed.

Your compiled Elm bundle reads `window.my_settings` and mounts into `#my_settings-root` — [`@pinkcrab/elm-wp-bootstrap`](https://github.com/Pink-Crab/elm-wp-bootstrap) handles that plumbing automatically.

## Contract

This section is the **authoritative spec** shared by all three packages. Any change here is a contract bump and must be mirrored in the other two repos in lockstep.

### Flags blob

Emitted via `wp_localize_script( $handle, $handle, $blob )`. The JavaScript side reads it from `window.<handle>` and hands it to Elm as flags.

```json
{
  "restRoot":     "https://example.test/wp-json/",
  "restNonce":    "abc123...",
  "restNamespace":"wp/v2",
  "ajaxUrl":      "https://example.test/wp-admin/admin-ajax.php",
  "ajaxNonce":    "def456...",
  "mountNode":    "my_settings-root",
  "locale":       "en_GB",
  "currentUser": {
    "id":          1,
    "displayName": "Glynn Quelch",
    "roles":       ["administrator"],
    "capabilities":["manage_options", "edit_posts"]
  },
  "pluginData": {
    "pageTitle": "My Settings",
    "canEdit":   true
  }
}
```

Notes:
- `restNonce` is minted from the `wp_rest` action and is what `wp.apiFetch` needs.
- `ajaxNonce` is minted from a package-specific action (one per handle) for the legacy `admin-ajax.php` path.
- `pluginData` is the only free-form section — user-supplied flags via `->flags( [...] )`.
- `capabilities` is a UI hint for Elm to disable buttons etc; **never trust it for authorisation** (server-side checks are the real gate).

### Port names

The JS bootstrap and the Elm package must agree on these names. Changing any is a contract break.

| Direction | Port name | Purpose |
|-----------|-----------|---------|
| Elm → JS  | `wpApiFetch`       | Outbound REST call via `wp.apiFetch`. Payload: `{ id, method, path, body? }`. |
| JS → Elm  | `wpApiFetchResult` | Paired response. Payload: `{ id, ok, status, body }`. |
| Elm → JS  | `wpNotice`         | Show an admin notice. Payload: `{ kind: "success"\|"error"\|"info"\|"warning", message }`. |
| Elm → JS  | `copyToClipboard`  | Copy text to clipboard. Payload: `string`. |

`id` on `wpApiFetch` / `wpApiFetchResult` is a string correlation id the Elm side generates so multiple in-flight requests can be matched to their responses.

### Versioning

`elm-mount`, `@pinkcrab/elm-wp-bootstrap` and `Pink-Crab/elm-wp` share a minor version during `0.x`. `0.3.1` of any one package is compatible with `0.3.x` of the other two.

## License

MIT © PinkCrab
