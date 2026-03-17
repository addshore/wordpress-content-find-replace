=== Content Find Replace ===
Contributors: addshore
Tags: find, replace, regex, content, migration
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unlimited content find/replace rules with regex support, Wikimedia thumbnail rewrite preset, migration preview, and rollback snapshots.

== Description ==

Content Find Replace provides:

* Unlimited rule-based find/replace for post content.
* Plain text or regex matching.
* Per-rule controls for ignore-case, admin behavior, and post type targeting.
* Built-in Wikimedia preset to round thumbnail sizes up to supported Wikimedia standards.
* Safe migration workflow: preview rewrites before applying, then rollback by run ID if needed.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via the Plugins screen.
2. Activate the plugin.
3. Open **Tools → Content Find Replace**.
4. Add custom rules and/or click **Install Wikimedia Preset Rule**.
5. Save settings.
6. (Optional) Use the migration section to preview and apply DB rewrites.

== Frequently Asked Questions ==

= Is regex required? =
No. Each rule can use plain text replacement or regex.

= What happens with invalid regex patterns? =
Invalid regex rules are skipped at runtime and warnings are shown to administrators.

= Can I undo a migration? =
Yes. Each applied migration run stores snapshots and can be rolled back from the Tools page.

== Screenshots ==

1. Rules management UI under Tools.
2. Migration preview table.
3. Rollback run list.

== Changelog ==

= 0.1.0 =
* Initial release.
* Unlimited rules with regex/plain modes.
* Wikimedia preset support.
* Migration preview, apply, and rollback.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
