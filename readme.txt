=== WP FEvents Book ===
Tags: events, booking, participants, list, lists, event, events, party, birthday, competition
Contributors: faina09, Arne, Claude, Borisa, bl-solutions
Donate link: http://goo.gl/QzIZZ
Requires at least: 5.2.2
Tested up to: 5.8.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates unlimited number of Events that can be booked by users.

== Description ==
Creates unlimited number of Events that can be booked by users.

Displays - in the same page of the form booking Event - the list of users that booked their partecipation to the Event.

You can use a shortcode to place the booking form anywhere in your posts or articles.

The User must be logged in to be able to book an Event: username is displayed and name+surname is grabbed from WP User Profile.

The Administrators can delete/add/update the Users' bookings.

Try it out on your [free dummy site](http://tastewp.com/new?pre-installed-plugin-slug=WP-Fevents-book&redirect=options-general.php%3Fpage%3Dfeventsbook&ni=true).
The link spins up a new TasteWP instance with the WP FEvents Book plugin already installed.

Works with PHP 5.2.12 or higher, and PHP 7.x: please report any issue you find, and any feature you want. I'll try to fix the firsts and to implement the seconds!

If any bug found please ask me for support!

+++

= Supported languages =
- English
- Italian - it's my native language!
- German - thanks to Arne
- French - thanks to Claude
- Croatian - thanks to Borisa Djuraskovic from www.webhostinghub.com
- Hungarian - thanks to Mladen Blatnik

== Installation ==
1. Unzip and place the 'wp-fevents-book' folder in your 'wp-content/plugins' directory.
2. Activate the plugin.
3. Click the 'WP FEvents Book' link in the WordPress setting menu, configure at least one Event and save (step REQUIRED).
4. Use a shortcode [feventsbook] to display all enabled Events, or [feventsbook eventid=x] to display Event with ID=x.
If you delete an Event, all users and info are lost, but the EventID is NOT removed if it is not the last. May be better just to Disable the Events.

== Frequently Asked Questions ==
= Is it free? =
Yes! The plugin is free.

= The plugin is not working! What can I do? =
Please send me the description of the error, and all the info you can about your configuration (WP, PHP and MySQL versions, configuration details,...). You can use WP plugin support page. I'll be happy to help you!

= What is a 'team list'? =
The 'team list' is an experimental function. I developed it for my athletic team site. Sometimes we need to collect two runners or more for a team for the relay races; may be you can have a similar need. The 'team list' try to help in this, defining a team with a settable number of elements. Sometimes we need to have elements from e.g. number 12 to 18 when we run a race during 1 hour starting at 12 a.m. until 6 p.m. and changing the runner every hour. This is why I used a 'fist number' option.

= What is the event expire date? =
It is the limit booking date; after this date the users are no longer allowed to book or update their booking. Nevertheless the admin users can still do both.

== Screenshots ==

1. Setup 'WP FEvents Book'
2. Sample of 'WP FEvents Book' front page
3. Colorful sample of 'WP FEvents Book' front page  

== Changelog ==
= 0.46 =
* fix for WP5.8.2 / PHP8.0
= 0.45 =
* include fix by @bl-solutions (thanks!)
  the issue was: refreshing delete page insert again the user
* tested up to WP5.2.2 / PHP 7.2
= 0.44 =
* tested for WP5.0
= 0.43 =
* responsive design using css
= 0.42 =
* User can delete his/her own booking
= 0.41 =
* CAPTCHA using Securimage-WP plugin
= 0.40 =
* WP4.0
= 0.39 =
* NotLoggedCanBook
= 0.38 =
* Hungarian translation added - thanks to Mladen Blatnik
= 0.37 =
* Croatian translation added - thanks to Borisa
* i18n datapicker updates
= 0.36 =
* ADD max number of users that can book an event (book is still possible for admin users)
= 0.35 =
* WP3.8 compatibility test
= 0.34 =
* confirm notification, some fixes
= 0.33 =
* User_maxusers
= 0.32 =
* fix apostrophes
= 0.31 =
* email notifications 6r
= 0.29 =
* fix issues if no events present
= 0.28 =
* link to invite subscribers to log in (wp-login.php)
* expire date css
= 0.27 =
* datapicker internationalization - thanks to Claude
= 0.26 =
* empty fields not displayed - WP 3.5.2 tested
= 0.25 =
* customizable elements
= 0.24 =
* rollback i18n admin data, small changes
= 0.23 =
* fix date display language in admin, textarea customizable css 
= 0.22 =
* French translation added - thanks to Claude
= 0.21 =
* fix: only last event is deletable
= 0.20 =
* small changes
= 0.19 =
* add events defaults to avoid warnings
* code for events 6ruote 
= 0.18 =
* add user choices
* some fixes
= 0.17 =
* event expire date
= 0.16 =
* added 'delete partecipants' button 
= 0.15 =
* can show number of total/confirmed or both partecipans
= 0.14 =
* Team list with a subscriber number added
= 0.13 =
* display existing user note if user has already booked
= 0.12 =
* complete data export
* allow not logged in users to see the Events
* delete Events & associated users
* some refactory
= 0.11 =
* allow data export
= 0.10 =
* fix
= 0.09 =
* fix delete user; renamed some fields
= 0.08 =
* add user confirmation checkbox
* user confirmation, email and info can be displayed or not
* display user choiche between: name and surname, nick, login, display name. All diplayed to admins
= 0.07 =
* German translation added - thanks to Arne !
= 0.06 =
* fix
= 0.05 =
* Use a shortcode [feventsbook] to display all enabled Events, or [feventsbook eventid=x] to display Event with ID=x
= 0.04 =
* internationalizations
= 0.03 =
* colorful events!
= 0.02 =
* fixes
= 0.01 =
* Initial release of plugin.
* Please test and report any issue you find, and any feature you want. I'll try to fix the firsts and to implement the seconds!

== Upgrade Notice ==
= 0.37 =
* Croatian translation added - thanks to Borisa
= 0.36 =
* ADD max number of users that can book an event
= 0.32 =
* fix apostrophes
= 0.28 =
* link to invite subscribers to log in (wp-login.php)
* expire date css
= 0.23 =
* fix date display language in admin, textarea customizable css 
= 0.19 =
* add events defaults to avoid warnings
= 0.18 =
* add user choices
= 0.17 =
* event expire date
= 0.16 =
* added 'clear booked user list' button 
= 0.15 =
* can show number of total/confirmed or both partecipans
= 0.14 =
* Team list with a subscriber number added
= 0.13 =
* display existing user note if user has already booked
= 0.12 =
* complete data export
* allow not logged in users to see the Events
* delete Events & associated users
* some refactory
= 0.11 =
* allow data export
= 0.09 =
* fix delete user
= 0.08 =
* add user confirmation checkbox
* user confirmation, email and info can be displayed or not
* display user choiche between: name and surname, nick, login, display name. All diplayed to admins
= 0.07 =
* German translation added - thanks to Arne !
= 0.05 =
* Use a shortcode [feventsbook] to display all enabled Events, or [feventsbook eventid=x] to display Event with ID=x
= 0.04 =
* internationalizations
= 0.03 =
* colorful events!
