# Validation status for 1.0.0

## Tested environment

- WordPress 7.1.2 in WordPress Studio, PHP 8.4 with SQLite
- Node.js 26 and npm 11

The declared minimums are WordPress 7.0 and PHP 7.4. CI runs the integration tests on PHP 7.4 and 8.4 with `@wordpress/env`.

## Automated results

- `npm run lint:js` and `npm run lint:css`: pass
- `npm run build`: pass, committed `build/` matches the sources
- `npm run plugin-zip`: pass, 17-file archive
- WordPress integration test (`wp eval-file tests/integration.php`): 75 checks pass
- Plugin Check on the extracted ZIP: no errors, no warnings

The integration check covers registration, dynamic rendering, absence of frontend scripts, settings validation, stable IDs, default and explicit contacts, malformed stored options and block attributes, phone normalization, contact default messages, per-block and per-post overrides, multiple blocks, persistence, URL encoding, output escaping, new-tab safety, a decorative icon with no per-button SVG and a markup size ceiling, native style and typography supports, the Outline style class, post meta registration, the two abilities (registration, REST visibility, permissions, no phone numbers in the output, block insertion, unknown contacts, input schema), empty settings, and uninstall behavior (option and per-post messages).

## Continuous integration

`.github/workflows/ci.yml` runs on every push and pull request:

- lint, build, a check that `build/` is up to date, the release ZIP and a PHP syntax check;
- Plugin Check on the extracted release ZIP;
- the integration tests on PHP 7.4 and 8.4.

## MCP end-to-end check

Done with the official WordPress MCP Adapter 0.7.0 on WordPress 7.1.2, speaking MCP (JSON-RPC over the adapter's HTTP transport, protocol 2025-06-18) as a logged-in administrator:

- `initialize` returns the "MCP Adapter Default Server" and a session ID.
- `tools/list` returns the adapter's discover, get-info and execute tools.
- `mcp-adapter-discover-abilities` lists `mintchat/list-contacts` and `mintchat/add-chat-button`.
- `mintchat/list-contacts` through `mcp-adapter-execute-ability` returns IDs, names and the default flag, with no phone numbers.
- `mintchat/add-chat-button` adds an outline button with a custom label and message to a draft page; the page preview renders it with the right `wa.me` link.

## Browser checks

Done in the block editor and on the frontend with:

- Twenty Twenty-Five: Fill, Outline and Theme styles, a button inside a Buttons block, the settings page. These are the WordPress.org screenshots in `.wordpress-org/`.
- A custom block theme (a child of a block theme) using the Outline style and a theme-registered style in the header, footer and content.

## Remaining manual checks

- Mobile and tablet layouts on a real device.
