# Changelog

## 1.1.0

- Optional contact modal, opened by the new Modal Trigger block: title, description, WhatsApp action (a Chat Button, so it keeps the Fill, Outline and Theme styles and AA contrast), email, phone, address and a lazy OpenStreetMap map.
- Modal settings in Settings > Mintchat, defined once and validated on read and save; modal colors and sizes are CSS custom properties that default to the theme palette.
- Accessible dialog: focus trap, Escape to close, focus returns to the trigger, aria-expanded on every trigger.
- Settings: unused empty contact rows are ignored on save; the default message field of a new row is now saved.

## 1.0.0

- First release of Mintchat: Click to Chat.
- Centrally managed WhatsApp contacts with a default contact and default messages.
- Per-button contact and pre-filled message; per-page message override on any post type.
- Fill, Outline and Theme block styles; full color, typography (including letter spacing and uppercase), spacing, border and shadow controls.
- The button can sit inside the core Buttons block.
- Dynamic PHP rendering, zero frontend JavaScript, no cookies or tracking.
- The icon is a single CSS mask in the stylesheet, so each button adds about 500 bytes of markup.
- Validation, escaping, accessibility details, integration tests, CI with Plugin Check and a verified production ZIP.
