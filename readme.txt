=== WP-Moods ===
Contributors: Dric1107
Donate link: http://www.driczone.net/blog
Tags: mood, moods, timeline, humor
Requires at least: 3.2
Tested up to: 3.2
Stable tag: 0.2

Calculate your general mood with several criteria. Get a timeline of all your moods. Don't forget to be happy !

== Description ==

Your mood is affected by several criteria, such as sex, food, work, family, love, neighbors, taxes, gas prices, numbers of spams, etc.
This plugin lets you calculate and display your mood by giving a value to those criteria. You can also display graphs of the evolution of one criterion.
In short, a totally useless plugin that will slow a little more your WP blog !

Let's see an example :

- Sex : 4/4 (very good !)
- Work : 1/4 (damn boss !)
- Food : 2/4 (burned burger)
- Spams : 4/4 (thanks askimet)

Total : 2.75/4 Feeling good, but not top.

But there is more : you can optionnaly set importance to the criteria. For example, you don't really care about food. Let's set it to 1/4. But sex is very important : 4/4.

- Sex : 4/4 x 4 = 16/16
- Work : 1/4 x 2 (default importance) = 2/8
- Food : 2/4 x 1 = 2/4
- Spams : 4/4 x 2 = 8/8

Total : 3.111/4
As sex is important and not food, the result is higher.

You can set as much criteria as you want. If importance is set to 0, the criteria won't be counted for the final mood.

Translations :

- French


(If you translated my plugin, please send the translated .po file at cedric@driczone.net )

[Plugin page](http://www.driczone.net/blog/plugins/wp-moods/) (French blog but feel free to comment in english)

Scripts used :

- Easy Slider By Alen Grakalic (http://cssglobe.com/post/4004/easy-slider-15-the-easiest-jquery-plugin-for-sliding)
- Flot by Ole Laursen (http://code.google.com/p/flot/)

== Installation ==

1. Download the plugin and unzip,
2. Upload the wp-moods folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,
4. Go to `Wp-Moods > Settings` to configure the plugin.
5. Use `[WPMOODS]` to display your mood in a page or post or use included widget.

== Frequently Asked Questions ==

= How do I avoid erasing css tweaks when I update the plugin ? =

Just put a copy of wp-moods.css in your theme dir, it will be processed instead of the css file included with the plugin.

= I have a poor hosting, is your plugin a big fat resources consumer ? =

I also have a poor hosting, so I try to keep my plugin as light as I can.

= Do you really test your plugin before publishing new versions at the Wordpress Plugin Repository ? =

Hum. I'm testing it on a single Wordpress installation, so it can't really be called "test". That's why there is often updates that just fix the previous ones... Sorry for that.


== Screenshots ==

1. admin widget
2. mood timeline (admin panel)
3. categories panel
4. widget

== ChangeLog ==

= 0.2 =
* Bug in widget when there is no data in DB.
* Shortcode added.

= 0.1 =
* First release
