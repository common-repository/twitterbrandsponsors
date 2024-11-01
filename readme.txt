=== TwitterBrandSponsors ===
Contributors: danzarrella, mashable
Tags: twitter,ads,advertising,brands,sidebar,widget,plugin,links,theme,stats,statistics,social
Requires at least: 2.0.2
Tested up to: 2.7
Stable tag: trunk
	
A limited number of brands (up to ten) can have their latest Tweets syndicated into your sidebar.

== Description ==

**Here's how it works**: a limited number of brands (up to ten) looking to engage with the social media community can have their latest Tweets syndicated into your WordPress blog's sidebar, and interested visitors can choose to connect with those brands on Twitter. You can find out more about how the ad unit looks [here][1] or looking in the Mashable sidebar.

The [TwitterBrandSponsors][2] plugin has a simple admin interface and includes options for display text, tweet caching and hiding reply tweets. The order of sponsors is randomly rotated for balance.

The plugin's settings page also features weekly and overall click counts on the outgoing links for each TwitterBrandSponsor. 

 [1]: http://mashable.com/advertise/twitter-brand-sponsors/
 [2]: http://mashable.com/2009/03/05/twitter-brand-sponsors/
	
== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

You can add the Twitter Brand Sponsors box to your site with the widget or if your template isn't widgetized, a copy-and-paste line of PHP code. To use the copy-and-paste method simply put the code below in your template where you want the box to appear:

&lt;?php TwitterBrandSponsors_display(); ?&gt;

You can control the display of the sponsor's tweets via CSS. The plugin uses 3 CSS ids:

1.  The Title of the Plugin: span#TwitterBrandSponsors_title
2.  Display Text: p#TwitterBrandSponsors\_display\_text
3.  Tweets Table: table#TwitterBrandSponsors

 ==Readme Generator== 

This Readme file was generated using <a href = 'http://sudarmuthu.com/projects/wp-readme.php'>wp-readme</a>, which generates readme files for WordPress Plugins.
