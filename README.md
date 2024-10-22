# bluesky image grabber (a freshrss extension)

bluesky has added rss support, but the feeds it produces only contain text. this extension automatically fetches each post's opengraph metadata, then shoves any images it finds in there onto the end of the post body so you can view them in freshrss.

## installation

clone this repo, then copy the `xExtension-BlueskyImages` directory (and its contents) into freshrss' extensions directory (`/var/www/FreshRSS/extensions/`). once it's in place, reboot freshrss, go to the extensions config page and enable the plugin.

note that the plugin will only add images to posts fetched *after* it's been installed!

## future plans

- do something fancier with the formatting than just "shove the images on the end"
- add support for videos...?

## shouts out

- paul tunbridge, korbak and kevin papst: for creating the [freshrss-invidious](https://github.com/tunbridgep/freshrss-invidious/tree/master) plugin, which this one is sort-of based on
- the freshrss team & contributors: for their continuing work on [freshrss](https://github.com/FreshRSS/FreshRSS)!
