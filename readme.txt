=== Room Calendars ===
Contributors: Sebastian Kedzior
Tags: calendar, ics, fullcalendar, rooms, availability, responsive
Stable tag: 1.1.19
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Room Calendars is a simple yet powerful plugin that lets you display multiple ICS-based room calendars in a responsive list view with interactive filters.  Each room’s availability is loaded from an external .ics feed and color-coded.

== Description ==
Room Calendars enables you to:
- Add unlimited room calendars. Configure room names and ICS URLs in Settings → Room Calendars.
- Responsive list view, optimized for both desktop and mobile.
- Color-coded events. Each room has a unique dot color for quick identification.
- Interactive filters. Click room names above the calendar to show/hide events from those feeds.
- Multi-room event merging. Events present in multiple room calendars appear only once, labeled with all relevant room names.
- Polish localization. Full UI translation for Polish (pl locale).

== Installation ==

- Upload the room-calendars folder to the /wp-content/plugins/ directory.
- Activate the plugin through the ‘Plugins’ menu in WordPress.
- Go to Settings → Room Calendars.
- Add each room’s Name and ICS URL, then save.
- Insert the shortcode [room_calendars] into any page or post to display the calendar interface.