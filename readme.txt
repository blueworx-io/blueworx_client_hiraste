=== BlueWorx Hiraste Enhancements ===
Contributors: blueworx
Tags: elementor, search, property, filters
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.3.14
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds BlueWorx Hiraste property search enhancements for Elementor and WordPress.

== Description ==

BlueWorx Hiraste Enhancements adds Elementor widgets for property search, filtered property results, and related property items.

Current enhancements include:

* Elementor property search form.
* Destination, collection, and amenity filters.
* Guest capacity filtering.
* Multi-unit search matching.
* Enhanced property results widget using an Elementor Loop Item template.
* Property related items widget for reviews and units with grid and carousel layouts.
* Property amenities widget using an Elementor Loop Item template with amenity icons.
* Active Enhancements admin information page.

== Installation ==

1. Upload the plugin folder to the WordPress plugins directory.
2. Activate BlueWorx Hiraste Enhancements in WordPress admin.
3. Add the Search Form widget in Elementor.

== Changelog ==

= 0.3.14 =
* Improved unit carousel looping so cards rotate continuously instead of snapping back.

= 0.3.13 =
* Added endless circular looping for unit carousels.

= 0.3.12 =
* Added caching for property unit filter matching.
* Removed the unused public search form nonce field.

= 0.3.11 =
* Hid empty destination and collection terms in Elementor Loop Grid taxonomy queries.

= 0.3.10 =
* Set Property Amenities icon images to 50px by 50px.

= 0.3.9 =
* Fixed Property Amenities titles inside archive result loop items.
* Added 10px gap and desktop/tablet left alignment for amenity image boxes.

= 0.3.8 =
* Added separate desktop, tablet, and mobile column controls with a 4-column option.
* Added custom amenity image box wrapper class and responsive alignment.

= 0.3.7 =
* Added an amenities limit setting.
* Improved amenity icon output when the Elementor image box template has no image markup.

= 0.3.6 =
* Added amenity icon support from the ACF featured_image field.
* Added title colour control for amenity image box titles.

= 0.3.5 =
* Removed amenities from the Property Related Items widget.
* Added a separate Property Amenities widget for assigned amenities in a loop grid.
* Updated related item carousels so they auto-rotate even when arrows and dots are hidden.

= 0.3.4 =
* Updated amenities in the Property Related Items widget to use the selected Elementor template while showing the correct amenity names.

= 0.3.3 =
* Added grid/carousel layout option to the Property Related Items widget.
* Added carousel arrows, dots, looping, and visible slide controls.

= 0.3.2 =
* Updated amenities in the Property Related Items widget to render directly from assigned taxonomy terms.

= 0.3.1 =
* Added Property Related Items Elementor widget.
* Added related reviews, units, and amenities display support.

= 0.3.0 =
* Added Enhanced Property Results Elementor widget.
* Added grid column, spacing, pagination, and no-results controls.

= 0.2.9 =
* Updated text domain format to use hyphens.
* Updated tested WordPress version.
* Cleaned search form request handling for plugin checker compliance.

= 0.2.8 =
* Added WordPress.org plugin header details.
* Updated text domain to match the plugin slug.
* Added readme.txt.

= 0.2.7 =
* Added Active Enhancements admin page.
* Improved guest and unit search filtering.
