# Mintchat: Click to Chat

Mintchat is a lightweight, cookie-free click-to-chat button block for WhatsApp. Configure contacts once, then choose the right contact and pre-filled message for every button.

The frontend is a plain link: no JavaScript, cookies, browser storage, analytics, tracking or external request before the visitor chooses to open WhatsApp.

## Features

- Native block placed exactly where you want it (no floating widget)
- Multiple centrally managed contacts, such as Sales, Support and Bookings, with a default contact
- Per-button contact and pre-filled message, or the contact default message
- Per-page message override in the document settings sidebar, on any post type with custom fields
- Fill (WhatsApp green), Outline and Theme block styles
- Full design controls: color, typography including letter spacing and uppercase, spacing, border, shadow, alignment
- Works inside the core Buttons block, next to regular buttons
- One number change updates every button of that contact, without resaving posts
- Zero frontend JavaScript and one small local stylesheet

Mintchat is a contact button, not a live chat: no popups, chatbots, CRM, automated messages or analytics.

## Privacy

Before a click, Mintchat sends no request anywhere, sets no cookie and stores nothing in the browser. After a click the visitor follows a `https://wa.me/` link on purpose; the number and optional message are part of that link and visible in the page source. WhatsApp's own terms and privacy policy then apply.

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

`npm run plugin-zip` builds `mintchat.zip` with a single `mintchat/` directory, without development files. CI lints, checks that `build/` matches the sources, runs the integration tests on WordPress and runs Plugin Check on the ZIP.

WordPress.org listing assets (banner, icon, screenshots) live in `.wordpress-org/`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## Trademarks

Mintchat is an independent project. It is not affiliated with, endorsed by or sponsored by WhatsApp LLC or Meta Platforms, Inc. WhatsApp is named only to describe where the button leads.
