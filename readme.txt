=== Mintchat: Click to Chat ===
Contributors: massimomazzariol
Tags: whatsapp, click to chat, contact button, privacy, block
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A cookie-free click-to-chat button block for WhatsApp: multiple contacts, a pre-filled message per button, zero frontend JavaScript.

== Description ==

Configure your WhatsApp contacts once, then place a chat button exactly where it makes sense, with the right contact and message for that page.

Mintchat adds a native block editor button that opens a WhatsApp chat. Administrators manage the numbers centrally; editors pick the contact and the pre-filled message for each button. Change a number later and every button assigned to that contact follows, without resaving any post.

For example, a hotel can set up Reception, Restaurant and Transfers, and point each page's button to the right team with a message like "Hello, I would like to book a table".

= Features =

* Native block, placed where you want it (no floating widget)
* Multiple contacts managed in Settings > Mintchat, with a default contact
* Per-button contact and pre-filled message, or the contact's default message
* Per-page message override from the document settings sidebar, on any post type
* Three styles: Fill (WhatsApp green), Outline and Theme (your theme's button look)
* Full design controls: colors, typography including letter spacing and uppercase, spacing, border, shadow
* Fits inside the core Buttons block, next to your other buttons
* Optional contact modal: one accessible dialog with the WhatsApp button, email, phone, address and an OpenStreetMap map, opened by the Modal Trigger block
* Zero frontend JavaScript (a small deferred script only while the optional modal is enabled) and zero requests: a 1 KB gzipped stylesheet, inlined by WordPress (block themes load it only where the block is used), and about 500 bytes of markup per button
* No cookies, browser storage, analytics, telemetry or tracking
* No account, chat SDK or WhatsApp Business API needed
* Ready for AI agents: list contacts and add buttons through the WordPress Abilities API (REST and MCP)

Mintchat is a contact button, not a live chat. It does not add chatbots, CRM features, automated messages or conversation analytics.

= Privacy =

Before a click, Mintchat sends no request to WhatsApp or anybody else, sets no cookie and stores nothing in the browser. The button is a plain link.

After a click, the visitor follows a `https://wa.me/` link on purpose. The number and the optional message are part of that link and visible in the page source. WhatsApp's own terms and privacy policy then apply. Mintchat never sends messages by itself.

= Trademark notice =

Mintchat is an independent project. It is not affiliated with, endorsed by or sponsored by WhatsApp LLC or Meta Platforms, Inc. WhatsApp is named only to describe where the button leads.

== Installation ==

1. Install and activate Mintchat.
2. Open Settings > Mintchat, add your contacts and choose the default one.
3. Edit a page, insert the "Mintchat: Click to Chat" block (on its own or inside a Buttons block).
4. Pick a contact, write the message if you want one, choose a style and publish.

== Frequently Asked Questions ==

= What number format is required? =

An international number with the country code. A single leading + is optional. Spaces, parentheses, dots and hyphens are removed. Letters, extensions, repeated plus signs, a leading 00 and numbers outside 7 to 15 digits are rejected.

= Does Mintchat send messages? =

No. It opens a WhatsApp chat with the message already typed; the visitor decides whether to send it.

= Does it need the WhatsApp Business API? =

No. It creates ordinary `wa.me` links.

= Will it match my theme? =

Choose the Theme style to reuse your theme's button design, or Outline, or keep the WhatsApp green Fill. Every design control of the block editor is available on top of that.

= What happens when a contact is deleted? =

Buttons assigned to that contact show nothing, so a visitor is never routed to the wrong person. Buttons using the default contact follow the current default.

= Can AI agents use Mintchat? =

Yes. Mintchat registers two abilities on the WordPress Abilities API: mintchat/list-contacts (read-only) and mintchat/add-chat-button. Agents reach them through the REST API or an MCP server such as the WordPress MCP Adapter, with the permissions of the logged-in user. Phone numbers are never returned.

= How does the contact modal work? =

Enable it under Settings > Mintchat, fill in the texts and contact details, then insert the Mintchat: Modal Trigger block where visitors should open it (for example in the header). The dialog is rendered once in the footer, traps focus, closes with Escape and returns focus to the trigger. The map loads from OpenStreetMap only when the dialog is opened.

= What is removed on uninstall? =

The Mintchat settings and the per-page messages. The content of your posts is left untouched.

== Screenshots ==

1. The Mintchat button in the block editor with its contact and message settings.
2. Contacts in Settings > Mintchat.
3. Fill, Outline and Theme styles.
4. A Mintchat button next to a regular button inside a Buttons block.

== Changelog ==

= 1.2.0 =
* New mintchat_message filter: translation plugins and sites can change the pre-filled message (e.g. into the page language).
* With LangSail, one default message field per site language in Settings > Mintchat.

= 1.1.0 =
* Optional contact modal with the Modal Trigger block: WhatsApp button, email, phone, address and an OpenStreetMap map, settings in Settings > Mintchat.
* Unused empty contact rows are ignored on save; new rows keep their default message field name.

= 1.0.0 =
* First release: centrally managed WhatsApp contacts, per-button contact and message, per-page message override, Fill, Outline and Theme styles, full design controls, support inside the Buttons block, zero frontend JavaScript and no cookies, abilities for AI agents (Abilities API).
