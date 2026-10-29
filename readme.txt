=== BlueWorx Labs | Hiraste Travel ===
Contributors: blueworx
Tags: elementor, search, property, filters
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.10.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hiraste Travel enhancements for Elementor and WordPress: property search, results, taxonomy ordering, and favourites.

== Description ==

BlueWorx Labs | Hiraste Travel adds Elementor widgets for property search, filtered property results, and related property items.

Current enhancements include:

* Elementor property search form.
* Destination, collection, and amenity filters.
* Guest capacity filtering.
* Multi-unit search matching.
* Enhanced property results widget using an Elementor Loop Item template.
* Property related items widget for reviews and units with grid and carousel layouts.
* Property amenities widget using an Elementor Loop Item template with amenity icons.
* Drag-and-drop taxonomy ordering for destinations, collections, and amenities.
* Property favourites button shortcode saving to the ACF user field.
* Property count heading for search results and archives.
* Unit popup that loads the clicked unit's Elementor template.
* Read more toggle for long text blocks.
* Ad-free property map on search results and property pages.
* One-off fill-in of each unit's Property field from its property.
* Active Enhancements admin information page.
* Automatic updates from GitHub releases.

== Installation ==

1. Upload the plugin folder to the WordPress plugins directory.
2. Activate BlueWorx Labs | Hiraste Travel in WordPress admin.
3. Add the Search Form widget in Elementor.

== Changelog ==

= 0.10.1 =
* The property name box now looks the same as the rest of the search form.

= 0.10.0 =
* Visitors can now search for a property by name. Type all or part of a name into the new box on the search form; it works alongside the other filters and only searches properties.

= 0.9.0 =
* Properties now show their own map with no adverts, using OpenStreetMap. On search results, "View on map" slides the map open under the property. On a property page, add the [property_map] shortcode where the map should go; "View on map" there scrolls down to it.

= 0.8.1 =
* Fixed messy page links on search results.

= 0.8.0 =
* The plugin now updates itself from new releases, like the other BlueWorx plugins.

= 0.7.0 =
* Search results now show in a shuffled order for each search, so no property is always at the top. Paging through results never repeats or skips a property.

= 0.6.0 =
* Units now show which property they belong to, filled in automatically from each property's unit list.

= 0.5.0 =
* Moved the property count, unit popup, and read more features into the plugin (previously code snippets).

= 0.4.0 =
* Renamed plugin to BlueWorx Labs | Hiraste Travel.
* Moved the property favourites button into the plugin (previously a code snippet).
* Favourites CSS and JS now load from plugin assets only on pages using the button.

= 0.3.17 =
* Added drag-and-drop backend ordering for destinations, collections, and amenities.

= 0.3.16 =
* Added an administrator capability repair for property admin access.

= 0.3.15 =
* Fixed search cache refresh when properties or units are deleted.
* Updated taxonomy filters so different filter types narrow results together.
* Changed the search form to start without active guest or unit filters.

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
