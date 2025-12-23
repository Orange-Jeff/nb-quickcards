=== NB QuickCards ===
Contributors: orangejeff
Donate link: https://netbound.ca/donate
Tags: link preview, cards, quote, post-it, embed
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful link preview cards, quote cards, and post-it notes with customizable styling. Turn boring URLs into rich previews.

== Description ==

NB QuickCards transforms plain URLs and quotes into beautiful, styled cards that grab attention and improve user experience.

**Three Card Types:**

= Link Cards =
Turn any URL into a rich preview card with auto-fetched:
* Page title
* Meta description
* Open Graph image
* Site favicon and domain

= Quote Cards =
Create beautiful quote blocks with:
* Large quote styling
* Author attribution with avatar
* Source link
* Multiple style options

= Post-it Notes =
Fun, memorable sticky notes with:
* Classic post-it colors (yellow, pink, blue, green, orange, purple)
* Realistic folded corner effect
* Perfect for tips, reminders, or callouts

**Key Features:**

* **Auto-fetches metadata** - Just paste a URL and the plugin does the rest
* **Smart caching** - URL metadata is cached to keep your site fast
* **Fully customizable** - Border color, width, style, radius, shadows
* **Responsive design** - Looks great on all devices
* **Dark mode support** - Adapts to user's system preference
* **No JavaScript required** - Pure CSS styling for fast loading

**Shortcode Examples:**

Link Card:
`[nb_link_card url="https://example.com"]`

Quote Card:
`[nb_quote_card source="CSS-Tricks" author="Chris Coyier"]
The best way to learn CSS is to use CSS.
[/nb_quote_card]`

Post-it Note:
`[nb_quote_card style="postit" postit_color="yellow"]
Don't forget to check the analytics!
[/nb_quote_card]`

Link List:
`[nb_link_list]
https://site1.com
https://site2.com
[/nb_link_list]`

== Installation ==

1. Upload the `nb-quickcards` folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu in WordPress
3. Go to Settings > NB QuickCards to configure defaults
4. Use shortcodes in posts, pages, or widgets

== Frequently Asked Questions ==

= How does the URL metadata fetching work? =
When you use `[nb_link_card]`, the plugin fetches the page and parses Open Graph meta tags (og:title, og:description, og:image). Results are cached to avoid repeated requests.

= Can I customize the appearance? =
Yes! Each shortcode accepts styling attributes like `border_color`, `border_width`, `border_radius`, and `shadow`. You can also set site-wide defaults in Settings > NB QuickCards.

= What post-it colors are available? =
Built-in colors: yellow, pink, blue, green, orange, purple. You can also use any hex color like `postit_color="#ff6b6b"`.

= Does this slow down my site? =
No. URL metadata is cached (24 hours by default), so each URL is only fetched once. The CSS is lightweight and there's no JavaScript.

= Can I use this with Gutenberg? =
Yes! Shortcodes work in the Shortcode block or any text block.

= What if a site doesn't have Open Graph tags? =
The plugin falls back to the page's `<title>` tag and standard meta description.

== Screenshots ==

1. Link card with auto-fetched metadata
2. Quote card with author attribution
3. Post-it note style in various colors
4. Compact link list
5. Settings page with default styling options

== Changelog ==

= 1.0.0 =
* Initial release
* Link card with URL metadata fetching
* Quote card with author attribution
* Post-it note style with folded corner
* Link list for compact multiple URLs
* Customizable borders, shadows, colors
* Smart caching with configurable duration
* Dark mode support

== Upgrade Notice ==

= 1.0.0 =
Initial release - start creating beautiful cards!
