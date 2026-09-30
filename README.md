<p align="center">
  <img src=".wordpress-org/banner-1544x500.png" alt="Mintchat: click to chat on WhatsApp. Lightweight, no cookies, no tracking." width="100%">
</p>

<p align="center">
  <img src=".wordpress-org/icon-128x128.png" alt="" width="64" height="64"><br>
  <strong>Mintchat: Click to Chat</strong><br>
  A native WordPress block that opens a WhatsApp chat with the right contact and a pre-filled message.<br>
  No JavaScript, no cookies, no tracking.
</p>

<p align="center">
  <img alt="WordPress 7.0+" src="https://img.shields.io/badge/WordPress-7.0%2B-21759b?logo=wordpress&logoColor=white">
  <img alt="PHP 7.4+" src="https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white">
  <img alt="Frontend JS: 0 KB" src="https://img.shields.io/badge/frontend%20JS-0%20KB-25d366">
  <img alt="Cookies: none" src="https://img.shields.io/badge/cookies-none-25d366">
  <img alt="License: GPL-2.0-or-later" src="https://img.shields.io/badge/license-GPL--2.0--or--later-blue">
</p>

---

Configure your contacts once (Sales, Support, Bookings...), then drop a button wherever you want it. Each button picks a contact and a message. Change a number in one place and every button follows, without resaving a single post.

The button is a plain `https://wa.me/` link. Nothing loads, runs or gets stored in the browser until the visitor decides to click.

## Screenshots

| Block editor | Contacts |
| --- | --- |
| ![The Mintchat button in the block editor, with contact, message, text, icon and new tab settings in the sidebar](.wordpress-org/screenshot-1.png) | ![Settings > Mintchat with three contacts, their numbers and default messages](.wordpress-org/screenshot-2.png) |

| Fill, Outline and Theme styles | Inside a Buttons block |
| --- | --- |
| ![Three buttons on the frontend: green Fill, bordered Outline and the theme's own button style](.wordpress-org/screenshot-3.png) | ![A regular button and a Mintchat button side by side in a core Buttons block](.wordpress-org/screenshot-4.png) |

## Features

- **A block, not a widget.** Place it in content, headers, footers or next to other buttons in a core Buttons block. No floating bubble.
- **Central contacts.** Several contacts with names, numbers and default messages, plus a site-wide default.
- **The right message.** Per button: the contact's default message or a custom one. Per page: an override in the document sidebar, on any post type with custom fields.
- **Three styles.** Fill (WhatsApp green), Outline and Theme (your theme's own button look).
- **All the native design tools.** Color, typography with letter spacing and uppercase, spacing, border, radius, shadow and alignment.
- **Safe by default.** Missing or deleted contacts hide the button instead of linking to a wrong number. Output is escaped, new tabs get `rel="noopener noreferrer"`.
- **Clean uninstall.** Removes its option and the per-page messages, leaves your content untouched.

## How light is it

Measured on WordPress 7.1 with Twenty Twenty-Five:

| | Size |
| --- | --- |
| Frontend JavaScript | **0 bytes** |
| Frontend requests | **0** (WordPress inlines the stylesheet; block themes add it only to pages that use the block) |
| Stylesheet, icon included | 2.6 KB, 1.1 KB gzipped, once per page |
| Markup per button | about 500 bytes, 0.3 KB gzipped |
| Cookies, local storage, external calls | none |
| Release ZIP | 23 KB, 16 files, 53 KB unpacked |

The WhatsApp glyph (from WordPress Core social icons) lives once in the stylesheet as a CSS mask and takes the button's text color, so ten buttons cost the same icon bytes as one.

## Privacy

Before a click Mintchat sends no request, sets no cookie and stores nothing in the browser. After a click the visitor follows a `wa.me` link on purpose. The number and the optional message are part of that link and visible in the page source. WhatsApp's own terms and privacy policy apply from there.

Mintchat is a contact button, not a live chat: no popups, chatbots, CRM, automated messages or analytics.

## Usage

1. Add contacts under **Settings > Mintchat** and choose the default.
2. Insert **Mintchat: Click to Chat**, on its own or inside a Buttons block.
3. Pick the contact, use its default message or write one, choose a style.

## Development

Runtime: WordPress 7.0+ and PHP 7.4+. Node.js 22+ is only needed for development.

```sh
npm ci
npm run lint:js
npm run lint:css
npm run build
npm run plugin-zip
wp eval-file wp-content/plugins/mintchat/tests/integration.php
```

`npm run plugin-zip` builds `mintchat.zip` with a single `mintchat/` directory and no development files. CI lints, checks that `build/` matches the sources, runs Plugin Check on the ZIP and runs the integration tests on PHP 7.4 and 8.4 with `@wordpress/env`. See [TESTING.md](TESTING.md).

WordPress.org listing assets (banner, icon, screenshots) live in `.wordpress-org/`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## Trademarks

Mintchat is an independent project. It is not affiliated with, endorsed by or sponsored by WhatsApp LLC or Meta Platforms, Inc. WhatsApp is named only to describe where the button leads.
