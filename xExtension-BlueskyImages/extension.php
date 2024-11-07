<?php

class BlueskyImageExtension extends Minz_Extension {

  // set it up!
  public function init() {
    $this->registerHook('entry_before_insert', array($this, 'handleBluesky'));  
  }

  // if its a bsky link, fetch it, rip the image urls out the opengraph tags, then shove em on the end
  // TODO: what's the return type here,
  public function handleBluesky($entry) {
    $link = $entry->link();

    if ($this->isBlueskyURL($link)) {
      // get image urls and post text
      $img_urls = $this->getImageURLsBetter($link);
      $html = $entry->content();

      // TODO: set the title as the original post text? seems like there's no need to do this rn, but
      // that might change if i wrap the post text in any html elements... we'll see
      // $entry->_title($html);

      // now slap the images on it
      // TODO: might be nice to have these be links
      // TODO: maybe wrap everything up inside some divs since bsky rss descriptions are just plain text
      for ($idx = 0; $idx < sizeof($img_urls); $idx++) {
        $img_url = $img_urls[$idx];
        $html .= '<img src="'.$img_url.'" />';
      }

      $entry->_content($html);
    }

    return $entry;
  }

  // check if the entry's link is a bluesky url
  private function isBlueskyURL(string $url): bool {
    $url_info = parse_url($url);
    $hostname = $url_info['host'];
    $hostname = str_replace("www.", "", $hostname);

    return $hostname == "bsky.app";
  }

  // fetch urls of any images in the post via the api instead of pointlessly scraping like a knucklehead,
  private function getImageURLsBetter($link): array {
    // switch off libxml's error handling so the logs don't get spammed with warnings...
    // TODO: uhhh do i need this now i'm not pulling html,
    libxml_use_internal_errors(true);

    // if we slap the guid into the api we get the post data in json. nice!
    // $api_url = 'https://public.api.bsky.app/xrpc/app.bsky.feed.getPostThread?uri='.$guid;
    // freshrss won't give the guid if the guid isn't unique. bsky seems to love repeating guids, so...
    // TODO: figure out how to use regex in php,
    $at_url = str_replace('https://bsky.app/profile/', 'at://', str_replace('/post/', '/app.bsky.feed.post/', $link));
    // Minz_Log::warning($at_url);
    $api_url = 'https://public.api.bsky.app/xrpc/app.bsky.feed.getPostThread?uri='.$at_url;

    // get the post
    $raw = file_get_contents($api_url);
    $json = json_decode($raw);

    // get the author's "did"
    $did = $json->thread->post->author->did;

    // if the embeds are images, iterate over them and piece together the urls
    $record = $json->thread->post->record;
    $img_urls = array();
    if (isset($record->embed) && $record->embed->{'$type'} == 'app.bsky.embed.images') {
      $base_url = 'https://cdn.bsky.app/img/feed_fullsize/plain/'.$did.'/';
      $images = $record->embed->images;
      for ($idx = 0; $idx < sizeof($images); $idx++)
      {
        $image = $images[$idx];
        $img_urls[] = $base_url.$image->image->ref->{'$link'};
      }
    }

    // ok turn it back on now
    libxml_use_internal_errors(false);

    return $img_urls;
  }

  // fetch urls of any images in the post from the post's link preview metadata
  private function getImageURLs($link): array { 
    // switch off libxml's error handling so the logs don't get spammed with warnings...
    libxml_use_internal_errors(true);

    // get the post
    $instance_page = file_get_contents($link);
    $page = new DOMDocument;
    $page->loadHtml($instance_page);

    // iterate over meta tags to get the image urls from all og:image tags
    $metas = $page->getElementsByTagName('meta');
    $img_urls = array();
    for ($idx = 0; $idx < $metas->length; $idx++)
    {
      $meta = $metas->item($idx);
      if ($meta->getAttribute('property') == 'og:image')
        $img_urls[] = $meta->getAttribute('content');
    }

    // ok turn it back on now
    libxml_use_internal_errors(false);

    return $img_urls;
  }
}
